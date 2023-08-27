<?php
namespace SabaiApps\Directories\Component\Payment\CSVImporter;

use SabaiApps\Directories\Component\CSV\Importer\AbstractImporter;
use SabaiApps\Directories\Component\Entity;

class PaymentCSVImporter extends AbstractImporter
{    
    protected function _csvImporterInfo()
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
    
    public function csvImporterSupports(Entity\Model\Bundle $bundle, Entity\Model\Field $field)
    {
        switch ($this->_name) {
            case 'payment_plan':
                return !empty($bundle->info['payment_enable']);
        }
        return true;
    }
    
    public function csvImporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        switch ($this->_name) {
            case 'payment_plan':
                switch ($column) {
                    case 'expires_at':
                        return $this->_getDateFormatSettingsForm($parents);
                }
        }
    }
    
    public function csvImporterDoImport(Entity\Model\Field $field, array $settings, $column, $value, &$formStorage, array &$logs)
    {
        switch ($this->_name) {  
            case 'payment_plan':
                if ($column === 'expires_at') {
                    if (false === ($value = $this->_strToTime($value, $settings))) {
                        return null;
                    }
                }
                return array(array($column => $value));
        }
    }
}