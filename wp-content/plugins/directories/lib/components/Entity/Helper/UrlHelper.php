<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class UrlHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, $path = '', array $params = [], $fragment = '', $separator = '&amp;')
    {
        if (!$bundle = $application->Entity_Bundle($entity)) return '';
        
        if (!empty($bundle->info['parent'])) { // child entity bundles do not have custom permalinks
            $permalink_path = ($parent = $application->Entity_ParentEntity($entity, false))
                ? str_replace(':slug', $parent->getSlug(), $bundle->getPath(true)) . '/' . $entity->getId()
                : '';
        } else {
            $permalink_path = $bundle->getPath(true) . '/' . $entity->getSlug();
        }
        
        return $application->Url(array(
            'route' => $permalink_path . '/' . ltrim($path, '/'),
            'params' => $params,
            'fragment' => $fragment,
            'script' => 'main',
            'separator' => $separator,
        ));
    }
}