<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class UserWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Select list', 'directories'),
            'field_types' => array('user'),
            'accept_multiple' => true,
            'default_settings' => array(
                'enhanced_ui' => true,
                'current_user_selected' => false,
                'num' => 200,
            ),
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'current_user_selected' => array(
                '#type' => 'checkbox',
                '#title' => __('Set current user selected by default', 'directories'),
                '#default_value' => $settings['current_user_selected'],
            ),
            'enhanced_ui' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable enhanced user interface', 'directories'),
                '#default_value' => $settings['enhanced_ui'],
            ),
            'num' => array(
                '#type' => 'number',
                '#title' => __('Max number of options', 'directories'),
                '#integer' => true,
                '#default_value' => $settings['num'],
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[enhanced_ui]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => false],
                    ),
                ),
            ),
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (isset($value)) {
            $default_value = [];
            foreach ($value as $_value) {
                $default_value[] = $_value->id;
            }
        } else {
            $default_value = null;
        }
        $default_text = isset($settings['default_text']) ? $settings['default_text'] : __('— Select —', 'directories');
        if ($settings['enhanced_ui']) {
            return array(
                '#type' => 'user',
                '#default_value' => $this->_getDefaultValue($value, $settings),
                '#multiple' => $field->getFieldMaxNumItems() !== 1,
                '#attributes' => array('placeholder' => $default_text),
            );
        }
        if (isset($value)) {
            $default_value = [];
            foreach ($value as $_value) {
                $default_value[] = $_value->id;
            }
        }
        if (!empty($default_value)) {
            if ($field->getFieldMaxNumItems() === 1) {
                $default_value = array_shift($default_value);
            }
        } else {
            if ($settings['current_user_selected']) {
                $default_value = $this->_application->getUser()->id;
            } else {
                $default_value = null; 
            }
        }
        return array(
            '#type' => 'select',
            '#empty_value' => 0,
            '#max_selection' => $field->getFieldMaxNumItems(),
            '#default_value' => $default_value,
            '#multiple' => $field->getFieldMaxNumItems() !== 1,
            '#options' => array(0 => $default_text) + $this->_getUserList($settings['num']),
        );
    }
	
    protected function _getDefaultValue($value, array $settings)
    {
        if (isset($value)) {
            $default_value = [];
            foreach ($value as $entity) {
                if (!is_object($entity)) continue;

                $default_value[$entity->id] = $entity->id;
            }
        } else {
            $default_value = null;
        }
        if (empty($default_value)
            && $settings['current_user_selected']
            && !$this->_application->getUser()->isAnonymous()
        ) {
            $default_value = $this->_application->getUser()->id;
        }
        return $default_value;
    }

    protected function _getUserList($limit = 200)
    {
        $ret = [];
        $identities = $this->_application
            ->getPlatform()
            ->getUserIdentityFetcher()
            ->fetch($limit, 0, 'name', 'ASC');
        foreach ($identities as $identity) {
            $ret[$identity->id] = $identity->name;
        }

        return $ret;
    }
}