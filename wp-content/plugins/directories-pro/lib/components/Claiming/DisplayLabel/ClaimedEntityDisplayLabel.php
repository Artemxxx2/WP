<?php
namespace SabaiApps\Directories\Component\Claiming\DisplayLabel;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class ClaimedEntityDisplayLabel extends Display\Label\AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Claimed content label', 'directories-pro'),
            'default_settings' => array(
                '_label' => _x('Claimed', 'claimed label', 'directories-pro'),
                '_color' => ['type' => 'info'],
            ),
        );
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (!$entity->getAuthorId()) return;

        return [
            'label' => $settings['_label'],
            'color' => $settings['_color'],
        ];
    }
}
