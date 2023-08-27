<?php
namespace SabaiApps\Directories\Component\Entity\DisplayLabel;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class FeaturedEntityDisplayLabel extends Display\Label\AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Featured item label', 'directories'),
            'default_settings' => array(
                '_label' => _x('Featured', 'featured label', 'directories'),
                '_color' => ['type' => 'warning'],
            ),
        );
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (!$entity->isFeatured()) return;

        return [
            'label' => $settings['_label'],
            'color' => isset($settings['_color']) ? $settings['_color'] : ['type' => 'warning'],
        ];
    }
}
