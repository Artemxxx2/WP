<?php
namespace SabaiApps\Directories\Component\CSV\Exporter;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Application;

abstract class AbstractExporter implements IExporter
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function csvExporterInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_csvExporterInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function csvExporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = []){}
    
    public function csvExporterDoExport(Entity\Model\Field $field, array $settings, $value, array $columns, array &$formStorage, array &$logs)
    {
        if (!empty($columns)) {
            $ret = [];
            foreach ($columns as $column) {
                $ret[$column] = isset($value[0][$column]) ? $value[0][$column] : '';
            }
            return $ret;
        }
        return $value[0]['value'];
    }
    
    public function csvExporterSupports(Entity\Model\Bundle $bundle, Entity\Model\Field $field)
    {
        return true;
    }

    public function csvExporterOnComplete(Entity\Model\Field $field, array $settings, array $columns, &$formStorage, array &$logs){}

    protected function _csvExporterInfo()
    {
        return array(
            'field_types' => array($this->_name),
        );
    }
    
    protected function _acceptMultipleValues(Entity\Model\Field $field, $enclosure, array $parents, array $reserved = [], $defaultSeparator = ';')
    {
        return array(
            '_separator' => array(
                '#type' => 'textfield',
                '#title' => __('Field value separator', 'directories'),
                '#size' => 5,
                '#description' => __('Enter the character that will be used to separate multiple values in case the field contains more than one value.', 'directories'),
                '#min_length' => 1,
                '#default_value' => $defaultSeparator,
                '#element_validate' => array(array(array($this, '_validateSeparator'), array($enclosure, $parents, $reserved))),
                '#weight' => 100,
                '#required' => true,
            ),
        );
    }
    
    public function _validateSeparator(Form\Form $form, &$value, $element, $enclosure, array $parents, array $reserved)
    {
        $form_values = $form->getValue($parents);        
        $value = trim($value);
        if ($value == $enclosure) {
            $form->setError(sprintf(__('Field value separator may not be the same as %s.', 'directories'), __('CSV file field enclosure', 'directories')), $element);
        }
        if (!empty($reserved)) {
            foreach ($reserved as $field_name => $field_label) {
                if (isset($form_values[$field_name])
                    && $value == $form_values[$field_name]
                ) {
                    $form->setError(sprintf(__('Field value separator may not be the same as %s.', 'directories'), $field_label), $element);
                }
            }
        }
    }
    
    protected function _getDateFormatSettingsForm(array $parents, array $reserved = [], $defaultDateFormatPhp = null)
    {
        return array(
            'date_format' => array(
                '#type' => 'select',
                '#title' => __('Date and time format', 'directories'),
                '#description' => __('Select the format used to represent date and time values in CSV.', 'directories'),
                '#options' => array(
                    'timestamp' => __('Timestamp', 'directories'),
                    'string' => __('Formatted date/time string', 'directories'),
                ),
                '#default_value' => 'timestamp',
            ),
            'date_format_php' => array(
                '#type' => 'textfield',
                '#title' => __('PHP date and time format', 'directories'),
                '#description' => __('Enter the data/time format string suitable for input to PHP date() function.', 'directories'),
                '#default_value' => isset($defaultDateFormatPhp) ? $defaultDateFormatPhp : 'Y-m-d',
                '#element_validate' => array(array(array($this, '_validateDateFormatPhp'), array($parents, $reserved))),
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[date_format]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'string'),
                    ),
                ),
                '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, array('date_format'))) === 'string';},
            ),
        );
    }
    
    public function _validateDateFormatPhp(Form\Form $form, &$value, $element, array $parents, array $reserved)
    {
        $form_values = $form->getValue($parents);
        
        if ($form_values['date_format'] !== 'string') return;
        
        if (isset($form_values['_separator']) && strlen($form_values['_separator'])) { 
            if (false !== strpos($value, $form_values['_separator'])) {
                $form->setError(sprintf(__('PHP date format may not contain %s.', 'directories'), __('Field value separator', 'directories')), $element);
            }
        }
        
        if (!empty($reserved)) {
            foreach ($reserved as $field_name => $field_label) {
                if (isset($form_values[$field_name])
                    && false !== strpos($value, $form_values[$field_name])
                ) {
                    $form->setError(sprintf(__('PHP date and time format may not contain %s.', 'directories'), $field_label), $element);
                }
            }
        }
    }
    
    protected function _getZipFileSettingsForm()
    {
        if (!class_exists('\ZipArchive', false)) return [];
        
        return array('zip' => array(
            '#type' => 'checkbox',
            '#title' => __('Generate zip archive of files/images', 'directories'),
            '#default_value' => true,
        ));
    }
    
    protected function _doZipFile(array $settings)
    {
        return !empty($settings['zip']);
    }
    
    protected function _getZipFile($fieldName, array $formStorage)
    {
        if (!class_exists('\ZipArchive', false)) return false;
        
        $zip = new \ZipArchive();
        $zip_file_path = rtrim($this->_application->getComponent('System')->getTmpDir(), '/') . '/' . basename($formStorage['files'][0], '.csv') . '-' . $fieldName . '.zip';
        if (true !== $result = $zip->open($zip_file_path, \ZipArchive::CREATE)) {
            $this->_application->logError('Failed creating zip archive. Error: ' . $result);
            return false;
        }
        
        return $zip;
    }
    
    protected function _getUserSettingsForm()
    {
        return array(
            'id_format' => array(
                '#type' => 'select',
                '#title' => __('User identification value format', 'directories'),
                '#description' => __('Select the format used to represent user identification values in CSV.', 'directories'),
                '#options' => array(
                    'id' => __('User ID', 'directories'),
                    'username' => __('Username', 'directories'),
                ),
                '#default_value' => 'username',
            ),
        );
    }
}