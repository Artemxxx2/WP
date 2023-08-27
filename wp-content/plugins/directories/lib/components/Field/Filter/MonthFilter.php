<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class MonthFilter extends AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return [
            'label' => __('Month picker', 'directories'),
            'field_types' => ['date'],
            'default_settings' => [],
        ];
    }

    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = [])
    {
        return [
            '#type' => 'monthpicker',
            '#disable_time' => true,
        ];
    }

    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value);
    }

    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $from = $this->_application->getPlatform()->getSystemToSiteTime($value);
        $to = mktime(23, 59, 59, date('n', $from), date('t', $from), date('Y', $from));
        $query->fieldIsOrGreaterThan($field, $value)
            ->fieldIsSmallerThan($field, $this->_application->getPlatform()->getSiteToSystemTime($to));
    }

    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $format= $this->_application->getPlatform()->getDateFormat();
        $from = $this->_application->getPlatform()->getSystemToSiteTime($value);
        $to = mktime(23, 59, 59, date('n', $from), date('t', $from), date('Y', $from));
        return ['' => $this->_application->H($defaultLabel) . ': ' . date($format, $from) . ' - ' . date($format, $to)];
    }
}
