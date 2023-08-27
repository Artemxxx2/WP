<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class UninstallHelper
{
    /**
     * @param Application $application
     */
    public function help(Application $application)
    {
        $remove_data = $application->Filter('core_uninstall_remove_data', false);
        $application->Action('core_uninstall', [$remove_data]);
        
        // Uninstall components
        if ($components = $application->getModel('Component', 'System')->fetch()) {
            foreach ($components as $component) {
                try {
                    $application->getComponent($component->name)->uninstall($remove_data);
                } catch (\Exception $e) {
                    $application->logWarning(sprintf('Component %s could not be uninstalled. Error: %s', $component->name, $e->getMessage()));
                }
            }
        }

        // Clear uploaded files
        if ($remove_data
            && ($dir = $application->getPlatform()->getVarDir())
            && is_dir($dir)
        ) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $path) {
                $path->isDir() && !$path->isLink() ? @rmdir($path->getPathname()) : @unlink($path->getPathname());
            }
            @rmdir($dir);
        }

        // Uninstall platform specific data
        $application->getPlatform()->uninstall($remove_data);
    }
}
