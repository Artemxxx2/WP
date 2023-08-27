<?php
namespace SabaiApps\Directories\Component\Claiming\DisplayLabel;

use SabaiApps\Directories\Component\Display\Label\AbstractLabel;
use SabaiApps\Directories\Component\Entity;

class StatusDisplayLabel extends AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Claim status label', 'directories-pro'),
            'default_settings' => array(
                '_icon' => '',
            ),
            'labellable' => false,
            'colorable' => false,
        );
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        $status = $this->_application->getComponent('Claiming')->getClaimStatus($entity);
        $statuses = $this->_application->Claiming_Statuses();
        if (!isset($statuses[$status])) {
            $label = __('Pending', 'directories-pro');
            $color = 'warning';
        } else {
            $label = $statuses[$status]['label'];
            $color = $statuses[$status]['color'];
        }
        return [
            'label' => $label,
            'color' => ['type' => $color],
        ];
    }
}
