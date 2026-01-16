<?php

namespace App\Services\Modules;

use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

/**
 * Provides a server-side manifest of available module pages.
 *
 * Enables the frontend to detect pages from modules installed after build,
 * since Vite's import.meta.glob only captures build-time pages.
 */
class ModulePageManifestService
{
    /**
     * Get page identifiers for all enabled modules.
     *
     * @return array<string> e.g., ['Core/Dashboard', 'Blog/Posts/Index']
     */
    public function getAvailablePages(): array
    {
        $pages = [];

        foreach (Module::allEnabled() as $module) {
            $moduleName = $module->getName();
            $pagesPath = $module->getPath().'/resources/assets/js/Pages';

            if (! File::isDirectory($pagesPath)) {
                continue;
            }

            $modulePages = $this->scanPagesDirectory($pagesPath, $moduleName);
            $pages = array_merge($pages, $modulePages);
        }

        return $pages;
    }

    /**
     * Recursively scan a pages directory for .jsx files.
     *
     * @param  string  $directory  The directory to scan
     * @param  string  $moduleName  The module name for prefixing
     * @param  string  $prefix  Current path prefix for recursion
     * @return array<string>
     */
    private function scanPagesDirectory(string $directory, string $moduleName, string $prefix = ''): array
    {
        $pages = [];
        $files = File::files($directory);
        $directories = File::directories($directory);

        foreach ($files as $file) {
            if ($file->getExtension() === 'jsx') {
                $pageName = $file->getFilenameWithoutExtension();
                $fullPath = $prefix ? "{$prefix}/{$pageName}" : $pageName;
                $pages[] = "{$moduleName}/{$fullPath}";
            }
        }

        foreach ($directories as $subDirectory) {
            $dirName = basename($subDirectory);
            $newPrefix = $prefix ? "{$prefix}/{$dirName}" : $dirName;
            $subPages = $this->scanPagesDirectory($subDirectory, $moduleName, $newPrefix);
            $pages = array_merge($pages, $subPages);
        }

        return $pages;
    }

    /**
     * Check if a specific module page exists.
     *
     * @param  string  $pageIdentifier  The page identifier (e.g., 'Core/Dashboard')
     * @return bool
     */
    public function pageExists(string $pageIdentifier): bool
    {
        $parts = explode('/', $pageIdentifier, 2);

        if (count($parts) < 2) {
            return false;
        }

        [$moduleName, $pagePath] = $parts;

        $module = Module::find($moduleName);

        if (! $module || ! Module::isEnabled($moduleName)) {
            return false;
        }

        $fullPath = $module->getPath()."/resources/assets/js/Pages/{$pagePath}.jsx";

        return File::exists($fullPath);
    }
}
