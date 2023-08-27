<?php
namespace SabaiApps\Directories\Component\CSV\Controller\Admin;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;

class Export extends Form\AbstractMultiStepController
{    
    protected function _getBundle(Context $context)
    {
        return $context->bundle;
    }
    
    protected function _getSteps(Context $context, array &$formStorage)
    {
        return array('select_fields' => [], 'exporter_settings' => [], 'export' => []);
    }
    
    public function _getFormForStepSelectFields(Context $context, array &$formStorage)
    {
        $exporters_by_field_type = $this->CSV_Exporters(true);
        $bundle = $this->_getBundle($context);
        $fields = $this->Entity_Field($bundle->name);
        $options = $custom_field_options = [];
        foreach ($fields as $field_name => $field) {
            if ((!$exporter_name = @$exporters_by_field_type[$field->getFieldType()])
                || (!$exporter = $this->CSV_Exporters_impl($exporter_name, true))
                || !$exporter->csvExporterSupports($bundle, $field)
            ) continue;
            
            $columns = $exporter->csvExporterInfo('columns');
            if (is_array($columns)) {
                if ($field->isCustomField()) {
                    foreach ($columns as $column => $label) {
                        $custom_field_options[$this->_getFieldOptionValue($field_name, $column)] = array(
                            'field' => $this->_getFieldLabel($field) . ' - ' . $label,
                            'column_header' => $field_name . '_' . $column,
                        );
                    }
                } else {
                    foreach ($columns as $column => $column_label) {
                        $options[$this->_getFieldOptionValue($field_name, $column)] = array(
                            'field' => $this->_getFieldLabel($field, $column_label),
                            'column_header' => $field_name . '_' . $column,
                        );
                    }
                }
            } else {
                $option = $this->_getFieldOptionValue($field_name, (string)$columns);
                if ($field->isCustomField()) {
                    $custom_field_options[$option] = array(
                        'field' => $this->_getFieldLabel($field),
                        'column_header' => $field_name,
                    );
                } else {
                    $options[$option] = array(
                        'field' => $this->_getFieldLabel($field),
                        'column_header' => $field_name,
                    );
                }
            }
        }
        uasort($options, function($a, $b) { return strnatcmp($a['field'], $b['field']); });
        if (!empty($custom_field_options)) {
            uasort($custom_field_options, function($a, $b) { return strnatcmp($a['field'], $b['field']); });
            $options += $custom_field_options;
        }
        
        // Disable required fields
        $options_disabled = [];
        foreach ($this->_getAllRequiredFields($context, $bundle) as $field_name) {
            $options_disabled[] = $this->_getFieldOptionValue($field_name);
        }
        
        $form = array(
            '#header' => [
                [
                    'level' => 'info',
                    'message' => __('Select the fields to export and configure CSV column headers.', 'directories'),
                ],
            ],
            'fields' => array(
                '#type' => 'tableselect',
                '#header' => array(
                    'field' => __('Field name', 'directories'),
                    'column_header' => __('Column header', 'directories'),
                ),
                '#multiple' => true,
                '#js_select' => true,
                '#options' => $options,
                '#options_disabled' => $options_disabled,
                '#default_value' => array_keys($options),
                '#element_validate' => array(array(array($this, 'validateSelectFields'), array($context, $fields, $bundle))),
            ),
        );
        
        return $form;
    }
    
    protected function _getFieldOptionValue($fieldName, $column = '')
    {
        return $fieldName . '__' . $column;
    }
    
    protected function _getFieldLabel(Field\IField $field, $columnLabel = '')
    {
        $label = $this->H($field->getFieldLabel()) . ' (' . $field->getFieldName() . ')';
        if ($field->isCustomField()) {
            $label = '<span class="' . DRTS_BS_PREFIX . 'badge ' . DRTS_BS_PREFIX . 'badge-secondary">' . $this->H(__('Custom field', 'directories')) . '</span> ' . $label;
        }
        if (strlen($columnLabel)) {
            $label .=  ' - ' . $this->H($columnLabel);
        }
        return $label;
    }
    
    protected function _getAllRequiredFields(Context $context, $bundle)
    {
        $required_fields = [];
        
        if (!empty($bundle->info['parent'])) {
            $required_fields[] = $bundle->entitytype_name . '_parent';
        }
        
        return $required_fields;
    }
    
    public function validateSelectFields($form, &$value, $element, $context, $fields, $bundle)
    {
        $value = is_array($value) ? array_filter($value) : [];

        // Make sure required fields are going to be imported
        foreach ($this->_getAllRequiredFields($context, $bundle) as $field_name) {
            if (isset($fields[$field_name])
                && !in_array($option_value = $this->_getFieldOptionValue($field_name), $value)
            ) {
                $value[] = $option_value;
            }
        }
    }
    
