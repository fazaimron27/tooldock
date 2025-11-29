<?php

namespace App\Services\Modules;

use App\Exceptions\MissingDependencyException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\App\Services\PermissionCacheService;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Nwidart\Modules\Module;
use Spatie\Permission\Models\Permission;

class ModuleLifecycleService
{
    /**
     * Cached module map for case-insensitive lookups
     * Maps lowercase module name => actual module name
     *
     * @var array<string, string>|null
     */
    private ?array $moduleMapCache = null;

    public function __construct(
        private RepositoryInterface $moduleRepository,
        private ActivatorInterface $activator
    ) {}

    /**
     * Validate that all cross-module class references are declared as dependencies
     *
     * Scans PHP files in the module's app directory to find references to other modules
     * (e.g., `Modules\Core\App\Models\User`) and ensures they are declared in module.json.
     * Also validates that all declared dependencies exist as modules.
     *
     * @param  Module  $module  The module to validate
     * @param  bool  $skipValidation  If true, skip dependency validation (useful for CI/CD or trusted modules)
     * @return array<string> Array of validated declared dependencies (all confirmed to exist as modules)
     *
     * @throws MissingDependencyException When undeclared module dependencies are found or declared dependencies don't exist
     */
    private function validateModuleDependencies(Module $module, bool $skipValidation = false): array
    {
        if ($skipValidation || config('modules.skip_dependency_validation', false)) {
            return $this->normalizeDependencies($module->get('requires', []));
        }

        $startTime = microtime(true);
        $moduleName = $module->getName();
        $modulePath = $module->getPath();
        $appPath = $modulePath.'/app';

        $declaredDependencies = $module->get('requires', []);

        $normalizedDependencies = $this->normalizeDependencies($declaredDependencies);

        $normalizedDependencies = array_values(array_filter($normalizedDependencies, function ($dep) use ($moduleName) {
            return strcasecmp($dep, $moduleName) !== 0;
        }));
        $invalidDependencies = [];
        $validatedDependencies = [];
        foreach ($normalizedDependencies as $declaredDependency) {
            if (! ModuleFacade::has($declaredDependency)) {
                $invalidDependencies[] = $declaredDependency;
            } else {
                $validatedDependencies[] = $declaredDependency;
            }
        }

        if (! empty($invalidDependencies)) {
            $invalidList = implode("', '", $invalidDependencies);
            $firstInvalid = reset($invalidDependencies);
            throw new MissingDependencyException(
                "Module '{$moduleName}' declares the following dependencies that do not exist: '{$invalidList}'.\n".
                    "Please remove them from the 'requires' array in {$moduleName}/module.json:\n".
                    "  \"requires\": [\"...\", ...] (remove '{$firstInvalid}')"
            );
        }

        if (! is_dir($appPath)) {
            return $validatedDependencies;
        }

        $foundDependencies = $this->scanModuleForDependencies($appPath, $moduleName, $module);
        $normalizedFoundDependencies = $this->normalizeDependencies($foundDependencies);

        $normalizedFoundDependencies = array_values(array_filter($normalizedFoundDependencies, function ($dep) use ($moduleName) {
            return strcasecmp($dep, $moduleName) !== 0;
        }));

        $undeclaredDependencies = array_diff($normalizedFoundDependencies, $normalizedDependencies);

        $validationTime = (microtime(true) - $startTime) * 1000;

        if ($validationTime > 100) {
            Log::warning("Module dependency validation took {$validationTime}ms for module '{$moduleName}'", [
                'module' => $moduleName,
                'validation_time_ms' => round($validationTime, 2),
                'files_scanned' => $this->countPhpFiles($appPath),
            ]);
        }

        if (! empty($undeclaredDependencies)) {
            $dependenciesList = implode("', '", $undeclaredDependencies);
            $firstDependency = reset($undeclaredDependencies);
            throw new MissingDependencyException(
                "Module '{$moduleName}' uses classes from the following modules that are not declared as dependencies: '{$dependenciesList}'.\n".
                    "Please add them to the 'requires' array in {$moduleName}/module.json:\n".
                    "  \"requires\": [\"{$firstDependency}\", ...]"
            );
        }

        return $validatedDependencies;
    }

    /**
     * Get or build the module map for case-insensitive lookups
     *
     * Caches the result to avoid repeated calls to ModuleFacade::all()
     *
     * @return array<string, string> Map of lowercase module name => actual module name
     */
    private function getModuleMap(): array
    {
        if ($this->moduleMapCache === null) {
            $this->moduleMapCache = [];
            foreach (ModuleFacade::all() as $module) {
                $moduleName = $module->getName();
                $this->moduleMapCache[strtolower($moduleName)] = $moduleName;
            }
        }

        return $this->moduleMapCache;
    }

