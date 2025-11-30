<?php

namespace App\Services\Modules;

use App\Exceptions\MissingDependencyException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Nwidart\Modules\Module;

class ModuleDependencyValidator
{
    /**
     * Cached module map for case-insensitive lookups
     * Maps lowercase module name => actual module name
     *
     * @var array<string, string>|null
     */
    private ?array $moduleMapCache = null;

    public function __construct(
        private ModuleStatusService $statusService
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
    public function validate(Module $module, bool $skipValidation = false): array
    {
        if ($skipValidation || config('modules.skip_dependency_validation', false)) {
            return $this->normalize($module->get('requires', []));
        }

        $startTime = microtime(true);
        $moduleName = $module->getName();
        $modulePath = $module->getPath();
        $appPath = $modulePath.'/app';

        $declaredDependencies = $module->get('requires', []);
        $normalizedDependencies = $this->normalize($declaredDependencies);

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

        $foundDependencies = $this->scanForDependencies($appPath, $moduleName, $module);
        $normalizedFoundDependencies = $this->normalize($foundDependencies);

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
     * Check if all required dependencies are installed (and optionally enabled)
     *
     * Validates that all modules listed in module.json "requires" array are:
     * - Installed in the database
     * - Optionally enabled (when $checkEnabled is true)
     *
     * Note: Existence validation is handled by validate() to avoid duplication.
     *
     * @param  Module  $module  The module to check dependencies for
     * @param  bool  $checkEnabled  If true, also verifies dependencies are enabled (for enable operation)
     * @param  bool  $skipValidation  If true, skip dependency validation (useful for CI/CD or trusted modules)
     *
     * @throws MissingDependencyException When a required dependency is not installed or not enabled
     */
    public function checkInstalled(Module $module, bool $checkEnabled = false, bool $skipValidation = false): void
    {
        // If skipping validation, don't check if dependencies are installed
        // This is used during auto-installation when we handle dependencies manually
        if ($skipValidation) {
            return;
        }

        $validatedDependencies = $this->validate($module, $skipValidation);

        if (empty($validatedDependencies)) {
            return;
        }

        $operation = $checkEnabled ? 'enable' : 'install';
        $moduleName = $module->getName();

        foreach ($validatedDependencies as $requiredModuleName) {
            if (! $this->statusService->isInstalled($requiredModuleName)) {
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
    public function normalize(array $dependencies): array
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
    private function scanForDependencies(string $directory, string $currentModuleName, Module $module): array
    {
        $moduleName = $module->getName();
        $moduleVersion = $module->get('version', '1.0.0');
        $fileHash = $this->calculateFilesHash($directory);
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

                    $fileDependencies = $this->scanPhpFile($content, $currentModuleName);
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
    private function calculateFilesHash(string $directory): string
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
    private function scanPhpFile(string $content, string $currentModuleName): array
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
}