    public function _getFormForStepExporterSettings(Context $context, array &$formStorage)
    {
        $form = array('#header' => [], 'settings' => []);

        $selected_fields = $formStorage['values']['select_fields']['fields'];
        $exporters_by_field_type = $this->CSV_Exporters(true);
        $bundle = $this->_getBundle($context);
        $fields = $this->Entity_Field($bundle->name);
        foreach ($selected_fields as $selected_field) {
            if (!$_selected_field = explode('__', $selected_field)) continue;

            $field_name = $_selected_field[0];
            $column = $_selected_field[1];      
            
            if (!$field = @$fields[$field_name]) continue;
                 
            $exporter_name = $exporters_by_field_type[$field->getFieldType()];
            if (!$exporter = $this->CSV_Exporters_impl($exporter_name, true)) {
                continue;
            }
            $info = $exporter->csvExporterInfo();
            $parents = array('settings', $field_name);
            if (strlen($column)) {
                $parents[] = $column;
            }
            if ($column_settings_form = $exporter->csvExporterSettingsForm($field, (array)@$info['default_settings'], $column, '"', $parents)) {
                foreach (array_keys($column_settings_form) as $key) {
                    if (strpos($key, '#') !== 0 ) {
                        $column_settings_form[$key]['#horizontal'] = true;
                    }
                }
                if (strlen($column)) {
                    $form['settings'][$field_name][$column] = $column_settings_form;
                    $form['settings'][$field_name][$column]['#title'] = $info['columns'][$column];
                    $form['settings'][$field_name][$column]['#collapsible'] = false;
                } else {
                    $form['settings'][$field_name] = $column_settings_form;
                }
                $form['settings'][$field_name]['#collapsible'] = true;
                $form['settings'][$field_name]['#title_no_escape'] = true;
                if (!isset($form['settings'][$field_name]['#title'])) {
                    $form['settings'][$field_name]['#title'] = $this->_getFieldLabel($field);
                }
            }
        }
        if (empty($form['settings'])) {
            return $this->_skipStepAndGetForm($context, $formStorage);
        }
        
        $form['settings']['#tree'] = true;
        $form['#header'][] = [
            'level' => 'info',
            'message' => __('Please configure additional options for each field.', 'directories'),
        ];
        
        return $form;
    }
    
    public function _getFormForStepExport(Context $context, array &$formStorage)
    {
        $bundle = $this->_getBundle($context);
        $this->_initProgress($context, __('Exporting...', 'directories'));
        $this->_submitButtons[] = [
            '#btn_label' => __('Export Now', 'directories'),
            '#btn_color' => 'primary',
            '#btn_size' => 'lg'
        ];
        
        return array(
            'filename' => array(
                '#title' => __('File name', 'directories'),
                '#type' => 'textfield',
                '#field_suffix' => '.csv',
                '#default_value' => $bundle->name . '-' . date('Ymd', time()),
                '#regex' => '/^[a-zA-Z0-9-_]+$/',
                '#required' => true,
                '#horizontal' => true,
            ),
            'query' => [
                '#title' => __('Query by field', 'directories'),
                '#tree' => true,
                '#element_validate' => [
                    function (Form\Form $form, &$value, $element) {
                        if (empty($value['fields'])) return;

                        $queries = [];
                        foreach (array_filter($value['fields']) as $query) {
                            if (!strlen($query['field'])
                                || !strlen(trim($query['query']))
                            ) continue;

                            $queries[$query['field']] = $query['query'];
                        }
                        $value['fields'] = $queries;
                    }
                ],
                'fields' => [
                    [
                        '#type' => 'field_query',
                        '#fields' => $this->Entity_Field($bundle),
                        '#default_value' => null,
                        '#query_in_admin' => true,
                    ],
                ],
                '_add' => [
                    '#type' => 'addmore',
                ],
                '#horizontal' => true,
            ],
            'limit' => array(
                '#type' => 'number',
                '#title' => __('Limit to X records (0 for all records)', 'directories'),
                '#default_value' => 0,
                '#min_value' => 0,
                '#integer' => true,
                '#horizontal' => true,
            ),
            'offset' => array(
                '#type' => 'number',
                '#title' => __('Start from Xth record', 'directories'),
                '#default_value' => 1,
                '#min_value' => 1,
                '#integer' => true,
                '#horizontal' => true,
            ),
            'limit_request' => array(
                '#type' => 'number',
                '#title' => __('Number of records to process per request', 'directories'),
                '#description' => __('Adjust this setting if you are experiencing timeout errors.', 'directories'),
                '#default_value' => empty($bundle->info['is_taxonomy']) ? 10 : 20,
                '#min_value' => 1,
                '#integer' => true,
                '#horizontal' => true,
            ),
        );
    }
    
