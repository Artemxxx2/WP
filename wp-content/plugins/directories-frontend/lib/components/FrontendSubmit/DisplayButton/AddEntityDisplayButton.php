<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\DisplayButton;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class AddEntityDisplayButton extends Display\Button\AbstractButton
{
    protected $_bundleType;

    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        $this->_bundleType = substr($name, 19); // remove 'frontendsubmit_add_' prefix
    }

    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        if ($child_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group)) {
            $label = $child_bundle->getLabel('singular');
        } else {
            $this->_application->LogError('Failed fetching child bundle: ' . $this->_bundleType);
            $label = 'N/A';
        }
        return [
            'label' => sprintf(__('Add %s button', 'directories-frontend'), strtolower($label), $label),
            'default_settings' => [
                'modal' => false,
                '_color' => 'outline-primary',
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
                '#title' => __('Show form in modal window', 'directories-frontend'),
                '#default_value' => $settings['modal'],
                '#horizontal' => true,
            ],
        ];
    }

    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        if (!$this->_application->Filter('frontendsubmit_display_button_add_entity_link', true, [$this->_name, $entity])) return;

        if (!$child_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group)) return;

        if ($this->_application->Entity_IsRoutable($child_bundle, 'add', $entity)) {
            $show_in_modal = !empty($settings['modal'])
                && !$this->_application->getUser()->isAnonymous(); // guests may need to be redirected, which does not work with modal
            if ($show_in_modal) {
                $this->_application->Entity_Form_loadAssets($child_bundle);
            }
            return $this->_application->LinkTo(
                $child_bundle->getLabel('add'),
                $this->_application->Entity_Url($entity, '/' . $child_bundle->info['slug'] . '/add'),
                [
                    'icon' => $this->_application->Entity_BundleTypeInfo($child_bundle, 'icon'),
                    'btn' => true,
                    'container' => $show_in_modal ? 'modal' : null,
                    'modalSize' => 'lg',
                ],
                ['class' => $settings['_class'], 'style' => $settings['_style']]
            );
        }

        if (!$this->_application->getUser()->isAnonymous()) return;

        return $this->_getLoginButton(
            $child_bundle->getLabel('add'),
            $this->_application->Entity_Url($entity, '/' . $child_bundle->info['slug'] . '/add'),
            ['no_escape' => true, 'icon' => $this->_application->Entity_BundleTypeInfo($child_bundle, 'icon'), 'btn' => true],
            ['class' => $settings['_class'], 'style' => $settings['_style']]
        );
    }
}
