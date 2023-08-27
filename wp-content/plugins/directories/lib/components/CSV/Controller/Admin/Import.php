<?php
namespace SabaiApps\Directories\Component\CSV\Controller\Admin;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Entity;

class Import extends Form\AbstractMultiStepController
{
    protected $_csvFile;

    protected function _getBundle(Context $context)
    {
        return $context->bundle;
    }
    
    protected function _getSteps(Context $context, array &$formStorage)
    {
        return array('csv_file' => [], 'map_fields' => [], 'settings' => [], 'import' => []);
    }
    
    public function _getFormForStepCsvFile(Context $context, array &$formStorage)
    {
        $bundle = $this->_getBundle($context);
        $existing_files = $this->Filter('csv_import_files', [], [$bundle]);
        $custom_existing_files = [];
        $dir = $this->getComponent('CSV')->getVarDir();
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.csv') as $csv_file) {
                $custom_existing_files[$csv_file] = basename($csv_file);
            }
        }

        $form = array(
            'upload' => array(
                '#type' => 'file',
                '#description' => sprintf(
                    __('You can also upload your CSV file to %s using FTP, and then reload this page.'),
                    $this->getComponent('CSV')->getVarDir()
                ),
                '#upload_dir' => $this->getComponent('System')->getTmpDir(),
                '#allowed_extensions' => array('csv'),
                '#required' => function($form) { return $form->getValue('type') === 'upload'; },
                // The finfo_file function used by the uploader to check mime types for CSV files is buggy. We can skip it safely here since this is for admins only.
                '#skip_mime_type_check' => true,
                '#multiple' => false,
                '#states' => [
                    'visible' => [
                        '[name="type"]' => ['value' => 'upload'],
                    ],
                ],
                '#weight' => 5,
            ),
            'delimiter' => array(
                '#type' => 'textfield',
                '#field_prefix' => __('Delimiter', 'directories'),
                '#size' => 5,
                '#description' => __('Enter the character used as CSV column delimiters.', 'directories'),
                '#min_length' => 1,
                '#max_length' => 1,
                '#default_value' => ',',
                '#required' => function($form) { return $form->getValue('type') === 'upload'; },
                '#weight' => 6,
            ),
            'enclosure' => array(
                '#type' => 'textfield',
                '#field_prefix' => __('Enclosure', 'directories'),
                '#size' => 5,
                '#description' => __('Enter the character used as CSV column enclosures.', 'directories'),
                '#min_length' => 1,
                '#max_length' => 1,
                '#default_value' => '"',
                '#required' => function($form) { return $form->getValue('type') === 'upload'; },
                '#weight' => 7,
            ),
        );

        if (!empty($existing_files)
            || !empty($custom_existing_files)
        ) {
            $form['type'] = [
                '#type' => 'select',
                '#title' => __('CSV file', 'directories'),
                '#options' => [
                    'upload' => __('Upload a CSV file', 'directories'),
                    'existing' => __('Select from existing CSV files', 'directories'),
                ],
                '#default_value' => 'upload',
                '#required' => true,
                '#weight' => 1,
            ];
            $form['existing'] = [
                '#type' => 'select',
                '#description' => __('Select a CSV file to import.', 'directories'),
                '#options' => $existing_files + $custom_existing_files,
                '#required' => true,
                '#weight' => 5,
                '#states' => [
                    'visible' => [
                        '[name="type"]' => ['value' => 'existing'],
                    ],
                ],
                '#required' => function($form) { return $form->getValue('type') === 'existing'; },
                '#element_validate' => [[$this, '_validateExistingFile']],
            ];
        } else {
            $form['upload']['#title'] = __('CSV file', 'directories');
            $form['type'] = [
                '#type' => 'hidden',
                '#default_value' => 'upload',
            ];
        }
        
        foreach (array_keys($form) as $key) {
            if (strpos($key, '#') !== 0 ) {
                $form[$key]['#horizontal'] = true;
            }
        }
        
        return $form;
    }

    public function _validateExistingFile(Form\Form $form, &$value, $element)
    {
        if ($form->getValue('type') === 'existing') {
            try {
                new \SplFileObject($form->getValue('existing'));
            } catch (\Exception $e) {
                $form->setError($e->getMessage(), $element);
            }
        }
    }
    
    public function _getFormForStepMapFields(Context $context, array &$formStorage)
    {
        @setlocale(LC_ALL, $this->getPlatform()->getLocale());

        $bundle = $this->_getBundle($context);
        
        // Extract header and a first row
        $rows = $this->_getCsvRows($bundle, $formStorage['values']['csv_file'], 2);
        $csv_columns = $rows[0];
        $csv_row1 = $rows[1];
        
        $options = [];
        $optgroups = $custom_field_options = [];
        $importers_by_field_type = $this->CSV_Importers(true);
        $fields = $this->Entity_Field($bundle->name);
        $properties = $this->Entity_Types_impl($bundle->entitytype_name)->entityTypeInfo('properties');
        $id_column_field_type = $properties['id']['type'];
        $id_column_key = null;
        foreach ($fields as $field_name => $field) {
            if ((!$importer_name = @$importers_by_field_type[$field->getFieldType()])
                || (!$importer = $this->CSV_Importers_impl($importer_name, true))
                || !$importer->csvImporterSupports($bundle, $field)
            ) continue;
                
            $columns = $importer->csvImporterInfo('columns');
            if (is_array($columns)) {
                $optgroups[$field_name] = $this->_getFieldLabel($field);
                if ($field->isCustomField()) {
                    foreach ($columns as $column => $label) {
                        $custom_field_options[$this->_getFieldOptionValue($field_name, $column)] = array(
                            '#group' => $field_name,
                            '#title' => $label,
                        );
                    }
                } else {
                    foreach ($columns as $column => $label) {
                        $options[$this->_getFieldOptionValue($field_name, $column)] = array(
                            '#group' => $field_name,
                            '#title' => $label,
                        );
                    }
                }
            } else {
                $option = $this->_getFieldOptionValue($field_name, (string)$columns);
                if ($field->isCustomField()) {
                    $custom_field_options[$option] = $this->_getFieldLabel($field);
                } else {
                    $options[$option] = $this->_getFieldLabel($field);
                    if ($field->getFieldType() === $id_column_field_type) {
                        $id_column_key = $option;
                    }
                }
            }
        }
        asort($options);
        if (!empty($custom_field_options)) {
            asort($custom_field_options);
            $options += $custom_field_options;
        }
        $options = array('' => __('— Select —', 'directories')) + $options;
        
        $form = array(
            '#header' => [
                [
                    'level' => 'info',
                    'message' => $this->H(__('Set up the associations between the CSV file columns and content fields.', 'directories')),
                ],
            ],
            '#options' => $options,
            '#optgroups' => $optgroups,
            'header' => array(
                '#type' => 'markup',
                '#markup' => '<table class="' . DRTS_BS_PREFIX . 'table drts-csv-table"><thead><tr><th style="width:25%;">' . __('Column Header', 'directories') . '</th>'
                    . '<th style="width:35%;">' . __('Row 1', 'directories') . '</th>'
                    . '<th style="width:40%;">' . __('Select field', 'directories') . '</th></tr></thead><tbody>',
            ),
            'fields' => array(
                '#tree' => true,
                '#element_validate' => [
                    [[$this, 'validateMapFields'], [$context, $fields, $bundle]]
                ],
                '#submit' => [
                    9 => [ // weight
                        function ($form) use ($bundle) {
                            $this->getPlatform()->setOption('csv_import_map_fields_' . $bundle->name, (array)$form->values['fields']);
                        },
                    ],
                ],
            ),
            'footer' => array(
                '#type' => 'markup',
                '#markup' => '</tbody></table>',
            ),
        );

        $prev_map_fields = $this->getPlatform()->getOption('csv_import_map_fields_' . $bundle->name, []);
        $invalid_column_names = [];
        foreach ($csv_columns as $column_key => $column_name) {
            $column_name = trim($column_name);
            if (!preg_match('#^[a-zA-Z0-9/_-]+$#', $column_name)) {
                $invalid_column_names[] = $column_name;
                continue;
            }

            if (isset($csv_row1[$column_key])) {
                $column_value = $csv_row1[$column_key];
                if (strlen($column_value) > 300) {
                    $column_value = $this->Summarize($column_value, 300);
                }
                if (strlen($column_value)) {
                    $column_value = '<code>' . $this->H($column_value) . '</code>';
                }
            } else {
                $column_value = '';
            }
            $default_value = null;
            if (isset($prev_map_fields[$column_name])
                && isset($options[$prev_map_fields[$column_name]])
            ) {
                $default_value = $prev_map_fields[$column_name];
            } else {
                if (isset($options[$column_name])
                    && $column_name !== $id_column_key // do not make id column selected by default
                ) {
                    $default_value = $column_name;
                }
            }
            $form['fields'][$column_name] = array(
                '#prefix' => '<tr><td>' . $this->H($column_name) . '</td><td>' . $column_value . '</td><td>',
                '#suffix' => '</td></tr>',
                '#type' => 'select',
                '#options' => $options,
                '#default_value' => $default_value,
                '#optgroups' => $optgroups,
            );
        }
        // Add warning if importing existing CSV file
        if ($formStorage['values']['csv_file']['type'] === 'existing') {
            $form['#header'][] = [
                'level' => 'warning',
                'message' => __('Leave as-is if you are unsure.', 'directories'),
            ];
        }
        // Show invalid column name error
        if (!empty($invalid_column_names)) {
            $form['#header'][] = [
                'level' => 'danger',
                'message' => sprintf(
                    __('Invalid CSV column names were found: %s', 'directories'),
                    '<code>' . implode('</code>, <code>', array_map([$this->_application, 'H'], $invalid_column_names)) . '</code>'
                ),
            ];
        }

        return $form;
    }
    
    protected function _getFieldOptionValue($fieldName, $column = '')
    {
        return strlen($column) ? $fieldName . '__' . $column : $fieldName;
    }
    
    protected function _getFieldLabel(Field\IField $field)
    {
        $label = $field->getFieldLabel() . ' (' . $field->getFieldName() . ')';
        if ($field->isCustomField()) {
            $label = sprintf(__('Custom field - %s', 'directories'), $label);
        }
        return $label;
    }
    
    public function validateMapFields($form, &$value, $element, $context, $fields, $bundle)
    {
        $value = array_filter((array)$value);
        
        $required_fields = [];
        
        if (!empty($bundle->info['parent'])) {
            $required_fields[] = $bundle->entitytype_name . '_parent';
        }

        // Make sure required fields are going to be imported
        foreach ($required_fields as $field_name) {
            if (isset($fields[$field_name]) && !in_array($this->_getFieldOptionValue($field_name), $value)) {
                $form->setError(sprintf(
                    __('The following field needs to be selected: %s.', 'directories'),
                    $this->_getFieldLabel($fields[$field_name])
                ));
            }
        }
        
        $count = array_count_values($value);
        foreach ($count as $option => $_count) {
            if ($option === '' || $_count <= 1) continue;
            
            $_option = explode('__', $option);
            $field_name = $_option[0];
            $form->setError(sprintf(
                __('You may not associate multiple columns with the field: %s', 'directories'),
                $this->_getFieldLabel($fields[$field_name])
            ));
        }
    }
    
    public function _getFormForStepSettings(Context $context, array &$formStorage)
    {     
        $form = array('importers' => []);

        $bundle = $this->_getBundle($context);
        $enclosure = $this->_getCsvFile($bundle, $formStorage['values']['csv_file'])->getCsvControl()[1];
        $mapped_fields = $formStorage['values']['map_fields']['fields'];
        $fields = $this->Entity_Field($this->_getBundle($context)->name);
        $importers_by_field_type = $this->CSV_Importers(true);
        foreach ($mapped_fields as $column_name => $mapped_field) {
            if (!$_mapped_field = explode('__', $mapped_field)) continue;

            $field_name = $_mapped_field[0];
            $column = (string)@$_mapped_field[1];      
            
            if (!$field = @$fields[$field_name]) continue;
                    
            $importer_name = $importers_by_field_type[$field->getFieldType()];
            if (!$importer = $this->CSV_Importers_impl($importer_name, true)) {
                continue;
            }
            $info = $importer->csvImporterInfo();
            $parents = array('importers', $field_name);
            if (strlen($column)) {
                $parents[] = $column;
            }
            $default_settings = [];
            if (!empty($info['default_settings']) && is_array($info['default_settings'])) $default_settings += $info['default_settings'];
            if ($column_settings_form = $importer->csvImporterSettingsForm($field, $default_settings, $column, $enclosure, $parents)) {
                foreach (array_keys($column_settings_form) as $key) {
                    if (strpos($key, '#') !== 0 ) {
                        $column_settings_form[$key]['#horizontal'] = true;
                    }
                }
                if (strlen($column)) {
                    $form['importers'][$field_name][$column] = $column_settings_form;
                    $form['importers'][$field_name][$column]['#title'] = $info['columns'][$column];
                    $form['importers'][$field_name][$column]['#collapsible'] = false;
                } else {
                    $form['importers'][$field_name] = $column_settings_form;
                }
                $form['importers'][$field_name]['#collapsible'] = true;
                if (!isset($form['importers'][$field_name]['#title'])) {
                    $form['importers'][$field_name]['#title'] = $this->_getFieldLabel($field);
                }
            }
        }
        if (empty($form['importers'])) {
            return $this->_skipStepAndGetForm($context, $formStorage);
        }
        
        $form['importers']['#tree'] = true;
        $form['#header'][] = [
            'level' => 'info',
            'message' => __('Please configure additional options for each field.', 'directories'),
        ];;
        // Add warning if importing existing CSV file
        if ($formStorage['values']['csv_file']['type'] === 'existing') {
            $form['#header'][] = [
                'level' => 'warning',
                'message' => __('Leave as-is if you are unsure.', 'directories'),
            ];
        }
        
        return $this->Filter('csv_import_settings_form', $form, [$bundle, $formStorage['values']['csv_file']]);
    }
    
    public function _getFormForStepImport(Context $context, array &$formStorage)
    {
        $this->_initProgress($context, __('Importing...', 'directories'));
        $this->_submitButtons[] = array('#btn_label' => __('Import Now', 'directories'), '#btn_color' => 'primary', '#btn_size' => 'lg');
        $bundle = $this->_getBundle($context);
        $file = $this->_getCsvFile($bundle, $formStorage['values']['csv_file']);
        $file->seek(PHP_INT_MAX);
        $total = $file->key() - 1;

        return [
            '#header' => [
                [
                    'level' => 'info',
                    'message' => sprintf(__('%d records will be imported.', 'directories'), $total),
                ],
            ],
            'limit_request' => [
                '#type' => 'number',
                '#title' => __('Number of records to process per request', 'directories'),
                '#description' => __('Adjust this setting if you are experiencing timeout errors.', 'directories'),
                '#default_value' => empty($bundle->info['is_taxonomy']) ? 10 : 20,
                '#min_value' => 1,
                '#integer' => true,
                '#horizontal' => true,
            ],
        ];
    }
    
    public function _submitFormForStepImport(Context $context, Form\Form $form)
    {
        @set_time_limit(0);

        define('DRTS_CSV_IMPORTING', true);

        $logs = ['error' => [], 'warning' => [], 'info' => [], 'success' => []];

        $bundle = $this->_getBundle($context);

        $csv_columns = $this->_getCsvRows($bundle, $form->storage['values']['csv_file']);
        $csv_columns = $csv_columns[0];
        $mapped_fields = $form->storage['values']['map_fields']['fields'];
        $importer_settings = (array)@$form->storage['values']['settings']['importers'];

        $_mapped_fields = [];
        $fields = $this->Entity_Field($bundle->name);
        $importers_by_field_type = $this->CSV_Importers(true);
        $rows_imported = isset($form->storage['rows_imported']) ? $form->storage['rows_imported'] : 0;
        $rows_updated = isset($form->storage['rows_updated']) ? $form->storage['rows_updated'] : 0;
        $rows_failed = isset($form->storage['rows_failed']) ? $form->storage['rows_failed'] : 0;
        $offset = $rows_imported + $rows_updated + $rows_failed;
        $file = $this->_getCsvFile($bundle, $form->storage['values']['csv_file']);
        if (!isset($form->storage['total'])) {
            $file->seek(PHP_INT_MAX);
            $form->storage['total'] = $file->key() - 1;
        }
        if (!isset($form->storage['importer_storage'])) {
            $form->storage['importer_storage'] = [];
        }
        $file->seek($offset + 1);
        $limit = (int)$form->values['limit_request'];
        $start_row_num = $offset + 1;
        $start_time = microtime(true);
        while (!$file->eof()) {
            $csv_row = $file->current();
            $file->next();
            if (!is_array($csv_row)
                || array(null) === $csv_row // skip invalid/empty rows
            )  {
                ++$rows_failed;
                continue;
            }

            ++$offset;

            $values = [];
            foreach ($csv_columns as $column_index => $column_name) {
                $column_name = trim($column_name);
                if (!isset($csv_row[$column_index])
                    || !strlen($csv_row[$column_index])
                ) continue; // no valid value for this row column

                if (!isset($_mapped_fields[$column_name])) {
                    if (!isset($mapped_fields[$column_name])
                        || (!$_mapped_fields[$column_name] = explode('__', $mapped_fields[$column_name]))
                    ) {
                        // Unset column since mapped field is invalid, to stop further processing the column
                        unset($csv_columns[$column_index], $_mapped_fields[$column_name]);
                        continue;
                    }
                }

                // Check importer and field
                $field_name = $_mapped_fields[$column_name][0];
                if ((!$field = @$fields[$field_name])
                    || (!$importer_name = @$importers_by_field_type[$field->getFieldType()])
                    || (!$importer = $this->CSV_Importers_impl($importer_name, true))
                ) {
                    // Unset column since mapped field is invalid, to stop further processing the column
                    unset($csv_columns[$column_index], $_mapped_fields[$column_name]);
                    continue;
                }

                $column = (string)@$_mapped_fields[$column_name][1];

                // Init importer settings
                if (strlen($column)) {
                    $settings = isset($importer_settings[$field_name][$column]) ? $importer_settings[$field_name][$column] : [];
                } else {
                    $settings = isset($importer_settings[$field_name]) ? $importer_settings[$field_name] : [];
                }

                // Import
                try {
                    $field_value = $importer->csvImporterDoImport(
                        $field,
                        $settings,
                        $column,
                        $this->Filter('csv_import_field_value', $csv_row[$column_index], [$field, $settings, $column]),
                        $form->storage['importer_storage'],
                        $logs
                    );
                } catch (\Exception $e) {
                    $logs['error'][] = sprintf('Failed importing row #%d (Error: %s)', $offset, $e);
                    ++$rows_failed;
                    continue 2; // abort importing the current row
                }

                if (false === $field_value) {
                    // Unset column since mapped field is invalid, to stop further processing the column
                    unset($csv_columns[$column_index], $_mapped_fields[$column_name]);
                    continue;
                }

                // Skip if no value to import
                if (null === $field_value) {
                    continue;
                }

                if (is_array($field_value) && isset($values[$field_name])) {
                    foreach ($field_value as $field_index => $_field_value) {
                        if (!isset($values[$field_name][$field_index])
                            || !is_array($values[$field_name][$field_index])
                        ) {
                            $values[$field_name][$field_index] = $_field_value;
                        } else {
                            if (strlen($column)
                                && isset($_field_value[$column])
                            ) {
                                // Always use value returned for own column
                                $values[$field_name][$field_index][$column] = $_field_value[$column];
                            }
                            $values[$field_name][$field_index] += $_field_value;
                        }
                    }
                } else {
                    $values[$field_name] = $field_value;
                }
            }

            try {
                if (!empty($values[$bundle->entitytype_name . '_id'])
                    && ($entity = $this->Entity_Entity($bundle->entitytype_name, $values[$bundle->entitytype_name . '_id'], false))
                    && $entity->getBundleName() === $bundle->name
                ) {
                    $entity = $this->Entity_Save($entity, $values);
                    ++$rows_updated;
                } else {
                    if (empty($values['post_author'])) {
                        $values['post_author'] = 0;
                    }
                    $entity = $this->Entity_Save($bundle, $values);
                    ++$rows_imported;
                }

                // Notify
                $this->Action('csv_import_entity', array($bundle, $entity, $values, $importer_settings));
            } catch (\Exception $e) {
                $logs['error'][] = sprintf(__('CSV data on row number %d could not be imported: %s', 'directories'), $offset, $e->getMessage());
                ++$rows_failed;
            }

            --$limit;
            if ($limit === 0) break;
        }

        $time_took = microtime(true) - $start_time;
        $message = __('Importing...', 'directories');
        if ($offset - $start_row_num <= 1) {
            $message .= sprintf(
                ' %d of %d rows processed (%s seconds).',
                $start_row_num,
                $form->storage['total'],
                $time_took
            );
        } else {
            $message .= sprintf(
                ' %d-%d of %d rows processed (%s seconds).',
                $start_row_num,
                $offset,
                $form->storage['total'],
                $time_took
            );
        }

        $file = null;

        $form->storage['done'] = $offset;
        $form->storage['rows_imported'] = $rows_imported;
        $form->storage['rows_updated'] = $rows_updated;
        $form->storage['rows_failed'] = $rows_failed;

        if ($form->storage['done'] < $form->storage['total']) {
            $this->_isInProgress($context, $form->storage['done'], $form->storage['total'], $message, $logs);
            return;
        }

        // Notify each importer that import has completed
        foreach (array_keys($_mapped_fields) as $column_name) {
            $field_name = $_mapped_fields[$column_name][0];
            $column = (string)@$_mapped_fields[$column_name][1];
            if ((!$field = @$fields[$field_name])
                || (!$importer_name = @$importers_by_field_type[$field->getFieldType()])
                || (!$importer = $this->CSV_Importers_impl($importer_name, true))
            ) continue;

            $settings = isset($importer_settings[$field_name][$column_name]) ? $importer_settings[$field_name][$column_name] : [];
            $importer->csvImporterOnComplete($field, $settings, $column, $form->storage['importer_storage'], $logs);
        }

        // Store logs to storage for the _complete() method.
        $form->storage['logs'] = $logs;
    }

    protected function _complete(Context $context, array $formStorage)
    {
        $message = [];
        if (!empty($formStorage['rows_imported'])) {
            $message[] = $this->H(sprintf(
                __('%d item(s) created successfully.', 'directories'),
                $formStorage['rows_imported']
            ));
        }
        if (!empty($formStorage['rows_updated'])) {
            $message[] = $this->H(sprintf(
                __('%d item(s) updated successfully.', 'directories'),
                $formStorage['rows_updated']
            ));
        }
        if (!empty($formStorage['rows_failed'])) {
            $message[] = $this->H(sprintf(
                __('Failed importing %d item(s).', 'directories'),
                $formStorage['rows_failed']
            ));
        }
        if ($formStorage['values']['csv_file']['type'] === 'upload') {
            @unlink($formStorage['values']['csv_file']['upload']['saved_file_path']);
        }

        $this->_completeProgress($context, null, empty($message) ? null : implode(' ', $message), $formStorage['logs']);
    }

    protected function _getCsvFile(Entity\Model\Bundle $bundle, array $settings)
    {
        if (!isset($this->_csvFile)) {
            if (!ini_get('auto_detect_line_endings')) {
                ini_set('auto_detect_line_endings', true);
            }
            $delimiter = empty($settings['delimiter']) ? ',' : $settings['delimiter'];
            $enclosure = empty($settings['enclosure']) ? '"' : $settings['enclosure'];
            $file = $settings['type'] === 'upload' ? $settings['upload']['saved_file_path'] : $settings['existing'];
            $this->_csvFile = new \SplFileObject($file);
            $this->_csvFile->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            $this->_csvFile->setCsvControl($delimiter, $enclosure);
        }

        return $this->_csvFile;
    }

    protected function _getCsvRows(Entity\Model\Bundle $bundle, array $settings, $limit = 1)
    {
        $ret = [];
        $file = $this->_getCsvFile($bundle, $settings);
        for ($file->rewind(); $file->key() < $limit; $file->next()) {
            if ($file->key() === 0) {
                $ret[] = preg_replace('/^\xEF\xBB\xBF/', '', $file->current()); // remove BOM
            } else {
                $ret[] = $file->current();
            }
        }
        return $ret;
    }
}