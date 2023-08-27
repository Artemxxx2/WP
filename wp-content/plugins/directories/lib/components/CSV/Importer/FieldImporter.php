<?php
namespace SabaiApps\Directories\Component\CSV\Importer;

use SabaiApps\Directories\Component\Entity;

class FieldImporter extends AbstractImporter implements IWpAllImportImporter
{
    protected function _csvImporterInfo()
    {
        $info =[
            'field_types' => [substr($this->_name, 6)], // remove field_ part
        ];
        switch ($this->_name) {
            case 'field_time':
                $info['field_types'][] = 'directory_opening_hours';
                break;
            case 'field_name':
                $info['columns'] = [
                    'prefix' => _x('Prefix', 'name salutation', 'directories'),
                    'first_name' => __('First Name', 'directories'),
                    'middle_name' => __('Middle Name', 'directories'),
                    'last_name' => __('Last Name', 'directories'),
                    'suffix' => _x('Suffix', 'name suffix', 'directories'),
                ];
                $info['field_types'][] = 'user_name';
                break;
            default:
        }
        return $info;
    }
    
    public function csvImporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        if ($this->_name === 'field_boolean') return;
        
        $form = $reserved_separator = [];
        
        switch ($this->_name) {                
            case 'field_video':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Video provider/ID separator', 'directories'),
                        '#description' => __('Enter the character used to separate the video provider and ID.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            case 'field_price':
                $form += [
                    'separator' => [
                        '#type' => 'textfield',
                        '#title' => __('Price value/currency separator', 'directories'),
                        '#description' => __('Enter the character used to separate the price value and currency.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ],
                    'dec_point' => [
                        '#type' => 'textfield',
                        '#title' => __('Decimal point', 'directories'),
                        '#description' => __('Enter the character used as the decimal point.', 'directories'),
                        '#default_value' => '.',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#max_length' => 1,
                        '#required' => true,
                        '#weight' => 2,
                    ],
                ];
                $reserved_separator['separator'] = $form['separator']['#title'];
                $reserved_separator['dec_point'] = $form['dec_point']['#title'];
                break;
            case 'field_number':
                $form += [
                    'dec_point' => [
                        '#type' => 'textfield',
                        '#title' => __('Decimal point', 'directories'),
                        '#description' => __('Enter the character used as the decimal point.', 'directories'),
                        '#default_value' => '.',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#max_length' => 1,
                        '#required' => true,
                        '#weight' => 2,
                    ],
                ];
                $reserved_separator['dec_point'] = $form['dec_point']['#title'];
                break;
            case 'field_range':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Field min/max separator', 'directories'),
                        '#description' => __('Enter the character used to separate the minimum and maximum values.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            case 'field_date':
                $form += $this->_getDateFormatSettingsForm($parents);
                break;
            case 'field_time':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Start/End/Day separator', 'directories'),
                        '#description' => __('Enter the character used to separate the starting time, ending time, and day of week.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                    ),
                    'date_format' => array(
                        '#type' => 'select',
                        '#title' => __('Time format', 'directories'),
                        '#description' => __('Select the format used to represent time values in CSV.', 'directories'),
                        '#options' => array(
                            'string' => __('HH:MM', 'directories'),
                            'timestamp' => __('Timestamp', 'directories'),
                        ),
                        '#default_value' => 'string',
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            default:
        }
        
        if ($field->isCustomField()) {
            $form += $this->_acceptMultipleValues($field, $enclosure, $parents, $reserved_separator);
        }
        
        return $form;
    }

    public function csvImporterDoImport(Entity\Model\Field $field, array $settings, $column, $value, &$formStorage, array &$logs)
    {
        if ($this->_name === 'field_boolean') return array(array('value' => $value));
        
        if (!empty($settings['_multiple'])) {
            if (!$values = explode($settings['_separator'], $value)) {
                return;
            }
        } else {
            $values = array($value);
        }

        $ret = [];
        switch ($this->_name) {
            case 'field_video':
                foreach ($values as $value) {
                    if ($value = explode($settings['separator'], $value)) {
                        $ret[] = array(
                            'id' => $value[1],
                            'provider' => $value[0],
                        );
                    }
                }
                break;
            case 'field_price':
                foreach ($values as $value) {
                    if ($value = explode($settings['separator'], $value)) {
                        if (strlen($settings['dec_point'])
                            && $settings['dec_point'] !== '.'
                        ) {
                            $value[0] = strtr($value[0], [$settings['dec_point'] => '.']);
                        }
                        $ret[] = array(
                            'value' => $value[0],
                            'currency' => isset($value[1]) ? $value[1] : null,
                        );
                    }
                }
                break;
            case 'field_range':
                foreach ($values as $value) {
                    if ($value = explode($settings['separator'], $value)) {
                        $ret[] = array(
                            'min' => $value[0],
                            'max' => $value[1],
                        );
                    }
                }
                break;
            case 'field_name':
                foreach ($values as $value) {
                    $ret[] = [$column => $value];
                }
                break;
            case 'field_date':
                foreach ($values as $value) {
                    if (false !== $value = $this->_strToTime($value, $settings)) {
                        $ret[] = $value;
                    }
                }
                break;
            case 'field_time':
                if ($settings['date_format'] === 'string') {
                    foreach ($values as $value) {
                        if (!$value = $this->_getTimeRange($value, $settings['separator'])) continue;

                        $ret[] = $value;
                    }
                } else {
                    foreach ($values as $value) {
                        $value = explode($settings['separator'], $value);
                        if (!$value[0]) continue;
                
                        $ret[] = [
                            'start' => $value[0],
                            'end' => isset($value[1]) && strlen($value[1]) ? $value[1] : null,
                            'day' => isset($value[2]) ? (string)$value[2] : '',
                            'all_day' => isset($value[3]) ? (string)$value[3] : '',
                        ];
                    }
                }
                break;
            case 'field_number':
                if (strlen($settings['dec_point'])
                    && $settings['dec_point'] !== '.'
                ) {
                    foreach ($values as $value) {
                        $ret[] = ['value' => strtr($value, [$settings['dec_point'] => '.'])];
                    }
                } else {
                    foreach ($values as $value) {
                        $ret[] = ['value' => $value];
                    }
                }
                break;
            default:
                foreach ($values as $value) {
                    $ret[] = ['value' => $value];
                }
        }
        
        return $ret;
    }

    public function csvWpAllImportImporterAddField(\RapidAddon $addon, Entity\Model\Field $field)
    {
        switch ($this->_name) {
            case 'field_boolean':
                $addon->add_title($field->getFieldLabel());
                $settings = $field->getFieldSettings();
                $addon->add_field(
                    $field->getFieldName(),
                    '',
                    'radio',
                    [
                        1 => $settings['on_label'],
                        0 => $settings['off_label'],
                    ],
                    '',
                    true,
                    0
                );
                return true;
            case 'field_video':
                $addon->add_title($field->getFieldLabel());
                $options = [
                    $addon->add_field(
                        $field->getFieldName() . '-provider',
                        __('Video provider', 'directories'),
                        'radio',
                        [
                            'youtube' => __('YouTube', 'directories'),
                            'vimeo' => __('Vimeo', 'directories'),
                        ]
                    )
                ];
                if ($field->isCustomField()
                    && $field->getFieldMaxNumItems() !== 1
                ) {
                    $options[] = $addon->add_field(
                        $field->getFieldName() . '-separator',
                        __('Separator', 'directories'),
                        'text',
                        null,
                        '',
                        true,
                        ','
                    );
                }
                $addon->add_options(
                    $addon->add_field( $field->getFieldName(), __('Video ID', 'directories'), 'text', null, ''),
                    __('Field Options', 'directories'),
                    $options
                );
                return true;
            case 'field_price':
                $addon->add_title($field->getFieldLabel());
                $addon->add_field(
                    $field->getFieldName() . '-currency',
                    __('Currency', 'directories'),
                    'text',
                    null,
                    '',
                    true,
                    ''
                );
                $addon->add_field(
                    $field->getFieldName() . '-value',
                    __('Price', 'directories'),
                    'text',
                    null,
                    '',
                    true,
                    ''
                );
                return true;
            case 'field_range':
                $addon->add_title($field->getFieldLabel());
                $addon->add_field(
                    $field->getFieldName() . '-min',
                    __('Minimum value', 'directories'),
                    'text',
                    null,
                    '',
                    true,
                    ''
                );
                $addon->add_field(
                    $field->getFieldName() . '-max',
                    __('Maximum value', 'directories'),
                    'text',
                    null,
                    '',
                    true,
                    ''
                );
                return true;
            case 'field_time':
                $addon->add_title($field->getFieldLabel());
                $settings = $field->getFieldSettings();
                if (!empty($settings['enable_day'])) {
                    foreach ($this->_application->Days() as $key => $day) {
                        $addon->add_field( $field->getFieldName() . '-' . $key, $day, 'text', null, 'HH:MM - HH:MM');
                    }
                } else {
                    if ($field->isCustomField()
                        && $field->getFieldMaxNumItems() !== 1
                    ) {
                        $addon->add_options(
                            $addon->add_field( $field->getFieldName(), '', 'text', null, 'HH:MM - HH:MM'),
                            __('Field Options', 'directories'),
                            [
                                $addon->add_field(
                                    $field->getFieldName() . '-separator',
                                    __('Separator', 'directories'),
                                    'text',
                                    null,
                                    '',
                                    true,
                                    ','
                                )
                            ]
                        );
                    } else {
                        $addon->add_field( $field->getFieldName(), '', 'text', null, '');
                    }
                }
                return true;
            case 'field_string':
            case 'field_text':
            case 'field_url':
            case 'field_email':
            case 'field_phone':
            case 'field_number':
            case 'field_color':
            case 'field_icon':
            case 'field_choice':
            case 'field_date':
            default:
                $addon->add_title($field->getFieldLabel());
                if ($field->isCustomField()
                    && $field->getFieldMaxNumItems() !== 1
                ) {
                    $addon->add_options(
                        $addon->add_field( $field->getFieldName(), '', 'text', null, ''),
                        __('Field Options', 'directories'),
                        [
                            $addon->add_field(
                                $field->getFieldName() . '-separator',
                                __('Separator', 'directories'),
                                'text',
                                null,
                                '',
                                true,
                                ','
                            ),
                        ]
                    );
                } else {
                    $addon->add_field( $field->getFieldName(), '', 'text', null, '');
                }
                return true;
        }
    }

    public function csvWpAllImportImporterDoImport(\RapidAddon $addon, Entity\Model\Field $field, array $data, $options, array $article)
    {
        switch ($this->_name) {
            case 'field_boolean':
                if (!isset($data[$field->getFieldName()])) return;

                return [$data[$field->getFieldName()]];
            case 'field_video':
                if (!isset($data[$field->getFieldName()])) return;

                if (isset($data[$field->getFieldName() . '-separator'])
                    && ($separator = trim($data[$field->getFieldName() . '-separator']))
                ) {
                    if (!$values = explode($separator, $data[$field->getFieldName()])) {
                        return;
                    }
                    $values = array_map('trim', $values);
                } else {
                    $values = [trim($data[$field->getFieldName()])];
                }
                if (isset($data[$field->getFieldName() . '-provider']) && in_array($data[$field->getFieldName() . '-provider'], ['vimeo'])) {
                    $provider = $data[$field->getFieldName() . '-provider'];
                } else {
                    $provider = 'youtube';
                }
                foreach (array_keys($values) as $key) {
                    $values[$key] = [
                        'id' => $values[$key],
                        'provider' => $provider,
                    ];
                }
                return array_values($values);
            case 'field_price':
                if (!isset($data[$field->getFieldName() . '-currency'])
                    || !isset($data[$field->getFieldName() . '-value'])
                ) return;

                return [
                    'value' => $data[$field->getFieldName() . '-value'],
                    'currency' => $data[$field->getFieldName() . '-currency'],
                ];
            case 'field_range':
                if (!isset($data[$field->getFieldName() . '-min'])
                    || !isset($data[$field->getFieldName() . '-max'])
                    || $data[$field->getFieldName() . '-max'] < $data[$field->getFieldName() . '-min']
                ) return;

                return [
                    'min' => $data[$field->getFieldName() . '-min'],
                    'max' => $data[$field->getFieldName() . '-max'],
                ];
            case 'field_time':
                $values = [];
                $settings = $field->getFieldSettings();
                if (!empty($settings['enable_day'])) {
                    foreach ($this->_application->Days() as $key => $day) {
                        if (empty($data[$field->getFieldName() . '-' . $key])
                            || (!$value = $this->_getTimeRange($data[$field->getFieldName() . '-' . $key]))
                        ) continue;

                        $value['day'] = $key;
                        $values[] = $value;
                    }
                } else {
                    if (!isset($data[$field->getFieldName()])) return;

                    if (isset($data[$field->getFieldName() . '-separator'])
                        && ($separator = trim($data[$field->getFieldName() . '-separator']))
                    ) {
                        if (!$values = explode($separator, $data[$field->getFieldName()])) {
                            return;
                        }
                    } else {
                        $values = [$data[$field->getFieldName()]];
                    }
                    foreach (array_keys($values) as $key) {
                        if (!$values[$key] = $this->_getTimeRange($values[$key])) {
                            unset($values[$key]);
                        }
                    }
                    $values = array_values($values);
                }
                return $values;
            case 'field_string':
            case 'field_text':
            case 'field_url':
            case 'field_email':
            case 'field_phone':
            case 'field_number':
            case 'field_color':
            case 'field_icon':
            case 'field_choice':
            case 'field_date':
            default:
                if (!isset($data[$field->getFieldName()])) return;

                if (isset($data[$field->getFieldName() . '-separator'])
                    && ($separator = trim($data[$field->getFieldName() . '-separator']))
                ) {
                    if (!$values = explode($separator, $data[$field->getFieldName()])) {
                        return;
                    }
                } else {
                    $values = [$data[$field->getFieldName()]];
                }
                return array_map('trim', $values);
        }
    }

    protected function _getTimeRange($str, $separator = '-')
    {
        if (!$range = explode($separator, $str)) return;

        $range = array_map('trim', $range);
        if (!$range[0]
            || false === ($range[0] = $this->_application->Form_Validate_time($range[0]))
        ) return;

        $end = null;
        if (isset($range[1])
            && strlen($range[1])
            && false !== ($range[1] = $this->_application->Form_Validate_time($range[1]))
        ) {
            $end = mktime($range[1][0], $range[1][1], 0);
        }

        return [
            'start' => mktime($range[0][0], $range[0][1], 0),
            'end' => $end,
            'day' => isset($range[2]) ? $range[2] : null,
            'all_day' => isset($range[3]) ? $range[3] : null,
        ];
    }
}