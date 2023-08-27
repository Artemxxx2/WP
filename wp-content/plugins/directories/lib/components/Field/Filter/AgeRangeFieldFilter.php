<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;

class AgeRangeFieldFilter extends RangeFilter
{
    protected $_maxSuffix = '+';

    protected function _fieldFilterInfo()
    {
        return [
            'label' => __('Age range filter', 'directories'),
            'field_types' => ['date'],
        ] + parent::_fieldFilterInfo();
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = parent::fieldFilterSettingsForm($field, $settings, $parents);
        $form['step']['#integer'] = true;

        return $form;
    }

    protected function _getMinSetting(IField $field, array $settings)
    {
        return 0;
    }

    protected function _getMaxSetting(IField $field, array $settings)
    {
        return 99;
    }

    protected function _getDefaultStep(IField $field)
    {
        return 1;
    }

    protected function _getDefaultLabel($defaultLabel, array $settings)
    {
        return __('Age', 'directories');
    }

    protected function _fieldFilterDoFilter(Query $query, IField $field, array $settings, $min, $max, array &$sorts)
    {
        $now = time();
        $oneyear = 86400 * 365;
        $query->fieldIsGreaterThan($field, $now - ($oneyear * ($max + 1)))
            ->fieldIsOrSmallerThan($field, $now - ($oneyear * $min));
    }
}
