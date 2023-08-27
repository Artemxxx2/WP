<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form\Field\TimePickerField;

class TimeType extends AbstractType implements
    ISortable,
    ISchemable,
    IQueryable,
    IOpenGraph,
    IHumanReadable,
    ICopiable,
    IConditionable
{
    use ConditionableDefaultTrait;

    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Time', 'directories'),
            'default_settings' => array(
                'enable_day' => false,
                'enable_end' => false,
            ),
            'icon' => 'far fa-clock',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'enable_day' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable day of week', 'directories'),
                '#default_value' => !empty($settings['enable_day']),
            ),
            'enable_end' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable end time', 'directories'),
                '#default_value' => !empty($settings['enable_end']),
            ),
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'start' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'length' => 8,
                    'was' => 'start',
                    'default' => '0',
                ),
                'end' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'length' => 8,
                    'was' => 'end',
                    'default' => '0',
                ),
                'day' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'length' => 2,
                    'was' => 'day',
                    'default' => '0',
                ),
                'all_day' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'default' => '',
                    'length' => 20,
                ),
            ),
            'indexes' => array(
                'start' => array(
                    'fields' => array('start' => array('sorting' => 'ascending')),
                ),
                'end' => array(
                    'fields' => array('end' => array('sorting' => 'ascending')),
                ),
                'day' => array(
                    'fields' => array('day' => array('sorting' => 'ascending')),
                ),
                'all_day' => array(
                    'fields' => array('all_day' => array('sorting' => 'ascending')),
                ),
            ),
        );
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach (array_keys($values) as $weight) {
            if (!$value = $this->_getValue($values[$weight])) continue;

            if (isset($value['day'])
                && ($value['day'] = (int)$value['day'])
            ) {
                if (in_array($value['day'], [TimePickerField::WEEKDAYS, TimePickerField::WEEKEND, TimePickerField::ALL_WEEK])) {
                    switch ($value['day']) {
                        case TimePickerField::WEEKDAYS:
                            $days = range(1,5);
                            break;
                        case TimePickerField::WEEKEND:
                            $days = [6, 7];
                            break;
                        case TimePickerField::ALL_WEEK:
                        default:
                            $days = range(1, 7);
                            break;
                    }
                    foreach ($days as $day) {
                        $value['day'] = $day;
                        $ret[] = $value;
                    }
                } else {
                    if ($value['day'] > 7 && $value['day'] % 7) {
                        $value['day'] = $value['day'] % 7;
                    }
                    $ret[] = $value;
                }
            } else {
                $value['day'] = 0;
                $ret[] = $value;
            }
        }
        foreach (array_keys($ret) as $weight) {
            $value =& $ret[$weight];
            $value['start'] = intval($value['start']) % 86400;
            if ($value['start'] < 0) $value['start'] += 86400;
            if (isset($value['end'])) {
                if (86400 !== $value['end'] = intval($value['end'])) {
                    $value['end'] = $value['end'] % 86400;
                    if ($value['end'] < $value['start']) {
                        $value['end'] += 86400;
                    }
                }
            } else {
                $value['end'] = $value['start'];
            }
        }
        return $ret;
    }

    protected function _getValue($value)
    {
        if (is_array($value)) {
            $value += ['start' => 0, 'end' => null, 'day' => null, 'all_day' => ''];
        } else {
            if (!is_numeric($value)) return;

            $value = ['start' => $value, 'end' => null, 'day' => null, 'all_day' => ''];
        }

        return $value;
    }

    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {
        if (count($currentLoadedValue) !== count($valueToSave)) return true;

        foreach (array_keys($currentLoadedValue) as $key) {
            if (count($currentLoadedValue[$key]) !== count($valueToSave[$key])
                || array_diff_assoc($currentLoadedValue[$key], $valueToSave[$key])
            ) return true;
        }
        return false;
    }

    public function fieldSortableOptions(IField $field)
    {
        return array(
            [],
            array('args' => array('desc'), 'label' => __('%s (desc)', 'directories')),
        );
    }

    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC', 'start');
    }

    public function fieldSchemaProperties()
    {
        return array('openingHoursSpecification');
    }

    public function fieldSchemaRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return;

        $days = $this->_application->Days();
        $_values = [];
        foreach ($values as $value) {
            if (!$value['day']
                || !isset($days[$value['day']])
                || (!empty($value['all_day']) && $value['all_day'] !== '1')
            ) continue;

            $_values[$value['start']][$value['end']][] = $days[$value['day']];
        }
        if (empty($_values)) return;

        $ret = [];
        foreach ($_values as $start => $__values) {
            foreach ($__values as $end => $_days) {
                $ret[] = array(
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $_days,
                    'opens' => date('H:i', $start),
                    'closes' => date('H:i', $end),
                );
            }
        }
        return $ret;
    }

    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        return array(
            'example' => '8:30,17:30,7',
            'tip' => __('Enter a single numeric value to query by day of week (1 = Mon, 7 = Sun), two time values for time range query, and three values for day and time range query, e.g. "1:00,24:00,2" for Tuesday 1:00 - 24:00.', 'directories'),
        );
    }

    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        $params = $this->_queryableParams($paramStr, false);
        switch (count($params)) {
            case 0:
                break;
            case 1:
                if (strlen($params[0])) {
                    $query->fieldIs($fieldName, $params[0], 'day');
                }
                break;
            default:
                if (strlen($params[0])
                    && false !== ($params[0] = $this->_application->Form_Validate_time($params[0], true))
                ) {
                    $start = $params[0];
                    $query->fieldIsOrSmallerThan($fieldName, $params[0], 'start');
                }
                if (strlen($params[1])
                    && false !== ($params[1] = $this->_application->Form_Validate_time($params[1], true))
                ) {
                    if (isset($start)) {
                        if ($params[1] < $start) $params[1] += 86400;
                        $query->fieldIsOrGreaterThan($fieldName, $params[1], 'end');
                    } else {
                        $query->startCriteriaGroup('OR')
                            ->startCriteriaGroup()
                                ->fieldIsOrSmallerThan($fieldName, $params[1], 'start')
                                ->fieldIsOrGreaterThan($fieldName, $params[1], 'end')
                            ->finishCriteriaGroup()
                            ->startCriteriaGroup()
                                ->fieldIsGreaterThan($fieldName, $params[1], 'start')
                                ->fieldIsOrGreaterThan($fieldName, $params[1] + 86400, 'end')
                            ->finishCriteriaGroup()
                            ->finishCriteriaGroup();
                    }
                }
                if (strlen($params[2])) {
                    $query->fieldIs($fieldName, $params[2], 'day');
                }
                break;
        }
    }

    public function fieldOpenGraphProperties()
    {
        return array('business:hours');
    }

    public function fieldOpenGraphRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return;

        $days = array(
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday',
        );
        $ret = [];
        foreach ($values as $value) {
            if (!$value['day']
                || !isset($days[$value['day']])
                || (!empty($value['all_day']) && $value['all_day'] !== '1')
            ) continue;

            $ret[] = array(
                'business:hours:day' => $days[$value['day']],
                'business:hours:start' => date('H:i', $value['start']),
                'business:hours:end' => date('H:i', $value['end']),
            );
        }

        return $ret;
    }

    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';

        $ret = [];
        foreach ($values as $value) {
            $_ret = [];
            if (!empty($value['day'])) {
                $_ret[] = $this->_application->Days($value['day']);
            }
            $_ret[] = $this->_application->System_Date_time($value['start']);
            if (!empty($value['end'])) {
                $_ret[] = '-';
                $_ret[] = $this->_application->System_Date_time($value['end']);
            }
            $ret[] = implode(' ', $_ret);
        }
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }

    public function fieldCopyValues(IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }
}
