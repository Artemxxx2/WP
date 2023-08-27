<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception\RuntimeException;
use SabaiApps\Directories\Component\Entity;

class AddEntitySlugHelper
{
    public function help(Application $application, $bundle, $lang = null)
    {
        if (!is_string($bundle)) {
            if (!$bundle instanceof Entity\Model\Bundle) {
                throw new RuntimeException('Invalid bundle parameter.');
            }
            if (!empty($bundle->info['parent'])) {
                $path = trim($bundle->getPath(true, $lang), '/');
                return empty($bundle->info['public']) ? $path . '_add' : $path . '/add';
            }

            $bundle = $bundle->type;
        }

        return $application->getComponent('FrontendSubmit')
            ->getSlug($this->name($application, $bundle), $lang);
    }
    
    public function name(Application $application, $bundleType)
    {
        return 'add-' . str_replace(array('_', '--'), array('-', '-'), $bundleType);
    }
    
    public function title(Application $application, $bundleType, $lang = null)
    {
        return $application->getComponent('FrontendSubmit')
            ->getTitle($this->name($application, $bundleType), $lang);
    }
}