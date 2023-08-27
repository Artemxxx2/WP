<?php
namespace SabaiApps\Directories\Component\Display\Button;

use SabaiApps\Directories\Component\Entity;

class BackButton extends AbstractButton
{
    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        return [
            'label' => __('Back button', 'directories'),
            'default_settings' => [
                '_label' => __('Back', 'directories'),
                '_color' => 'outline-secondary',
                '_icon' => 'fas fa-arrow-left',
            ],
            'overlayable' => false,
        ];
    }

    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        return $this->_application->LinkTo(
            $settings['_label'],
            '#',
            ['icon' => $settings['_icon']],
            ['class' => $settings['_class'], 'style' => $settings['_style'], 'onclick' => 'window.history.back();']
        );
    }
}