    public function _submitFormForStepExport(Context $context, Form\Form $form)
    {
        @set_time_limit(0);

        $logs = ['error' => [], 'warning' => [], 'info' => [], 'success' => []];

        if (isset($form->values['next'])) {
            $is_first = false;
            $offset = (int)$form->values['next'];
        } else {
            $is_first = true;
            $offset = (int)$form->values['offset'];
            try {
                $this->ValidateDirectory($this->getComponent('System')->getTmpDir(), true);
            } catch (\Exception $e) {
                throw new Exception\RuntimeException($e->getMessage());
            }
        }

        $file = rtrim($this->getComponent('System')->getTmpDir(), '/') . '/' . $form->values['filename'] . '.csv';        
        if (false === ($fp = fopen($file, $is_first ? 'w+' : 'a+'))) {
            throw new Exception\RuntimeException(sprintf('Failed opening file %s with write permission', $file));
        }

        $selected_fields = $form->storage['values']['select_fields']['fields'];
        $exporter_settings = (array)@$form->storage['values']['exporter_settings']['settings'];
        $exporters_by_field_type = $this->CSV_Exporters(true);
        $bundle = $this->_getBundle($context);
        $fields = $this->Entity_Field($bundle->name);
        $export_fields = $columns = [];
        foreach ($selected_fields as $selected_field) {
            if (!$_selected_field = explode('__', $selected_field)) continue;

            $field_name = $_selected_field[0];
            $column = $_selected_field[1];      
            
            if (!$field = @$fields[$field_name]) continue;
                    
            $exporter_name = $exporters_by_field_type[$field->getFieldType()];
            if (!$exporter = $this->CSV_Exporters_impl($exporter_name, true)) {
                continue;
            }
            
            if (strlen($column)) {
                $columns[$field_name][$column] = $column;
            }
            $export_fields[$field_name] = $exporter_name;
        }
        unset($selected_fields);

        if ($is_first) {
            $headers = [];
            foreach (array_keys($export_fields) as $field_name) {
                if (isset($columns[$field_name])) {
                    foreach ($columns[$field_name] as $column) {
                        $headers[] = $field_name . '__' . $column;
                    }
                } else {
                    $headers[] = $field_name;
                }
            }
            if (false === fputcsv($fp, $headers)) {
                throw new Exception\RuntimeException(sprintf('Failed writing CSV headers into file %s', $file));
            }
        }

        $limit = (int)$form->values['limit'];
        $fetch_limit = (int)$form->values['limit_request'];
        if ($limit && $limit < $fetch_limit) {
            $fetch_limit = $limit;
        }
        $rows_exported = isset($form->storage['rows_exported']) ? $form->storage['rows_exported'] : 0;
        $rows_failed = isset($form->storage['rows_failed']) ? $form->storage['rows_failed'] : 0;
        $bundle = $this->_getBundle($context);
        $query_fields = empty($form->values['query']['fields']) ? [] : $form->values['query']['fields'];
        $count = $this->_getQuery($context, $bundle, $query_fields)->count();
        if ($limit && $count > $limit) {
            $count = $limit;
        }
        if (!isset($form->storage['exporter_storage'])) {
            $form->storage['exporter_storage'] = ['files' => [$file]];
        }
        $i = $offset - 1;
        $start_time = microtime(true);
        $entities = $this->_getQuery($context, $bundle, $query_fields)
            ->sortById()
            ->fetch($fetch_limit, $offset - 1, null, true);

        // Notify
        $this->Action('csv_export_entities', array($bundle, $entities, $export_fields, $exporter_settings));

        foreach ($entities as $entity) {
            ++$i;

            $row = [];
            $field_values = $entity->getFieldValues(true);
            foreach ($export_fields as $field_name => $exporter_name) {
                if (!isset($field_values[$field_name])
                    || $field_values[$field_name] === false // can be false if conditions did not meet
                ) {
                    // No field value, so populate columns with empty values
                    if (isset($columns[$field_name])) {
                        foreach ($columns[$field_name] as $column) {
                            $row[$field_name . '__' . $column] = '';
                        }
                    } else {
                        $row[$field_name] = '';
                    }
                    continue;
                }

                $settings = isset($exporter_settings[$field_name]) ? $exporter_settings[$field_name] : [];
                $exported = $this->CSV_Exporters_impl($exporter_name)->csvExporterDoExport(
                    $fields[$field_name],
                    $settings,
                    $field_values[$field_name],
                    isset($columns[$field_name]) ? $columns[$field_name] : [],
                    $form->storage['exporter_storage'],
                    $logs
                );
                if (isset($columns[$field_name])) {
                    foreach ($columns[$field_name] as $column) {
                        $row[$field_name . '__' . $column] = isset($exported[$column]) ? $exported[$column] : '';
                    }
                } else {
                    $row[$field_name] = $exported;
                }
            }
            if (false === fputcsv($fp, $this->Filter('csv_export_entity', $row, [$bundle, $entity]))) {
                ++$rows_failed;
            } else {
                ++$rows_exported;
            }

            if ($limit && $i >= $limit) break;
        }

        $form->storage['total'] = $count;
        $form->storage['done'] = $i;
        $form->storage['rows_exported'] = $rows_exported;
        $form->storage['rows_failed'] = $rows_failed;
        
        fclose($fp);

        $end_time = microtime(true);
        $message = __('Exporting...', 'directories');
        if ($i - $offset === 0) {
            $message .= sprintf(
                ' %d of %d items processed (%s seconds).',
                $i,
                $form->storage['total'],
                $end_time - $start_time
            );
        } else {
            $message .= sprintf(
                ' %d-%d of %d items processed (%s seconds).',
                $offset,
                $i,
                $form->storage['total'],
                $end_time - $start_time
            );
        }

        if ($form->storage['done'] < $form->storage['total']) {
            $this->_isInProgress($context, $form->storage['done'], $form->storage['total'], $message, $logs);
            return;
        }

        // Notify each exporter that export has completed
        foreach ($export_fields as $field_name => $exporter_name) {
            $this->CSV_Exporters_impl($exporter_name)->csvExporterOnComplete(
                $fields[$field_name],
                isset($exporter_settings[$field_name]) ? $exporter_settings[$field_name] : [],
                isset($columns[$field_name]) ? $columns[$field_name] : [],
                $form->storage['exporter_storage'],
                $logs
            );
        }

        // Store logs to storage for the _complete() method.
        $form->storage['logs'] = $logs;
    }

