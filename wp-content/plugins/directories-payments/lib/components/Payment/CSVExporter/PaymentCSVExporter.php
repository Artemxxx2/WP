<?php
namespace SabaiApps\Directories\Component\Payment\CSVExporter;

use SabaiApps\Directories\Component\CSV\Exporter\AbstractExporter;
use SabaiApps\Directories\Component\Entity;

class PaymentCSVExporter extends AbstractExporter
{    
    protected function _csvExporterInfo()
    {
        switch ($this->_name) {
            case 'payment_plan':
                $columns = array(
                    'expires_at' => __('Exp. Date', 'directories-payments'),
                    'plan_id' => __('Plan ID', 'directories-payments'),
                    'addon_features' => __('Additional Features', 'directories-payments'),
                );
                break;
            
            default:
                $columns = null;
        }
        return array(
            'field_types' => array($this->_name),
            'columns' => $columns,
        );
    }
    
    public function csvExporterSupports(Entity\Model\Bundle $bundle, Entity\Model\Field $field)
    {
        switch ($this->_name) {
            case 'payment_plan':
                return !empty($bundle->info['payment_enable']);
        }
        return true;
    }
    
    public function csvExporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        switch ($this->_name) {
            case 'payment_plan':
                switch ($column) {
                    case 'expires_at':
                        return $this->_getDateFormatSettingsForm($parents);
                }
        }
    }
    
    public function csvExporterDoExport(Entity\Model\Field $field, array $settings, $value, array $columns, array &$formStorage, array &$logs)
    {
        switch ($this->_name) {
            case 'payment_plan':
                $ret = parent::csvExporterDoExport($field, $settings, $value, $columns, $formStorage, $logs);
                $ret['addon_features'] = serialize($ret['addon_features']);
                if ($settings['expires_at']['date_format'] === 'string') {
                    if (!empty($ret['expires_at'])
                        && false !== ($date = @date($settings['expires_at']['date_format_php'], $ret['expires_at']))
                    ) {
                        $ret['expires_at'] = $date;
                    } else {
                        $ret['expires_at'] = '';
                    }
                }
                return $ret;
        }
    }
}