    /**
     * Normalize dependencies to ensure case-insensitive uniqueness and correct case
     *
     * This method:
     * - Removes duplicate dependencies (case-insensitive)
     * - Maps dependencies to their correct case using actual module names from ModuleFacade::all()
     * - Returns a deduplicated array with proper casing
     *
     * @param  array<string>  $dependencies  Array of dependency names (may have duplicates or wrong case)
     * @return array<string> Normalized array with unique dependencies in correct case
     */
    private function normalizeDependencies(array $dependencies): array
    {
        if (empty($dependencies)) {
            return [];
        }

        $moduleMap = $this->getModuleMap();

        $normalized = [];
        $seen = [];

        foreach ($dependencies as $dependency) {
            $lowerDependency = strtolower(trim($dependency));

            if (empty($lowerDependency)) {
                continue;
            }

            if (isset($seen[$lowerDependency])) {
                continue;
            }

            if (isset($moduleMap[$lowerDependency])) {
                $correctCase = $moduleMap[$lowerDependency];
            } else {
                $foundModule = ModuleFacade::find($dependency);
                if ($foundModule !== null) {
                    $correctCase = $foundModule->getName();
                    $this->moduleMapCache[$lowerDependency] = $correctCase;
                } else {
                    $correctCase = $dependency;
                }
            }

            $normalized[] = $correctCase;
            $seen[$lowerDependency] = true;
        }

        return array_values($normalized);
    }

