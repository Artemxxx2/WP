<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class PageTitleHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, $withIcon = false)
    {
        $title = $application->Entity_Title($entity);
        $bundle = $application->Entity_Bundle($entity, null, '', true);
        if ($entity->getParentId()) {
            $title = sprintf(
                $bundle->getLabel('page'),
                $title,
                ($parent_entity = $application->Entity_ParentEntity($entity, false)) ? $application->Entity_Title($parent_entity) : ''
            );
        } elseif (empty($bundle->info['is_primary'])) {
            $title = sprintf($bundle->getLabel('page'), $title);
        }
        $title = $application->H($title);

        // Add icon?
        if ($withIcon
            && !empty($bundle->info['entity_icon'])
        ) {
            $style = $application->getPlatform()->isRtl() ? 'float:left;' : 'float:right;';
            if (!empty($bundle->info['entity_icon_is_image'])) {
                if ($icon_src = $application->Entity_Image($entity, 'icon_lg', $bundle->info['entity_icon'])) {
                    $title = '<img src="' . $icon_src . '" style="' . $style . '" />' . $title;
                }
            } else {
                if ($icon_class = $application->Entity_Icon($entity, false)) {
                    if ($color = $application->Entity_Color($entity)) {
                        $style .= 'background-color:' . $color . ';color:#fff;';
                    }
                    $title = '<i class="drts-' . $icon_class . ' fa-border" style="' . $style . '"></i>' . $title;
                }
            }
        }
        
        return $application->Filter('entity_page_title', $title, [$withIcon]);
    }
}
