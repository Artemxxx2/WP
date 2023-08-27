<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class EntityUrlHelper extends Entity\Helper\UrlHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, $path = '', array $params = [], $fragment = '', $separator = '&amp;')
    {
        if ($entity->getType() !== 'post'
            || $entity->getParentId()
        ) return parent::help($application, $entity, $path, $params, $fragment, $separator);
        
        if (strlen($path)) {
            $params['drts_action'] = trim($path, '/');
        }
        return $application->createUrl(array(
            'script_url' => get_permalink((int)$entity->getId()),
            'params' => $params,
            'fragment' => $fragment,
            'separator' => $separator,
        ));
    }
}