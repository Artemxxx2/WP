<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class FieldGenerator extends AbstractGenerator
{
    protected $_colors;
    
    protected function _fakerGeneratorInfo()
    {
        $info = array(
            'field_types' => array(substr($this->_name, 6)), // remove field_ part
        );
        switch ($this->_name) {
            case 'field_string':
                $info += array(
                    'default_settings' => array(
                        'length' => array('min' => 30, 'max' => 80),
                        'max' => 5,
                    ),
                );
                break;
            case 'field_text':
                $info += array(
                    'default_settings' => array(
                        'length' => array('min' => 300, 'max' => 1000),
                        'max' => 5,
                    ),
                );
                break;
            case 'field_number':
            case 'field_range':
                $info += array(
                    'default_settings' => array(
                        'range' => array('min' => 0, 'max' => 100),
                        'max' => 5,
                    ),
                );
                break;
            case 'field_user':
                $info += array(
                    'default_settings' => array(
                        'type' => 'admin',
                        'num' => 10,
                        'users' => array($this->_application->getUser()->id),
                        'max' => 5,
                    ),
                );
                break;
            case 'field_video':
                $info += array(
                    'default_settings' => array(
                        'videos' => ['nHXVc_cQqyI', 'TN-pwblNxU4', 'b7WD-SpNX_I', 'CbdARMu6lCA', 'DX48mJjL7oU',
                            'u3APNJYMrLo', 'bMUxpTb_wWc', '1La4QzGeaaQ', 'PsrPTpg6mNo', 'nRt4Duf7GoI', 'xYYYT48Iv_c'],
                        'max' => 5,
                    ),
                );
                break;
            case 'field_email':
                $info += [
                    'default_settings' => [
                        'max' => 5,
                    ],
                ];
                $info['field_types'][] = 'user_email';
                break;
            case 'field_phone':
                $info += [
                    'default_settings' => [
                        'max' => 5,
                    ],
                ];
                break;
            case 'field_url':
                $info += [
                    'default_settings' => [
                        'max' => 5,
                    ],
                ];
                $info['field_types'][] = 'user_url';
                break;
            case 'field_name':
                $info += [
                    'default_settings' => [
                        'max' => 5,
                    ],
                ];
                $info['field_types'][] = 'user_name';
                break;
            case 'field_date':
                $info += array(
                    'default_settings' => array(
                        'range' => array(
                            'from' => time() - 86400 * 365 * 10, // 10 years ago
                            'to' => time(),
                        ),
                        'max' => 5,
                    ),
                );
                break;
            case 'field_time':
                $info += array(
                    'default_settings' => array(
                        'max' => 7,
                    ),
                );
                $info['field_types'][] = 'directory_opening_hours';
                break;
            case 'field_color':
                $info += array(
                    'default_settings' => array(
                        'max' => 4,
                    ),
                );
                break;
        }
        
        return $info;
    }
    
    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $ret = array(
            'probability' => $this->_getProbabilitySettingForm(isset($settings['probability']) ? $settings['probability'] : null),
        );
        switch ($this->_name) {
            case 'field_string':
            case 'field_text':
                $field_settings = $field->getFieldSettings();
                $min = empty($field_settings['min_length']) || $field_settings['min_length'] < 5 ? null : $field_settings['min_length'];
                $max = empty($field_settings['max_length']) || $field_settings['max_length'] < 5 ? null : $field_settings['max_length'];
                return $ret + array(
                    'length' => array(
                        '#type' => 'range',
                        '#title' => __('Text length range', 'directories-faker'),
                        '#integer' => true,
                        '#min_value' => isset($min) ? $min : min(array(5, $settings['length']['min'])),
                        '#max_value' => isset($max) ? $max : max(100, $settings['length']['max']),
                        '#default_value' => array(
                            'min' => isset($min) ? max(array($min, $settings['length']['min'])) : $settings['length']['min'],
                            'max' => isset($max) ? min(array($max, $settings['length']['max'])) : $settings['length']['max'],
                        ),
                    ),
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                );
            case 'field_name':
                return $ret + [
                    'gender' => [
                        '#type' => 'select',
                        '#title' => __('Gender', 'directories-faker'),
                        '#options' => [
                            '' => __('Any', 'directories-faker'),
                            'male' => __('Male', 'directories-faker'),
                            'female' => __('Female', 'directories-faker'),
                        ],
                        '#default_value' => '',
                    ],
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                ];
            case 'field_email':
            case 'field_phone':
            case 'field_url':
            case 'field_time':
            case 'field_color':
                return $ret + array(
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                );
            case 'field_number':
            case 'field_range':
                $field_settings = $field->getFieldSettings();
                $min = empty($field_settings['min']) ? null : $field_settings['min'];
                $max = empty($field_settings['max']) ? null : $field_settings['max'];
                return $ret + array(
                    'range' => array(
                        '#type' => 'range',
                        '#title' => __('Number range', 'directories-faker'),
                        '#integer' => true,
                        '#min_value' => isset($min) ? $min : min(array(0, $settings['range']['min'])),
                        '#max_value' => isset($max) ? $max : max(100, $settings['range']['max']),
                        '#default_value' => array(
                            'min' => isset($min) ? max(array($min, $settings['range']['min'])) : $settings['range']['min'],
                            'max' => isset($max) ? min(array($max, $settings['range']['max'])) : $settings['range']['max'],
                        ),
                    ),
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                );
            case 'field_video':
                return $ret + array(
                    'videos' => array(
                        '#type' => 'textfield',
                        '#title' => __('YouTube videos', 'directories-faker'),
                        '#separator' => ',',
                        '#default_value' => $settings['videos'],
                        '#description' => __('Enter YouTube video IDs separated with commas.'),
                    ),
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                );
            case 'field_user':
                return $ret + array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('— Select —', 'directories-faker'),
                        '#options' => array(
                            'admin' => __('Administrators', 'directories-faker'),
                            'users' => __('Specific users', 'directories-faker'),
                            'newest' => __('Newest users', 'directories-faker'),
                        ),
                        '#default_value' => $settings['type'],
                    ),
                    'users' => array(
                        '#type' => 'user',
                        '#multiple' => true,
                        '#default_value' => $settings['users'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'users'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'users'; },
                    ),
                    'num' => array(
                        '#type' => 'slider',
                        '#title' => __('Max number of users', 'directories-faker'),
                        '#min_value' => 1,
                        '#max_value' => 100,
                        '#default_value' => $settings['num'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'newest'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'newest'; },
                    ),
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                );
            case 'field_date':
                return $ret + array(
                    'range' => $this->_getTimestampSettingForm($settings['range']),
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                );
            case 'field_choice':
                $field_settings = $field->getFieldSettings();
                return $ret + [
                    'options' => [
                        '#title' => $field->getFieldLabel(),
                        '#type' => 'checkboxes',
                        '#columns' => 3,
                        '#options' => $field_settings['options']['options'],
                        '#default_value' => array_keys($field_settings['options']['options']),
                    ],
                ];
            default:
                return $ret;
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {       
        if (mt_rand(0, 100) > $settings['probability']) return;
        
        switch ($this->_name) {
            case 'field_string':
                $ret = [];
                $min_len = $settings['length']['min'];
                $max_len = $settings['length']['max'];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $ret[] = str_replace('.', '', $this->_getFaker()->text(mt_rand($min_len + 1, $max_len + 1))); // remove periods
                }
                return empty($ret) ? null : $ret;
            case 'field_text':
                $ret = [];
                $min_len = $settings['length']['min'];
                $max_len = $settings['length']['max'];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $ret[] = str_replace("\n", "\n\n", $this->_getFaker()->text(mt_rand($min_len, $max_len)));
                }
                return empty($ret) ? null : $ret;
            case 'field_boolean':
                return array((bool)mt_rand(0, 1));
            case 'field_number':
                $ret = [];
                $min = $settings['range']['min'];
                $max = $settings['range']['max'];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $ret[] = mt_rand($min, $max);
                }
                return empty($ret) ? null : $ret;
            case 'field_range':
                $ret = [];
                $min = $settings['range']['min'];
                $max = $settings['range']['max'];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $ret[] = array(
                        'min' => $_min = mt_rand($min, $max),
                        'max' => mt_rand($_min, $max),
                    );
                }
                return empty($ret) ? null : $ret;
            case 'field_name':
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                $field_settings = $field->getFieldSettings();
                if (!empty($field_settings['prefixes'])) {
                    $prefixes = $field_settings['prefixes'];
                }
                $gender = empty($settings['gender']) ? null : $settings['gender'];
                for ($i = 0; $i < $count; ++$i) {
                    $prefix = '';
                    if (isset($prefixes)) {
                        if (!empty($gender)) {
                            if ($title = $this->_getFaker()->title($gender)) {
                                $title = strtolower(strtr($title, ['.' => '']));
                                if (in_array($title, $prefixes)) {
                                    $prefix = $title;
                                }
                            }
                        } else {
                            $prefix = $prefixes[array_rand($prefixes)];
                        }
                    }
                    $ret[] = [
                        'prefix' =>  $prefix,
                        'first_name' => $this->_getFaker()->firstName($gender),
                        'last_name' => $this->_getFaker()->lastName(),
                        'suffix' => $this->_getFaker()->suffix(),
                    ];
                }
                return empty($ret) ? null : $ret;
            case 'field_email':
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $ret[] = $this->_getFaker()->email();
                }
                return empty($ret) ? null : $ret;
            case 'field_phone':
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $ret[] = $this->_getFaker()->phoneNumber();
                }
                return empty($ret) ? null : $ret;
            case 'field_url':
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $ret[] = $this->_getFaker()->url();
                }
                return empty($ret) ? null : $ret;
            case 'field_video':
                if (empty($settings['videos'])) return false;
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                $max_index = count($settings['videos']) - 1;
                for ($i = 0; $i < $count; ++$i) {
                    $id = $settings['videos'][mt_rand(0, $max_index)];
                    $ret[$id] = array(
                        'id' => $id,
                        'provider' => 'youtube',
                    );
                }
                return empty($ret) ? null : array_values($ret);
            case 'field_choice':
                if (($options_count = count($settings['options']))
                    && ($max = $this->_getMaxNumItems($field, $options_count))
                    && ($keys = (array)array_rand($settings['options'], $max))
                ) {
                    $ret = [];
                    foreach ($keys as $key) {
                        $ret[] = $settings['options'][$key];
                    }
                    return $ret;
                }
                return;
            case 'field_user':
                if (!isset($this->_users)) {
                    if ($settings['type'] === 'users') {
                        $this->_users = $settings['users'];
                    } elseif ($settings['type'] === 'admin') {
                        $this->_users = array_values($this->_application->getPlatform()->getAdministrators());
                    } else {
                        $this->_users = $this->_application
                            ->getPlatform()
                            ->getUserIdentityFetcher()
                            ->fetch($settings['num'], 0, 'timestamp', 'ASC');
                    }
                    $this->_userCount = count($this->_users);
                }
                if (empty($this->_userCount)) return null;
                
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $ret[] = $this->_users[mt_rand(0, $this->_userCount - 1)];
                }
                return $ret;
            case 'field_date':
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $ret[] = $this->_generateTimestamp($settings['range']);
                }
                return empty($ret) ? null : $ret;
            case 'field_time':
                $field_settings = $field->getFieldSettings();
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                if ($field->getFieldType() === 'directory_opening_hours') {
                    for ($i = 0; $i < $count; ++$i) {
                        $ret[] = array(
                            'start' => mt_rand(0, 43200),
                            'end' => mt_rand(43201, 86400),
                            'day' => mt_rand(1, 7),
                        );
                    }
                } else {
                    for ($i = 0; $i < $count; ++$i) {
                        $ret[] = array(
                            'start' => mt_rand(0, 86400),
                            'end' => !empty($field_settings['enable_end']) ? mt_rand(0, 86400) : null,
                            'day' => !empty($field_settings['enable_day'])  ? mt_rand(1, 7) : null,
                        );
                    }
                }
                return empty($ret) ? null : $ret;
            case 'field_color':
                $ret = [];
                if (!isset($this->_colors)) {
                    $this->_colors = call_user_func_array('array_merge', array_values(Field\Type\ColorType::colors()));
                    srand((float)microtime() * 10000000);
                }
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $value = $this->_colors[array_rand($this->_colors)];
                    $ret[] = [
                        'value' => '#' . $value,
                        'closest' => $value,
                    ];
                }
                return empty($ret) ? null : $ret;
        }
    }
}