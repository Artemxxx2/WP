<?php
namespace SabaiApps\Directories\Component\Claiming\DisplayButton;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class ClaimEntityDisplayButton extends Display\Button\AbstractButton
{
    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        return [
            'label' => __('Claim listing button', 'directories-pro'),
            'default_settings' => [
                '_color' => 'outline-warning',
            ],
            'labellable' => false,
            'iconable' => false,
        ];
    }

    public function displayButtonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return [
            'modal' => [
                '#type' => 'checkbox',
                '#title' => __('Show form in modal window', 'directories-pro'),
                '#default_value' => $settings['modal'],
                '#horizontal' => true,
            ],
        ];
    }

    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        if (!$claim_bundle = $this->_application->Entity_Bundle('claiming_claim', $bundle->component, $bundle->group)) return;

        if ($this->_application->Entity_IsRoutable($claim_bundle, 'add', $entity)) {
            if (!empty($settings['modal'])) {
                $this->_application->Entity_Form_loadAssets($claim_bundle);
            }
            return $this->_application->LinkTo(
                $claim_bundle->getLabel('add'),
                $this->_application->Entity_Url($entity, '/' . $claim_bundle->info['slug'] . '_add'),
                [
                    'icon' => $this->_application->Entity_BundleTypeInfo($claim_bundle, 'icon'),
                    'btn' => true,
                    'container' => empty($settings['modal']) ? null : 'modal',
                    'modalSize' => 'lg',
                ],
                ['class' => $settings['_class'], 'style' => $settings['_style']]
            );
        }

        if ($this->_application->getUser()->isAnonymous()) {
            // We can't use Entity_IsRoutable helper here since it will always return false if guest

            // Allow other components to filter result
            $result = $this->_application->Filter(
                'claiming_is_entity_claimable',
                $entity->getAuthorId() ? false : true,
                [$entity]
            );

            if (!$result) return;

            return $this->_getLoginButton(
                $claim_bundle->getLabel('add'),
                $this->_application->Entity_Url($entity, '/' . $claim_bundle->info['slug'] . '_add'),
                ['no_escape' => true, 'icon' => $this->_application->Entity_BundleTypeInfo($claim_bundle, 'icon'), 'btn' => true],
                ['class' => $settings['_class'], 'style' => $settings['_style']]
            );
        }
    }
}
