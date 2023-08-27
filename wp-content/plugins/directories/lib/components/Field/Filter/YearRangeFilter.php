<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Form;

class YearRangeFilter extends RangeFilter
{
    protected function _fieldFilterInfo()
    {
        $info = parent::_fieldFilterInfo();
        $info['field_types'] = ['date'];
        $info['label'] = __('Year range picker', 'directories');
        return $info;
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = parent::fieldFilterSettingsForm($field, $settings, $parents);
        $form['min_year'] = [
            '#title' => __('Minimum year', 'directories'),
            '#type' => 'number',
            '#integer' => true,
            '#min_value' => -3000,
            '#max_value' => 3000,
            '#default_value' => isset($settings['min_year']) ? $settings['min_year'] : date('Y') - 50,
            '#deacription' => __('Leave empty for current year.', 'directories'),
        ];
        $form['max_year'] = [
            '#title' => __('Maximum year', 'directories'),
            '#type' => 'number',
            '#integer' => true,
            '#min_value' => -3000,
            '#max_value' => 3000,
            '#default_value' => isset($settings['max_year']) ? $settings['max_year'] : date('Y'),
            '#deacription' => __('Leave empty for current year.', 'directories'),
        ];
        $form['step']['#type'] = 'slider';
        $form['step']['#step'] = 1;
        $form['step']['#min_value'] = 1;
        $form['step']['#max_value'] = 100;
        $form['step']['#integer'] = true;
        $form['#element_validate'][] = function(Form\Form $form, &$value, $element) use ($field) {
            if ($this->_getMinSetting($field, $value) >= $this->_getMaxSetting($field, $value)) {
                $form->setError(__('Minimum year must not be later than maximum year.', 'directories'), $element['#name'] . '[min_year]');
            }
        };

        return $form;
    }

    protected function _getMinSetting(IField $field, array $settings)
    {
        return empty($settings['min_year']) ? date('Y') : $settings['min_year'];
    }

    protected function _getMaxSetting(IField $field, array $settings)
    {
        return empty($settings['max_year']) ? date('Y') : $settings['max_year'];
    }

    protected function _getDefaultStep(IField $field)
    {
        return 1;
    }

    protected function _fieldFilterDoFilter(Query $query, IField $field, array $settings, $min, $max, array &$sorts)
    {
        $from = mktime(0, 0, 0, 1, 1, $min);
        $to = mktime(23, 59, 59, 12, 31, $max);
        $query->fieldIsOrGreaterThan($field, $this->_application->getPlatform()->getSiteToSystemTime($from))
            ->fieldIsSmallerThan($field, $this->_application->getPlatform()->getSiteToSystemTime($to));
    }
}