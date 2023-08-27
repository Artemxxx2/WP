<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;

class GridField extends AbstractField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (empty($data['#default_value'])) {
            $data['#default_value'] = [];
            return;
        }
        
        // Define columns
        $columns = [];
        foreach (array_keys($data['#children']) as $weight) {
            if (!is_int($weight)) continue;

            foreach (array_keys($data['#children'][$weight]) as $column_name) {
                $columns[$column_name] = $data['#children'][$weight][$column_name];
            }
        }

        // Add rows
        foreach (array_keys($data['#default_value']) as $i) {
            if (isset($data['#default_markup'][$i])) {
                $data['#default_value'][$i] += $data['#default_markup'][$i];
            }
            if (!isset($data['#row_settings'][$i])) {
                $data['#row_settings'][$i] = [];
            }
            foreach (array_keys($columns) as $column_name) {
                // Init column settings
                $column_settings = $columns[$column_name];
                if (isset($data['#row_settings'][$i][$column_name])) {
                    $column_settings = $data['#row_settings'][$i][$column_name] + $column_settings;
                }
                $column_settings['#default_value'] = null;
                foreach (array('#value', '#default_value', '#markup') as $data_key) {
                    if (isset($data[$data_key][$i][$column_name])) {
                        $column_settings[$data_key] = $data[$data_key][$i][$column_name];
                        break;
                    }
                }
                // Always prepend element name of the grid
                $column_settings['#tree'] = true;
                $column_settings['#tree_allow_override'] = false;
                if ($column_settings['#type'] !== 'radio' || empty($column_settings['#single_value'])) {
                    $column_settings['#name'] = sprintf('%s[%s][%s]', $name, $i, $column_name);
                } else {
                    // Only single value allowed for this column
                    $column_settings['#name'] = sprintf('%s[0][%s]', $name, $column_name);
                }
                $column_settings['#title'] = '';
                $form->initField($column_settings['#name'], $column_settings);
                // Update column settings
                $data['#row_settings'][$i][$column_name] = $column_settings;
            }
        }
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (!is_array($value)) {
            $value = [];
        }
        
        // Process child elements
        foreach (array_keys($data['#children']) as $weight) {
            if (!is_int($weight)) continue;

            foreach (array_keys($data['#children'][$weight]) as $ele_key) {
                $ele_data =& $data['#children'][$weight][$ele_key];
                if (!empty($ele_data['#disabled'])) {
                    continue;
                }
                foreach (array_keys($data['#default_value']) as $i) {
                    if (!isset($value[$i]) || !array_key_exists($ele_key, $value[$i])) {
                        $value[$i][$ele_key] = null;
                    }

                    // Custom settings for this column?
                    if (!empty($data['#row_settings'][$i][$ele_key])) {
                        $ele_data = array_merge($ele_data, $data['#row_settings'][$i][$ele_key]);
                    }

                    // Send form submit notification to the element.
                    try {
                        $this->_application->Form_Fields_impl($ele_data['#type'], $form->settings['#build_id'])->formFieldSubmit($value[$i][$ele_key], $ele_data, $form);
                    } catch (Exception\IException $e) {
                        // Catch any application level exception that might occur and display it as a form element error.
                        $form->setError($e->getMessage(), $ele_data);
                    }

                    // Any error?
                    if ($form->hasError($ele_data['#name'])) continue;

                    // Copy the value to be used in subsequent validations
                    $ele_value =& $value[$i][$ele_key];

                    if (empty($form->settings['#skip_validate'])) {
                        // Process custom validations if any
                        if (!empty($ele_data['#element_validate'])) {
                            foreach ($ele_data['#element_validate'] as $callback) {
                                try {
                                    $this->_application->CallUserFuncArray($callback, array($form, &$ele_value, $ele_data));
                                } catch (Exception\IException $e) {
                                    $form->setError($e->getMessage(), $ele_data);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Is one of the column specified as the value column?
        if (isset($data['#value_column'])) {
            foreach (array_keys($value) as $i) {
                $value[$i] = isset($value[$i][$data['#value_column']]) ? $value[$i][$data['#value_column']] : null; 
            }
        }
    }

    public function formFieldCleanup(array &$data, Form $form)
    {
        $form->cleanupChildFields($data);
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $class = DRTS_BS_PREFIX . 'table ' . DRTS_BS_PREFIX . 'my-0';
        if (isset($data['#size'])
            && $data['#size'] === 'sm'
        ) {
            $class .= ' ' . DRTS_BS_PREFIX . 'table-sm';
        }
        $table = ['<table class="' . $class . '"' . $this->_application->Attr($data['#attributes']) . '>'];
        $headers = $columns = [];
        foreach (array_keys($data['#children']) as $weight) {
            if (!is_int($weight)) continue;

            foreach (array_keys($data['#children'][$weight]) as $column_name) {
                ;
                if (isset($data['#children'][$weight][$column_name]['#title'])) {
                    $title = $data['#children'][$weight][$column_name]['#title'];
                    if (empty($data['#title_no_escape'])) {
                        $title = $this->_application->H($title);
                    }
                    if (isset($data['#column_attributes'][$column_name])) {
                        $headers[] = '<th data-column-name="' . $column_name .'"' . $this->_application->Attr($data['#column_attributes'][$column_name]) . '>' . $title . '</th>';
                    } else {
                        $headers[] = '<th data-column-name="' . $column_name .'">' . $title . '</th>';
                    }
                }
                $columns[] = $column_name;
            }
        }
        if (!empty($headers)) {
            $table[] = '<thead><tr>';
            $table[] = implode(PHP_EOL, $headers);
            $table[] = '</tr></thead>';
        }
        
        $table[] = '<tbody>';
        if (!empty($data['#default_value'])) {
            foreach (array_keys($data['#default_value']) as $row) {
                $attr = isset($data['#row_attributes'][$row]['@row']) ? $data['#row_attributes'][$row]['@row'] : [];
                $attr['data-row-id'] = $row;
                $table[] = '<tr' . $this->_application->Attr($attr) . '">';
                $row_settings = $data['#row_settings'][$row];
                foreach ($columns as $column_name) {
                    $attr = isset($data['#row_attributes'][$row][$column_name]) ? $data['#row_attributes'][$row][$column_name] : [];
                    if (isset($data['#row_attributes']['@all'][$column_name])) {
                        $attr += $data['#row_attributes']['@all'][$column_name];
                    }
                    $table[] = empty($attr) ? '<td>' : '<td' . $this->_application->Attr($attr) . '>';
                    if (isset($row_settings[$column_name])) {
                        try {
                            $form->renderField($row_settings[$column_name]);
                            $table[] = implode(PHP_EOL, $row_settings[$column_name]['#html']);
                        } catch (Exception\IException $e) {
                            $table[] = '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-danger">' . $e->getMessage() . '</div>';
                        }
                    }
                    $table[] = '</td>';
                }
                $table[] = '</tr>';
            }
        } else {
            $no_options_msg = isset($data['#no_options_msg']) ? $data['#no_options_msg'] : __('No entries found', 'directories');
            $table[] = '<tr><td colspan="' . count($columns) . '">' . $this->_application->H($no_options_msg) . '</td></tr>';
        }
        $table[] = '</tbody>';
        $table[] = '</table>';
        $table = implode(PHP_EOL, $table);
        if (!isset($data['#responsive']) || $data['#responsive']) {
            $table = '<div class="' . DRTS_BS_PREFIX . 'table-responsive-md">' . $table . '</div>';
        }
        $data['#description_top'] = true;
        
        $this->_render($table, $data, $form);
    }
}
