<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Component\Entity\FieldType\ITitleFieldType;

class NameType extends AbstractType implements ISortable, ITitle, ITitleFieldType, IHumanReadable
{
    public $_tags = ['prefix', 'first_name', 'middle_name', 'last_name', 'suffix'];

    protected function _fieldTypeInfo()
    {
        return [
            'label' => _x('Name', 'person name', 'directories'),
            'default_settings' => [
                'prefix_field' => false,
                'prefixes' => ['mr', 'mrs', 'ms'],
                'first_name_field' => true,
                'middle_name_field' => false,
                'last_name_field' => true,
                'suffix_field' => false,
                'display_format' => '{first_name} {last_name}'
            ],
            'icon' => 'fas fa-user',
        ];
    }

    public function fieldTypeSchema()
    {
        return [
            'columns' => [
                'prefix' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 20,
                    'notnull' => true,
                    'default' => '',
                ],
                'first_name' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 150,
                    'notnull' => true,
                    'default' => '',
                ],
                'middle_name' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 150,
                    'notnull' => true,
                    'default' => '',
                ],
                'last_name' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 150,
                    'notnull' => true,
                    'default' => '',
                ],
                'suffix' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 150,
                    'notnull' => true,
                    'default' => '',
                ],
                'display_name' => [
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 150,
                    'notnull' => true,
                    'default' => '',
                ],
            ],
            'indexes' => [
                'first_name' => [
                    'fields' => ['first_name' => ['sorting' => 'ascending']],
                ],
                'middle_name' => [
                    'fields' => ['middle_name' => ['sorting' => 'ascending']],
                ],
                'last_name' => [
                    'fields' => ['last_name' => ['sorting' => 'ascending']],
                ],
                'display_name' => [
                    'fields' => ['display_name' => ['sorting' => 'ascending']],
                ],
            ],
        ];
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            '#element_validate' => [function(Form $form, &$value, $element) {
                 if (empty($value['first_name_field'])
                    && empty($value['last_name_field'])
                    && empty($value['middle_name_field'])
                 ) {
                     $form->setError(__('At least one name field needs to be enabled.', 'directories'), $element);
                 }
            }],
            'prefix_field' => [
                '#title' => __('Enable name prefix field', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['prefix_field']),
            ],
            'prefixes' => [
                '#type' => 'sortablecheckboxes',
                '#options' => $this->getNamePrefixOptions(),
                '#default_value' => $settings['prefixes'],
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['prefix_field']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'first_name_field' => [
                '#title' => __('Enable first name field', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['first_name_field']),
            ],
            'middle_name_field' => [
                '#title' => __('Enable middle name field', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['middle_name_field']),
            ],
            'last_name_field' => [
                '#title' => __('Enable last name field', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['last_name_field']),
            ],
            'suffix_field' => [
                '#title' => __('Enable name suffix field', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['suffix_field']),
            ],
            'display_format' => [
                '#title' => __('Display format', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['display_format'],
                '#description' => $this->_application->System_Util_availableTags($this->_tags, '{', '}'),
                '#description_no_escape' => true,
            ],
        ];
    }

    public function getNamePrefixOptions()
    {
        return $this->_application->Filter('field_type_name_prefixes', [
            'mr' => __('Mr.', 'directories'),
            'mrs' => __('Mrs.', 'directories'),
            'miss' => __('Miss', 'directories'),
            'ms' => __('Ms.', 'directories'),
            'dr' => __('Dr.', 'directories'),
            'prof' => __('Prof.', 'directories'),
        ]);
    }

    public function getTags()
    {
        return $this->_tags;
    }

    public function formatName($value, array $settings)
    {
        if (empty($settings['name_custom_format'])
            || !isset($settings['name_format'])
            || (!$format = trim($settings['name_format']))
            || ('' === $formatted = $this->_formatName($value, $format, true))
        ) return $value['display_name'];

        return $formatted;
    }

    protected function _formatName($value, $format, $addDisplayName = false)
    {
        $tags = $this->_tags;
        if ($addDisplayName) {
            $tags[] = 'display_name';
        }
        $replace = [];
        foreach ($tags as $key) {
            $tag = '{' . $key . '}';
            if (!isset($value[$key])) {
                $replace[$tag] = '';
                continue;
            }
            if ($key === 'prefix') {
                if (false === strpos($format, $tag)
                    || (!$prefixes = $this->getNamePrefixOptions())
                    || !isset($prefixes[$value[$key]])
                ) {
                    $replace[$tag] = '';
                } else {
                    $replace[$tag] = $prefixes[$value[$key]];
                }
            } else {
                $replace[$tag] = (string)$value[$key];
            }
        }
        // Replace tags
        $formatted = trim(strtr($format, $replace));
        // Replace multiple columns with single column
        $formatted = preg_replace('/,+/', ',', $formatted);
        // Replace columns with spaces in between with a single column
        $formatted = preg_replace('/,\s*,/', ',', $formatted);
        // Replace multiple spaces with single space
        $formatted = preg_replace('/\s+/', ' ', $formatted);
        // Remove starting/trailing spaces/commas
        $formatted = trim($formatted, ' ,');

        return $formatted;
    }

    public function formatNameSettingsForm(array $settings, array $parents = [])
    {
        return [
            'name_custom_format' => [
                '#type' => 'checkbox',
                '#title' => __('Customize display format', 'directories'),
                '#default_value' => !empty($settings['name_custom_format']),
            ],
            'name_format' => [
                '#type' => 'textfield',
                '#title' => __('Display format', 'directories'),
                '#description' => $this->_application->System_Util_availableTags(array_merge(['display_name'], $this->_tags), '{', '}'),
                '#description_no_escape' => true,
                '#default_value' => isset($settings['name_format']) ? $settings['name_format'] : '{first_name} {middle_name} {last_name}',
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s[name_custom_format]"]', $this->_application->Form_FieldName($parents)) => [
                            'type' => 'checked',
                            'value' => true
                        ],
                    ],
                ],
                '#required' => function ($form) use ($parents) {
                    $val = $form->getValue(array_merge($parents, ['name_custom_format']));
                    return !empty($val);
                },
            ],
        ];
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        $settings = $field->getFieldSettings();
        foreach (array_keys($values) as $key) {
            if (!is_array($values[$key])) {
                if (!is_string($values[$key])) continue;

                $values[$key] = [
                    'first_name' => $values[$key],
                    'display_name' => $values[$key],
                ];
            } else {
                if (!isset($values[$key]['display_name'])
                    || !strlen($values[$key]['display_name'])
                ) {
                    $values[$key]['display_name'] = $this->_formatName($values[$key], $settings['display_format']);
                }
            }
            $ret[] = $values[$key];
        }
        return $ret;
    }

    public function fieldSortableOptions(IField $field)
    {
        $options = [];
        $options[] = ['args' => ['display_name'], 'label' => __('%s (A-Z)', 'directories')];
        $options[] = ['args' => ['display_name,desc'], 'label' => __('%s (Z-A)', 'directories')];
        $settings = $field->getFieldSettings();
        foreach ([
            'first_name' => __('First name', 'directories'),
            'middle_name' => __('Middle name', 'directories'),
            'last_name' => __('Last name', 'directories'),
        ] as $name_field => $name_field_label) {
            if (!empty($settings[$name_field . '_field'])) {
                $options[] = ['args' => [$name_field], 'label' => __('%s (A-Z)', 'directories'), 'sub_label' => $name_field_label];
                $options[] = ['args' => [$name_field, 'desc'], 'label' => __('%s (Z-A)', 'directories'), 'sub_label' => $name_field_label];
            }
        }
        return $options;
    }

    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField(
            $fieldName,
            isset($args[1]) && $args[1] === 'desc' ? 'DESC' : 'ASC',
            isset($args[0]) ? $args[0] : 'first_name'
        );
    }

    public function fieldTitle(IField $field, array $values)
    {
        return isset($values[0]['display_name']) ? $values[0]['display_name'] : null;
    }

    public function entityFieldTypeGetTitle(IField $field, IEntity $entity)
    {
        if ((!$value = $entity->getSingleFieldValue($field->getFieldName()))
            || !isset($value['display_name'])
        ) return;

        return $value['display_name'];
    }

    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $this->_getFormattedValues($field, $entity)) return '';

        return implode(isset($separator) ? $separator : ', ', $values);
    }

    protected function _getFormattedValues(IField $field, IEntity $entity, array $values = null)
    {
        if (!isset($values)
            && (!$values = $entity->getFieldValue($field->getFieldName()))
        ) return;

        foreach (array_keys($values) as $i) {
            $values[$i] = $values[$i]['display_name'];
        }

        return $values;
    }
}
