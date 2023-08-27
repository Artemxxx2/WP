<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class UserFilter extends AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return array(
            'field_types' => [$this->_name],
            'default_settings' => [
                'enhanced_ui' => true,
                'num' => 200,
            ],
        );
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return [
            'enhanced_ui' => [
                '#type' => 'checkbox',
                '#title' => __('Enable enhanced user interface', 'directories'),
                '#default_value' => $settings['enhanced_ui'],
            ],
            'num' => [
                '#type' => 'number',
                '#title' => __('Max number of options', 'directories'),
                '#integer' => true,
                '#default_value' => $settings['num'],
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[enhanced_ui]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => false],
                    ],
                ],
            ],
        ];
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        $default_text = isset($settings['default_text']) ? $settings['default_text'] : __('— Select —', 'directories');
        if ($settings['enhanced_ui']) {
            return [
                '#type' => 'user',
                '#multiple' => false,
                '#attributes' => ['placeholder' => $default_text],
            ];
        }

        return array(
            '#type' => 'select',
            '#empty_value' => 0,
            '#multiple' => false,
            '#options' => [0 => $default_text] + $this->_getUserList($settings['num']),
            '#entity_filter_form_type' => 'select',
        );
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value) && $this->_application->UserIdentity($value);
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $query->fieldIs($field, $value);
    }

    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        return ['' => $this->_application->H($defaultLabel . ': ' . $this->_application->UserIdentity($value)->username)];
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