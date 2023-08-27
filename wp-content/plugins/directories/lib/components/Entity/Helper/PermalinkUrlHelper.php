<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class PermalinkUrlHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, $fragment = '', $lang = null, array $params = [])
    {
        if (!$url = $application->Filter('entity_permalink_url', null, [$entity, $fragment, $lang, $params])) {
            $url = $application->Url([
                'route' => $application->Entity_Path($entity, $lang),
                'params' => $params,
                'fragment' => $fragment,
                'script' => 'main',
            ]);
        }
        return $url;
    }
}