<?php
namespace SabaiApps\Directories\Component\Entity\DisplayLabel;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class NewEntityDisplayLabel extends Display\Label\AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('New item label', 'directories'),
            'default_settings' => array(
                'days' => 1,
                '_label' => __('New', 'directories'),
                '_color' => ['type' => 'danger'],
            ),
        );
    }

    public function displayLabelSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return [
            'days' => [
                '#type' => 'slider',
                '#title' => __('Show if publish date is within last X days', 'directories'),
                '#default_value' => $settings['days'],
                '#min_value' => 1,
                '#max_value' => 100,
                '#horizontal' => true,
            ],
        ];
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        $days = (!$settings['days'] = (int)$settings['days']) ? 1 : $settings['days'];
        if ((!$ts = $entity->getTimestamp())
            || (time() - $ts >= $days * 86400)
        ) return;

        return [
            'label' => $settings['_label'],
            'color' => $settings['_color'],
        ];
    }
}