    protected function _complete(Context $context, array $formStorage)
    {
        $logs = $formStorage['logs'];
        $message = [];
        if ($formStorage['rows_exported'] > 0) {
            $message[] = $this->H(sprintf(
                __('%d item(s) exported successfully.', 'directories'),
                $formStorage['rows_exported']
            ));
            $download_file = basename($formStorage['exporter_storage']['files'][0]);
            if (count($formStorage['exporter_storage']['files']) > 1
                && class_exists('\ZipArchive', false)
            ) {
                $zip = new \ZipArchive();
                $zip_file = basename($formStorage['exporter_storage']['files'][0], '.csv') . '.zip';
                if (true !== $result = $zip->open(rtrim(dirname($formStorage['exporter_storage']['files'][0]), '/') . '/' . $zip_file, \ZipArchive::CREATE)) {
                    $logs['error'][] = 'Failed creating zip archive (Error: ' . $this->H($result) . ')';
                } else {
                    foreach ($formStorage['exporter_storage']['files'] as $file) {
                        $zip->addFile($file, basename($file));
                    }
                    $zip->close();
                    $download_file = $zip_file; // let user download zip file
                }
            }
        }
        if (!empty($formStorage['rows_failed'])) {
            $message[] = $this->H(sprintf(__('Failed exporting %d item(s).', 'directories'), $formStorage['rows_failed']));
        }

        $this->_completeProgress(
            $context,
            null,
            empty($message) ? null : implode(' ', $message),
            $logs,
            isset($download_file) ? $download_file : null
        );
    }
    
    protected function _getQuery(Context $context, Entity\Model\Bundle $bundle, array $fields)
    {
        $query = $this->Entity_Query($bundle->entitytype_name, $bundle->name);
        if (!empty($fields)) {
            foreach ($fields as $field_name => $query_str) {
                if (($field = $this->Entity_Field($bundle->name, $field_name))
                    && ($field_type = $this->Field_Type($field->getFieldType(), true))
                    && $field_type instanceof Field\Type\IQueryable
                ) {
                    $field_type->fieldQueryableQuery($query->getFieldQuery(), ($property = $field->isPropertyField()) ? $property : $field_name, $query_str, $bundle);
                } else {
                    $this->logError(sprintf('Invalid query field for %s: %s', $bundle->name, $field_name));
                }
            }
        }
        return $this->Filter('csv_export_query', $query, array($bundle));
    }
}