    /**
     * Scan PHP files in a directory for cross-module class references
     *
     * Uses PHP tokenizer for accurate parsing that understands PHP syntax context.
     * Ignores comments, strings, and false positives. Uses caching to avoid re-scanning
     * unchanged modules. Cache key includes module name, version, and file hash for proper invalidation.
     *
     * @param  string  $directory  The directory to scan
     * @param  string  $currentModuleName  The name of the module being scanned (to exclude self-references)
     * @param  Module  $module  The module object (used for cache key generation)
     * @return array<string> Array of module names found in use statements and class references
     */
    private function scanModuleForDependencies(string $directory, string $currentModuleName, Module $module): array
    {
        $moduleName = $module->getName();
        $moduleVersion = $module->get('version', '1.0.0');
        $fileHash = $this->calculateModuleFilesHash($directory);
        $cacheKey = "module_dependencies:{$moduleName}:{$moduleVersion}:{$fileHash}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($directory, $currentModuleName) {
            $dependencies = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    if ($content === false) {
                        continue;
                    }

                    $fileDependencies = $this->scanPhpFileForDependencies($content, $currentModuleName);
                    $dependencies = array_merge($dependencies, $fileDependencies);
                }
            }

            return array_unique($dependencies);
        });
    }

    /**
     * Calculate a hash of all PHP files in a directory for cache invalidation
     *
     * Creates a hash based on file paths and modification times to detect when
     * module files change. This ensures cache is invalidated when code changes,
     * even if the module version doesn't change.
     *
     * @param  string  $directory  The directory to hash
     * @return string Hash string representing the state of all PHP files
     */
    private function calculateModuleFilesHash(string $directory): string
    {
        if (! is_dir($directory)) {
            return 'empty';
        }

        $fileHashes = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getPathname();
                $mtime = $file->getMTime();
                $size = $file->getSize();
                $fileHashes[] = $filePath.':'.$mtime.':'.$size;
            }
        }

        if (empty($fileHashes)) {
            return 'empty';
        }

        sort($fileHashes);

        return substr(md5(implode('|', $fileHashes)), 0, 16);
    }

    /**
     * Scan a single PHP file for cross-module dependencies using tokenizer
     *
     * Uses PHP's tokenizer to accurately parse use statements and fully qualified class names,
     * ignoring comments, strings, and other non-code contexts.
     *
     * @param  string  $content  The PHP file content
     * @param  string  $currentModuleName  The name of the module being scanned (to exclude self-references)
     * @return array<string> Array of module names found
     */
    private function scanPhpFileForDependencies(string $content, string $currentModuleName): array
    {
        $dependencies = [];
        $tokens = @token_get_all($content);

        if ($tokens === false) {
            return [];
        }

        $inUseStatement = false;
        $useNamespace = '';
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];

            if (! is_array($token)) {
                if ($token === ';' && $inUseStatement) {
                    $this->processUseStatement($useNamespace, $currentModuleName, $dependencies);
                    $inUseStatement = false;
                    $useNamespace = '';
                } elseif ($token === ',' && $inUseStatement) {
                    $this->processUseStatement($useNamespace, $currentModuleName, $dependencies);
                    $useNamespace = '';
                }

                continue;
            }

            [$id, $text] = $token;

            if (in_array($id, [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                continue;
            }

            if ($id === T_USE) {
                $nextToken = $this->getNextNonWhitespaceToken($tokens, $i);
                if ($nextToken !== null && is_array($nextToken) && $nextToken[0] === T_STRING) {
                    $nextText = strtolower($nextToken[1]);
                    if ($nextText === 'function' || $nextText === 'const') {
                        continue;
                    }
                }

                $inUseStatement = true;
                $useNamespace = '';

                continue;
            }

            if ($inUseStatement) {
                if ($id === T_STRING || $id === T_NS_SEPARATOR) {
                    $useNamespace .= $text;
                } elseif ($id === T_WHITESPACE) {
                    continue;
                } elseif ($id === T_AS) {
                    $this->processUseStatement($useNamespace, $currentModuleName, $dependencies);
                    $useNamespace = '';
                    while ($i < $tokenCount - 1) {
                        $i++;
                        $nextToken = $tokens[$i];
                        if (! is_array($nextToken) && ($nextToken === ';' || $nextToken === ',')) {
                            if ($nextToken === ',') {
                                $inUseStatement = true;
                            } else {
                                $inUseStatement = false;
                            }
                            break;
                        }
                    }
                }
            }

            if ($id === T_NAME_FULLY_QUALIFIED) {
                if (preg_match('/^\\\\?Modules\\\\([A-Za-z0-9_]+)/', $text, $matches)) {
                    $referencedModule = $matches[1];
                    if (strcasecmp($referencedModule, $currentModuleName) !== 0 && ModuleFacade::has($referencedModule)) {
                        $dependencies[] = $referencedModule;
                    }
                }

                continue;
            }

            if ($id === T_NAME_QUALIFIED) {
                if (preg_match('/^Modules\\\\([A-Za-z0-9_]+)/', $text, $matches)) {
                    $referencedModule = $matches[1];
                    if (strcasecmp($referencedModule, $currentModuleName) !== 0 && ModuleFacade::has($referencedModule)) {
                        $dependencies[] = $referencedModule;
                    }
                }

                continue;
            }

            if ($id === T_NAME_RELATIVE) {
                if (preg_match('/^Modules\\\\([A-Za-z0-9_]+)/', $text, $matches)) {
                    $referencedModule = $matches[1];
                    if (strcasecmp($referencedModule, $currentModuleName) !== 0 && ModuleFacade::has($referencedModule)) {
                        $dependencies[] = $referencedModule;
                    }
                }

                continue;
            }

            if ($id === T_NS_SEPARATOR && $i < $tokenCount - 1) {
                $nextToken = $tokens[$i + 1] ?? null;
                if (is_array($nextToken) && $nextToken[0] === T_STRING && $nextToken[1] === 'Modules') {
                    $moduleNamespace = '\\Modules\\';
                    $j = $i + 2;
                    while ($j < $tokenCount) {
                        $nsToken = $tokens[$j] ?? null;
                        if (! is_array($nsToken)) {
                            break;
                        }
                        if ($nsToken[0] === T_STRING) {
                            $moduleNamespace .= $nsToken[1];
                            if (preg_match('/^(\\\\?)?Modules\\\\([A-Za-z0-9_]+)/', $moduleNamespace, $matches)) {
                                $referencedModule = $matches[2];
                                if (strcasecmp($referencedModule, $currentModuleName) !== 0 && ModuleFacade::has($referencedModule)) {
                                    $dependencies[] = $referencedModule;
                                    break;
                                }
                            }
                            $j++;
                        } elseif ($nsToken[0] === T_NS_SEPARATOR) {
                            $moduleNamespace .= '\\';
                            $j++;
                        } else {
                            break;
                        }
                    }
                }
            } elseif ($id === T_STRING && $text === 'Modules' && $i < $tokenCount - 1) {
                $nextToken = $tokens[$i + 1] ?? null;
                if (is_array($nextToken) && $nextToken[0] === T_NS_SEPARATOR) {
                    $moduleNamespace = 'Modules\\';
                    $j = $i + 2;
                    while ($j < $tokenCount) {
                        $nsToken = $tokens[$j] ?? null;
                        if (! is_array($nsToken)) {
                            break;
                        }
                        if ($nsToken[0] === T_STRING) {
                            $moduleNamespace .= $nsToken[1];
                            if (preg_match('/^Modules\\\\([A-Za-z0-9_]+)/', $moduleNamespace, $matches)) {
                                $referencedModule = $matches[1];
                                if (strcasecmp($referencedModule, $currentModuleName) !== 0 && ModuleFacade::has($referencedModule)) {
                                    $dependencies[] = $referencedModule;
                                    break;
                                }
                            }
                            $j++;
                        } elseif ($nsToken[0] === T_NS_SEPARATOR) {
                            $moduleNamespace .= '\\';
                            $j++;
                        } else {
                            break;
                        }
                    }
                }
            }
        }

        if ($inUseStatement && ! empty($useNamespace)) {
            $this->processUseStatement($useNamespace, $currentModuleName, $dependencies);
        }

        return $dependencies;
    }

    /**
     * Process a use statement namespace and extract module dependencies
     *
     * @param  string  $namespace  The namespace from the use statement
     * @param  string  $currentModuleName  The current module name (to exclude)
     * @param  array<string>  $dependencies  Reference to dependencies array to populate
     */
    private function processUseStatement(string $namespace, string $currentModuleName, array &$dependencies): void
    {
        if (preg_match('/^Modules\\\\([A-Za-z0-9_]+)\\\\/', $namespace, $matches)) {
            $referencedModule = $matches[1];
            if (strcasecmp($referencedModule, $currentModuleName) !== 0 && ModuleFacade::has($referencedModule)) {
                $dependencies[] = $referencedModule;
            }
        }
    }

    /**
     * Get the next non-whitespace token from the token array
     *
     * @param  array  $tokens  Array of tokens
     * @param  int  $currentIndex  Current index in tokens array
     * @return array|null The next non-whitespace token or null if not found
     */
    private function getNextNonWhitespaceToken(array $tokens, int $currentIndex): ?array
    {
        $tokenCount = count($tokens);
        for ($i = $currentIndex + 1; $i < $tokenCount; $i++) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] !== T_WHITESPACE) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Count PHP files in a directory (for performance monitoring)
     *
     * @param  string  $directory  The directory to count files in
     * @return int Number of PHP files found
     */
    private function countPhpFiles(string $directory): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if all required dependencies are installed (and optionally enabled)
     *
     * Validates that all modules listed in module.json "requires" array are:
     * - Installed in the database
     * - Optionally enabled (when $checkEnabled is true)
     *
     * Note: Existence validation is handled by validateModuleDependencies() to avoid duplication.
     *
     * @param  Module  $module  The module to check dependencies for
     * @param  bool  $checkEnabled  If true, also verifies dependencies are enabled (for enable operation)
     * @param  bool  $skipValidation  If true, skip dependency validation (useful for CI/CD or trusted modules)
     *
     * @throws MissingDependencyException When a required dependency is not installed or not enabled
     */
    private function checkDependencies(Module $module, bool $checkEnabled = false, bool $skipValidation = false): void
    {
        $validatedDependencies = $this->validateModuleDependencies($module, $skipValidation);

        if (empty($validatedDependencies)) {
            return;
        }

        $operation = $checkEnabled ? 'enable' : 'install';
        $moduleName = $module->getName();

        foreach ($validatedDependencies as $requiredModuleName) {
            $isInstalled = DB::table('modules_statuses')
                ->where('name', $requiredModuleName)
                ->where('is_installed', true)
                ->exists();

            if (! $isInstalled) {
                throw new MissingDependencyException(
                    "Cannot {$operation} '{$moduleName}' because the required dependency '{$requiredModuleName}' is not installed.\n".
                        "Please install '{$requiredModuleName}' first."
                );
            }

            if ($checkEnabled && ! ModuleFacade::isEnabled($requiredModuleName)) {
                throw new MissingDependencyException(
                    "Cannot {$operation} '{$moduleName}' because the required dependency '{$requiredModuleName}' is not enabled.\n".
                        "Please enable '{$requiredModuleName}' first."
                );
            }
        }
    }

    /**
     * Install a module
     *
     * Performs the complete installation process:
     * 1. Validates dependencies are installed
     * 2. Records installation in database (is_installed, version, installed_at)
     * 3. Temporarily enables module (so migrations can be discovered)
     * 4. Runs database migrations
     * 5. Optionally runs seeders
     * 6. Calls enable() to activate module and perform cleanup
     *
     * @param  string  $moduleName  The name of the module to install
     * @param  bool  $withSeed  If true, run module seeders after migrations
     * @param  bool  $skipValidation  If true, skip dependency validation (useful for CI/CD or trusted modules)
     *
     * @throws MissingDependencyException When required dependencies are not installed
     */
    public function install(string $moduleName, bool $withSeed = false, bool $skipValidation = false): void
    {
        Log::info("ModuleLifecycleService: Starting install for module '{$moduleName}'");

        $module = $this->moduleRepository->findOrFail($moduleName);
        Log::info("ModuleLifecycleService: Found module '{$moduleName}'", [
            'path' => $module->getPath(),
            'version' => $module->get('version'),
        ]);

        $this->checkDependencies($module, checkEnabled: false, skipValidation: $skipValidation);
        Log::info("ModuleLifecycleService: Dependencies checked for '{$moduleName}'");

        DB::table('modules_statuses')->updateOrInsert(
            ['name' => $moduleName],
            [
                'is_installed' => true,
                'installed_at' => now(),
                'version' => $module->get('version'),
                'updated_at' => now(),
            ]
        );
        Log::info("ModuleLifecycleService: Updated modules_statuses for '{$moduleName}'");

        $this->activator->enable($module);
        Log::info("ModuleLifecycleService: Enabled module '{$moduleName}' via activator");

        $migrationPath = $module->getPath().'/database/migrations';
        if (is_dir($migrationPath) && ! empty(glob($migrationPath.'/*.php'))) {
            Log::info("ModuleLifecycleService: Found migrations for '{$moduleName}'", [
                'path' => $migrationPath,
            ]);
            try {
                Artisan::call('module:migrate', [
                    'module' => $moduleName,
                    '--force' => true,
                ]);
                Log::info("ModuleLifecycleService: Ran module:migrate for '{$moduleName}'");
            } catch (\Exception $e) {
                Log::warning("ModuleLifecycleService: module:migrate failed for '{$moduleName}'", [
                    'error' => $e->getMessage(),
                ]);
            }

            Artisan::call('migrate', [
                '--path' => 'Modules/'.$moduleName.'/database/migrations',
                '--force' => true,
            ]);
            Log::info("ModuleLifecycleService: Ran migrate for '{$moduleName}'");
        } else {
            Log::info("ModuleLifecycleService: No migrations found for '{$moduleName}'");
        }

        $this->runPermissionSeeder($moduleName);

        if ($withSeed) {
            Log::info("ModuleLifecycleService: Running database seeders for '{$moduleName}'");
            $seedResult = Artisan::call('module:seed', [
                'module' => $moduleName,
                '--force' => true,
            ]);
            if ($seedResult !== 0) {
                Log::warning("ModuleLifecycleService: Database seeder failed for '{$moduleName}'", [
                    'error' => 'This is optional and only creates sample data.',
                ]);
            } else {
                Log::info("ModuleLifecycleService: Database seeders completed for '{$moduleName}'");
            }
        }

        $this->enable($moduleName, skipValidation: $skipValidation);
        Log::info("ModuleLifecycleService: Installation complete for '{$moduleName}'");
    }

    /**
     * Uninstall a module
     *
     * Performs the complete uninstallation process:
     * 1. Validates no installed modules depend on this module
     * 2. Disables module (deactivates routes and services)
     * 3. Rolls back database migrations
     * 4. Marks module as uninstalled in database
     *
     * Note: This does NOT delete the module files, only removes it from the system.
     *
     * @param  string  $moduleName  The name of the module to uninstall
     *
     * @throws \RuntimeException When other installed modules depend on this module
     */
    public function uninstall(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        if ($module->get('protected') === true) {
            throw new \RuntimeException(
                "Cannot uninstall '{$moduleName}' because it is a protected module.\n".
                    'Protected modules are essential to the system and cannot be removed.'
            );
        }

        $this->checkReverseDependencies($moduleName);

        $this->disable($moduleName);

        $this->cleanupModulePermissions($moduleName);

        Artisan::call('module:migrate-rollback', [
            'module' => $moduleName,
            '--force' => true,
        ]);

        DB::table('modules_statuses')
            ->where('name', $moduleName)
            ->update([
                'is_installed' => false,
                'updated_at' => now(),
            ]);
    }

    /**
     * Enable a module
     *
     * Activates a previously installed module:
     * 1. Verifies module is installed
     * 2. Validates dependencies are installed AND enabled
     * 3. Sets is_active flag in database
     * 4. Enables via activator (updates nwidart/laravel-modules cache)
     * 5. Performs cleanup (reload statuses, refresh registry, clear caches, generate routes)
     *
     * Called by install() after migrations/seeders, or independently to re-enable a disabled module.
     *
     * @param  string  $moduleName  The name of the module to enable
     * @param  bool  $skipValidation  If true, skip dependency validation (useful for CI/CD or trusted modules)
     *
     * @throws MissingDependencyException When required dependencies are not installed or enabled
     * @throws \RuntimeException When module is not installed
     */
    public function enable(string $moduleName, bool $skipValidation = false): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        $isInstalled = DB::table('modules_statuses')
            ->where('name', $moduleName)
            ->where('is_installed', true)
            ->exists();

        if (! $isInstalled) {
            throw new \RuntimeException(
                "Cannot enable '{$moduleName}' because it is not installed.\n".
                    "Please install '{$moduleName}' first."
            );
        }

        $this->checkDependencies($module, checkEnabled: true, skipValidation: $skipValidation);

        DB::table('modules_statuses')->updateOrInsert(
            ['name' => $moduleName],
            [
                'is_active' => true,
                'updated_at' => now(),
            ]
        );

        $this->activator->enable($module);

        $this->finalizeModuleOperation();
    }

    /**
     * Disable a module
     *
     * Deactivates an enabled module:
     * 1. Validates no active modules depend on this module
     * 2. Sets is_active flag to false in database
     * 3. Disables via activator (updates nwidart/laravel-modules cache)
     * 4. Performs cleanup (reload statuses, refresh registry, clear caches, generate routes)
     *
     * Called by uninstall() before rollback, or independently to temporarily disable a module.
     *
     * @param  string  $moduleName  The name of the module to disable
     *
     * @throws \RuntimeException When active modules depend on this module
     */
    public function disable(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);

        if ($module->get('protected') === true) {
            throw new \RuntimeException(
                "Cannot disable '{$moduleName}' because it is a protected module.\n".
                    'Protected modules are essential to the system and must remain enabled.'
            );
        }

        $this->checkReverseDependenciesForDisable($moduleName);

        DB::table('modules_statuses')
            ->where('name', $moduleName)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        $this->activator->disable($module);

        $this->finalizeModuleOperation();
    }

    /**
     * Check if other active modules depend on this module (for disable operation)
     *
     * Prevents disabling a module that is required by currently active modules.
     * Only checks enabled modules since disabled modules don't need their dependencies active.
     *
     * @param  string  $moduleName  The module to check reverse dependencies for
     *
     * @throws \RuntimeException When active modules depend on this module
     */
    private function checkReverseDependenciesForDisable(string $moduleName): void
    {
        $dependents = [];

        foreach (ModuleFacade::allEnabled() as $activeModule) {
            $activeModuleName = $activeModule->getName();

            if ($activeModuleName === $moduleName) {
                continue;
            }

            $requires = $activeModule->get('requires', []);

            if (in_array($moduleName, $requires, true)) {
                $dependents[] = $activeModuleName;
            }
        }

        if (! empty($dependents)) {
            $dependentsList = implode("', '", $dependents);
            $firstDependent = reset($dependents);
            throw new \RuntimeException(
                "Cannot disable '{$moduleName}' because the following active modules depend on it: '{$dependentsList}'.\n".
                    'Please disable the dependent modules first.'
            );
        }
    }

    /**
     * Check if other installed modules depend on this module (for uninstall operation)
     *
     * Prevents uninstalling a module that is required by other installed modules.
     * Checks ALL installed modules (not just enabled) since installed modules may be enabled later.
     *
     * @param  string  $moduleName  The module to check reverse dependencies for
     *
     * @throws \RuntimeException When installed modules depend on this module
     */
    private function checkReverseDependencies(string $moduleName): void
    {
        $installedModules = DB::table('modules_statuses')
            ->where('is_installed', true)
            ->pluck('name')
            ->toArray();

        $dependents = [];

        foreach ($installedModules as $installedModuleName) {
            if ($installedModuleName === $moduleName) {
                continue;
            }

            $installedModule = ModuleFacade::find($installedModuleName);

            if ($installedModule === null) {
                continue;
            }

            $requires = $installedModule->get('requires', []);

            if (in_array($moduleName, $requires, true)) {
                $dependents[] = $installedModuleName;
            }
        }

        if (! empty($dependents)) {
            $dependentsList = implode("', '", $dependents);
            $firstDependent = reset($dependents);
            throw new \RuntimeException(
                "Cannot uninstall '{$moduleName}' because the following installed modules depend on it: '{$dependentsList}'.\n".
                    'Please uninstall the dependent modules first.'
            );
        }
    }

    /**
     * Reload statuses if the activator is a DatabaseActivator
     *
     * DatabaseActivator caches module statuses in memory. After external database changes
     * (like direct SQL updates), we need to reload the cache to keep it in sync.
     *
     * This is a no-op for other activator types (e.g., FileActivator).
     */
    private function reloadStatusesIfNeeded(): void
    {
        if ($this->activator instanceof DatabaseActivator) {
            $this->activator->reloadStatuses();
        }
    }

    /**
     * Refresh the module registry by scanning, registering, and booting modules
     *
     * Ensures the nwidart/laravel-modules package discovers any new or changed modules
     * after installation/enabling. This is necessary because:
     * - scan() discovers modules in the filesystem
     * - register() registers service providers
     * - boot() boots registered providers
     */
    private function refreshModuleRegistry(): void
    {
        ModuleFacade::scan();
        ModuleFacade::register();
        ModuleFacade::boot();
    }

    /**
     * Clear application caches (config and routes)
     *
     * Clears Laravel's cached configuration and routes to ensure changes from
     * newly installed/enabled modules are picked up immediately.
     */
    private function clearApplicationCaches(): void
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
    }

    /**
     * Finalize a module operation by performing all cleanup steps
     *
     * Centralized cleanup method called after enable/disable operations.
     * Ensures consistency by:
     * 1. Reloading activator statuses (if DatabaseActivator)
     * 2. Refreshing module registry (discover new modules)
     * 3. Clearing caches (config, routes)
     * 4. Regenerating Ziggy routes (for frontend route helpers)
     */
    private function finalizeModuleOperation(): void
    {
        $this->reloadStatusesIfNeeded();
        $this->refreshModuleRegistry();
        $this->clearApplicationCaches();
        $this->generateZiggyRoutes();
    }

    /**
     * Generate Ziggy routes using the default Artisan command
     *
     * Regenerates the Ziggy route definitions file (resources/js/ziggy.js) to include
     * routes from newly installed/enabled modules. This allows the frontend to use
     * route() helper functions for module routes.
     */
    private function generateZiggyRoutes(): void
    {
        Artisan::call('ziggy:generate');
    }

    /**
     * Discover and register all available modules in the database
     *
     * Scans the Modules directory for all available modules and registers them
     * in the modules_statuses table. This is useful after a fresh database migration
     * to ensure all modules are tracked in the database.
     *
     * Modules are registered with is_installed=false and is_active=false by default.
     * They must be explicitly installed using install() or module:manage command.
     *
     * @return array<string> Array of discovered module names
     */
    public function discoverAndRegisterAll(): array
    {
        ModuleFacade::scan();

        $allModules = ModuleFacade::all();
        $discoveredModules = [];

        foreach ($allModules as $module) {
            $moduleName = $module->getName();

            $exists = DB::table('modules_statuses')
                ->where('name', $moduleName)
                ->exists();

            if (! $exists) {
                DB::table('modules_statuses')->insert([
                    'name' => $moduleName,
                    'is_active' => false,
                    'is_installed' => false,
                    'version' => $module->get('version'),
                    'installed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('modules_statuses')
                    ->where('name', $moduleName)
                    ->update([
                        'version' => $module->get('version'),
                        'updated_at' => now(),
                    ]);
            }

            $discoveredModules[] = $moduleName;
        }

        $this->reloadStatusesIfNeeded();

        return $discoveredModules;
    }

    /**
     * Discover and install all protected modules automatically.
     *
     * This method is called after migrations complete on a fresh database
     * to automatically install essential protected modules (like Core).
     * Only modules marked as "protected": true in their module.json are installed.
     *
     * Modules are installed in dependency order (modules with no dependencies first).
     *
     * @return array<string> Array of installed module names
     */
    public function installProtectedModules(): array
    {
        Log::info('ModuleLifecycleService: Starting installProtectedModules');

        $this->discoverAndRegisterAll();

        $allModules = ModuleFacade::all();
        Log::info('ModuleLifecycleService: Discovered modules', [
            'count' => count($allModules),
            'names' => array_map(fn ($m) => $m->getName(), $allModules),
        ]);

        $protectedModules = [];

        foreach ($allModules as $module) {
            if ($module->get('protected') === true) {
                $protectedModules[] = $module;
                Log::info('ModuleLifecycleService: Found protected module', [
                    'name' => $module->getName(),
                    'version' => $module->get('version'),
                ]);
            }
        }

        if (empty($protectedModules)) {
            Log::warning('ModuleLifecycleService: No protected modules found');

            return [];
        }

        Log::info('ModuleLifecycleService: Sorting protected modules by dependencies');
        usort($protectedModules, function (Module $a, Module $b) {
            $aRequires = $a->get('requires', []);
            $bRequires = $b->get('requires', []);

            if (in_array($b->getName(), $aRequires, true)) {
                return 1;
            }

            if (in_array($a->getName(), $bRequires, true)) {
                return -1;
            }

            return 0;
        });

        $installedModules = [];

        foreach ($protectedModules as $module) {
            $moduleName = $module->getName();

            Log::info("ModuleLifecycleService: Processing module '{$moduleName}'");

            $isInstalled = DB::table('modules_statuses')
                ->where('name', $moduleName)
                ->where('is_installed', true)
                ->exists();

            Log::info("ModuleLifecycleService: Module '{$moduleName}' installation status", [
                'isInstalled' => $isInstalled,
            ]);

            if ($isInstalled) {
                Log::info("ModuleLifecycleService: Skipping '{$moduleName}' - already installed");

                continue;
            }

            try {
                Log::info("ModuleLifecycleService: Installing module '{$moduleName}'");
                $this->install($moduleName, withSeed: false, skipValidation: true);
                $installedModules[] = $moduleName;
                Log::info("ModuleLifecycleService: Successfully installed module '{$moduleName}'");
            } catch (\Exception $e) {
                Log::error(
                    "Failed to auto-install protected module '{$moduleName}'",
                    [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }
        }

        Log::info('ModuleLifecycleService: installProtectedModules complete', [
            'installed' => $installedModules,
            'count' => count($installedModules),
        ]);

        return $installedModules;
    }

    /**
     * Run permission seeder for a module if it exists.
     *
     * Permission seeders are required for module functionality and should
     * always run during installation, even without the --seed flag.
     *
     * @param  string  $moduleName  The name of the module
     */
    private function runPermissionSeeder(string $moduleName): void
    {
        $module = $this->moduleRepository->findOrFail($moduleName);
        $seederPath = $module->getPath().'/database/seeders';
        $permissionSeederClass = "Modules\\{$moduleName}\\Database\\Seeders\\{$moduleName}PermissionSeeder";

        $permissionSeederFile = $seederPath."/{$moduleName}PermissionSeeder.php";
        if (! file_exists($permissionSeederFile)) {
            Log::info("ModuleLifecycleService: No permission seeder found for '{$moduleName}'");

            return;
        }

        if (! class_exists($permissionSeederClass)) {
            Log::warning("ModuleLifecycleService: Permission seeder class not found: {$permissionSeederClass}");

            return;
        }

        try {
            Log::info("ModuleLifecycleService: Running permission seeder for '{$moduleName}'");
            $seeder = app($permissionSeederClass);
            $seeder->run();
            Log::info("ModuleLifecycleService: Permission seeder completed for '{$moduleName}'");
        } catch (\Exception $e) {
            Log::error("ModuleLifecycleService: Failed to run permission seeder for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Clean up module permissions when uninstalling a module.
     *
     * Removes all permissions that start with the module prefix (e.g., "blog.*", "newsletter.*")
     * and cleans up related pivot table entries.
     *
     * @param  string  $moduleName  The name of the module being uninstalled
     */
    private function cleanupModulePermissions(string $moduleName): void
    {
        $modulePrefix = strtolower($moduleName).'.';

        try {
            Log::info("ModuleLifecycleService: Cleaning up permissions for '{$moduleName}'", [
                'prefix' => $modulePrefix,
            ]);

            $permissions = Permission::where('name', 'like', $modulePrefix.'%')->get();

            if ($permissions->isEmpty()) {
                Log::info("ModuleLifecycleService: No permissions found for '{$moduleName}'");
                app(PermissionCacheService::class)->clear();

                return;
            }

            $permissionIds = $permissions->pluck('id')->toArray();
            $permissionNames = $permissions->pluck('name')->toArray();

            Log::info('ModuleLifecycleService: Found permissions to remove', [
                'count' => count($permissionIds),
                'permissions' => $permissionNames,
            ]);

            $rolePermissionsDeleted = DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            Log::info('ModuleLifecycleService: Removed permissions from roles', [
                'count' => $rolePermissionsDeleted,
            ]);

            $modelPermissionsDeleted = DB::table('model_has_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            Log::info('ModuleLifecycleService: Removed permissions from models', [
                'count' => $modelPermissionsDeleted,
            ]);

            $permissionsDeleted = Permission::whereIn('id', $permissionIds)->delete();

            Log::info('ModuleLifecycleService: Deleted permissions', [
                'count' => $permissionsDeleted,
            ]);

            app(PermissionCacheService::class)->clear();

            Log::info("ModuleLifecycleService: Permission cleanup completed for '{$moduleName}'");
        } catch (\Exception $e) {
            Log::error("ModuleLifecycleService: Failed to cleanup permissions for '{$moduleName}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
