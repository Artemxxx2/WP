<?php
namespace SabaiApps\Directories\Component\Payment\FieldType;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field\IField;

class PlanFieldType extends Field\Type\AbstractType implements
    Field\Type\IQueryable,
    Field\Type\ISortable,
    Field\Type\IRestrictable,
    Field\Type\IHumanReadable,
    Field\Type\ICopiable,
    Field\Type\IColumnable,
    Field\Type\IConditionable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Payment Plan', 'directories-payments'),
            'creatable' => false,
            'default_renderer' => $this->_name,
            'default_settings' => [],
            'admin_only' => true,
            'entity_types' => array('post'),
            'icon' => 'far fa-money-bill-alt'
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'expires_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'expires_at',
                    'default' => 0,
                    'length' => 10,
                ),
                'deactivated_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'deactivated_at',
                    'default' => 0,
                    'length' => 10,
                ),
                'plan_id' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'plan_id',
                ),
                'addon_features' => array(
                    'type' => Application::COLUMN_TEXT,
                    'notnull' => true,
                    'was' => 'addon_features',
                ),
                'extra_data' => array(
                    'type' => Application::COLUMN_TEXT,
                    'notnull' => true,
                    'was' => 'addon_features',
                ),
            ),
            'indexes' => array(
                'expires_at' => array(
                    'fields' => array('expires_at' => array('sorting' => 'ascending')),
                    'was' => 'expires_at',
                ),
                'deactivated_at' => array(
                    'fields' => array('deactivated_at' => array('sorting' => 'ascending')),
                    'was' => 'deactivated_at',
                ),
                'plan_id' => array(
                    'fields' => array('plan_id' => array('sorting' => 'ascending')),
                    'was' => 'plan_id',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $value = array_shift($values); // single entry allowed for this field
        if (!is_array($value)) {
            if ($value === null) return; // do nothing

            return array(false); // delete
        }

        // Keep expiration date
        if (!isset($value['expires_at'])) {
            if (!empty($currentValues[0]['expires_at'])) {
                $value['expires_at'] = $currentValues[0]['expires_at'];
            } else {
                $value['expires_at'] = 0;
            }
        }

        // Keep deactivation date unless expiration date ahs been updated
        if (!isset($value['deactivated_at'])) {
            if (!empty($currentValues[0]['deactivated_at'])
                && $currentValues[0]['deactivated_at'] >= $value['expires_at']
            ) {
                $value['deactivated_at'] = $currentValues[0]['deactivated_at'];
            } else {
                $value['deactivated_at'] = 0;
            }
        }

        // Make sure plan id is set
        if (!isset($value['plan_id'])) {
            if (empty($currentValues[0]['plan_id'])) {
                return array(false); // delete
            }

            $value['plan_id'] = $currentValues[0]['plan_id'];
        } elseif (empty($value['plan_id'])) {
            return array(false); // delete
        } else {
            if (($default_lang = $this->_application->getPlatform()->getDefaultLanguage())
                && ($payment_component = $this->_application->getComponent('Payment')->getPaymentComponent())
            ) {
                $value['plan_id'] = $payment_component->paymentGetPlanId($value['plan_id'], $default_lang);
            }
        }

        // Add or keep addon features
        if (isset($value['addon_features']) && is_array($value['addon_features'])) {
            foreach (array_keys($value['addon_features']) as $feature_name) {
                if ($value['addon_features'][$feature_name] === false) {
                    unset($value['addon_features'][$feature_name], $currentValues[0]['addon_features'][$feature_name]);
                }
            }
            if (!empty($currentValues[0]['addon_features'])) {
                $value['addon_features'] += $currentValues[0]['addon_features'];
            }
        } else {
            $value['addon_features'] = isset($currentValues[0]['addon_features']) ?
                $currentValues[0]['addon_features'] :
                [];
        }
        $value['addon_features'] = empty($value['addon_features']) ? '' : serialize($value['addon_features']);

        if (!isset($value['extra_data'])) {
            if (!empty($currentValues[0]['extra_data'])) {
                $value['extra_data'] = $currentValues[0]['extra_data'];
            }
        }
        $value['extra_data'] = empty($value['extra_data']) ? '' : serialize($value['extra_data']);

        return array($value);
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity, array $allValues)
    {
        foreach (array_keys($values) as $key) {
            $values[$key]['addon_features'] = strlen($values[$key]['addon_features']) ? (array)@unserialize($values[$key]['addon_features']) : [];
            $values[$key]['extra_data'] = strlen($values[$key]['extra_data']) ? (array)@unserialize($values[$key]['extra_data']) : [];
        }
    }

    public function fieldTypeIsModified(Field\IField $field, $valueToSave, $currentLoadedValue)
    {
        if (count($currentLoadedValue) !== count($valueToSave)
            || empty($valueToSave[0]) // may be false if removing
        ) return true;

        $currentLoadedValue[0]['addon_features'] = empty($currentLoadedValue[0]['addon_features']) ? '' : serialize((array)$currentLoadedValue[0]['addon_features']);
        $currentLoadedValue[0]['extra_data'] = empty($currentLoadedValue[0]['extra_data']) ? '' : serialize((array)$currentLoadedValue[0]['extra_data']);

        return count($currentLoadedValue[0]) !== count($valueToSave[0])
            || array_diff_assoc($currentLoadedValue[0], $valueToSave[0]);
    }

    public function fieldQueryableInfo(Field\IField $field, $inAdmin = false)
    {
        return array(
            'example' => 'default',
            'tip' => __('Enter payment plan IDs separated with commas. Enter 1 only to query items with a payment plan, 0 only to query items without any payment plan, -1 only to query expired items, -2 only to query deactivated items, or -3 only to query expiring items.', 'directories-payments'),
        );
    }

    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle)
    {
        if ($paramStr == 1) {
            $query->fieldIsNotNull($fieldName, 'plan_id');
        } elseif ($paramStr == 0) {
            $query->fieldIsNull($fieldName, 'plan_id');
        } elseif ($paramStr == -1) {
            $query->fieldIsOrSmallerThan($fieldName, time(), 'expires_at')
                ->fieldIsGreaterThan($fieldName, 0, 'expires_at');
        } elseif ($paramStr == -2) {
            $query->fieldIsGreaterThan($fieldName, 0, 'deactivated_at');
        } elseif ($paramStr == -3) {
            $expiring_ts = time() + 86400 * $this->_application->getComponent('Payment')->getConfig('renewal', 'expiring_days');
            $query->fieldIsGreaterThan($fieldName, time(), 'expires_at')
                ->fieldIsOrSmallerThan($fieldName, $expiring_ts, 'expires_at');
        } else {
            if ($plan_ids = $this->_queryableParams($paramStr)) {
                $query->fieldIsIn($fieldName, $plan_ids, 'plan_id');
            }
        }
    }

    public function fieldSortableOptions(Field\IField $field)
    {
        return [
            ['label' => $label = __('Exp. Date', 'directories-payments')],
            ['args' => ['desc'], 'label' => sprintf(__('%s (desc)', 'directories-payments'), $label)],
        ];
    }

    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, 'EMPTY_LAST', 'expires_at'); // moves NULL or 0 to last in order
        $query->sortByField(
            $fieldName,
            isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC',
            'expires_at'
        );
    }

    public function fieldRestrictableOptions(Field\IField $field)
    {
        if ((!$bundle = $field->Bundle)
            || empty($bundle->info['payment_enable'])
        ) return;

        if ($plans = $this->_application->Payment_Plans($bundle->name, $this->_application->Filter('payment_base_plan_types', ['base'], [$bundle->name]))) {
            foreach (array_keys($plans) as $plan_id) {
                $plans[$plan_id] = $plans[$plan_id]->paymentPlanTitle();
            }
        }
        $plans['-1'] = __('Show all expired', 'directories-payments');
        $plans['-2'] = __('Show all deactivated', 'directories-payments');
        $plans['-3'] = __('Show all expiring', 'directories-payments');

        return $plans;
    }

    public function fieldRestrictableRestrict(Field\IField $field, $value)
    {
        if ($value == -1) {
            return array('column' => 'expires_at', 'compare' => 'BETWEEN', 'value' => [1, time()]);
        } elseif ($value == -2) {
            return array('column' => 'deactivated_at', 'compare' => '>', 'value' => 0);
        } elseif ($value == -3) {
            $expiring_ts = time() + 86400 * $this->_application->getComponent('Payment')->getConfig('renewal', 'expiring_days');
            return array('column' => 'expires_at', 'compare' => 'BETWEEN', 'value' => [time(), $expiring_ts]);
        }
        return array('column' => 'plan_id');
    }

    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        switch ($key) {
            case 'expire_on':
                return empty($value['expires_at']) ? '' : $this->_application->System_Date($value['expires_at']);
            case 'expire_days':
                if (empty($value['expires_at'])) return '';

                $days = floor(($value['expires_at'] - time()) / 86400);
                if ($days === 0) $days = 1;
                return $days;
            default:
                return ($plan = $this->_application->Payment_Plan($entity)) ? $plan->paymentPlanTitle() : '';
        }
    }

    public function fieldCopyValues(Field\IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }

    public function fieldColumnableInfo(Field\IField $field)
    {
        return [
            '' => [
                'label' => $field->getFieldLabel(),
                'sortby' => 'plan_id',
            ],
            'expires_at' => [
                'label' => __('Exp. Date', 'directories-payments'),
                'sortby' => 'expires_at',
                'hidden' => true,
            ],
        ];
    }

    public function fieldColumnableColumn(Field\IField $field, $value, $column = '')
    {
        switch ($column) {
            case 'expires_at':
                if (empty($value[0]['expires_at'])) return;

                return $this->_application->System_Date($value[0]['expires_at'], true);
            default:
                if (empty($value[0]['plan_id'])
                    || !$field->bundle_name
                    || (!$plan = $this->_application->Payment_Plan($field->bundle_name, $value[0]['plan_id']))
                ) return;

                return $this->_application->H($plan->paymentPlanTitle());
        }
    }

    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {
        if (!$isServerSide) return;

        return [
            '' => [
                'compare' => ['value', '!value', 'one', 'empty', 'filled'],
                'tip' => __('Enter payment plan IDs and/or slugs separated with commas.', 'directories-payments'),
                'example' => '1,5,arts,17',
            ],
        ];
    }

    public function fieldConditionableRule(IField $field, $compare, $value = null, $_name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case 'one':
                $value = trim($value);
                if (strpos($value, ',')) {
                    if (!$value = explode(',', $value)) return;

                    $value = array_map('trim', $value);
                }
                return ['type' => $compare, 'value' => $value];
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
                return;
        }
    }

    public function fieldConditionableMatch(IField $field, array $rule, array $values, IEntity $entity)
    {
        switch ($rule['type']) {
            case 'value':
            case '!value':
            case 'one':
                if (empty($values)) return $rule['type'] === '!value';

                foreach ((array)$rule['value'] as $rule_value) {
                    // Get payment plan ID if rule value is a plan slug
                    if (is_numeric($rule_value)) {
                        $rule_value = (int)$rule_value;
                    } else {
                        if ($plan = $this->_application->Payment_Plan($entity->getBundleName(), $rule_value)) {
                            $rule_value = $plan->paymentPlanId();
                        }
                    }

                    if (!empty($rule_value)) {
                        foreach ($values as $input) {
                            if (is_array($input)) {
                                $term_id = (int)$input['plan_id'];
                            } else {
                                $term_id = (int)$input;
                            }
                            if ($term_id === $rule_value) {
                                if ($rule['type'] === '!value') return false;
                                if ($rule['type'] === 'one') return true;
                                continue 2;
                            }
                        }
                    }

                    // One of rules did not match
                    if ($rule['type'] === 'value') return false;
                }
                // All rules matched or did not match.
                return $rule['type'] !== 'one' ? true : false;
            case 'empty':
                return empty($values) === $rule['value'];
            case 'filled':
                return !empty($values) === $rule['value'];
            default:
                return false;
        }
    }
}
