<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;

abstract class AbstractUploadField extends FieldsetField
{
    protected $_defaultRoute;
    protected static $_uploadFields = [];

    public function formFieldInit($name, array &$data, Form $form)
    {
        // Add file upload field
        $allowed_extensions = isset($data['#allowed_extensions']) ? $data['#allowed_extensions'] : array('jpeg', 'jpg', 'gif', 'png', 'txt', 'pdf', 'zip');
        $max_file_size = $this->_application->System_Util_strToBytes(!empty($data['#max_file_size']) ? $data['#max_file_size'] : (($size = @ini_get('upload_max_filesize')) ? $size : '2M'));
        $max_file_size_str = $this->_application->System_Util_bytesToStr($max_file_size);
        $allow_only_images = !empty($data['#allow_only_images']);

        $file_settings = $data;
        $file_settings['#type'] = 'file';
        $file_settings['#title'] = '';
        $descriptions = array(
            !empty($data['#max_num_files'])
                ? sprintf(__('Max number of files %d, maximum file size %s.', 'directories'), $data['#max_num_files'], $max_file_size_str)
                : sprintf(__('Max file size %s.', 'directories'), $max_file_size_str),
            sprintf(
                __('Supported file formats: %s', 'directories'),
                $allow_only_images ? 'gif jpeg jpg png' : implode(' ', $allowed_extensions)
            ),
        );
        $file_settings['#description'] = implode(' ', $descriptions);
        $file_settings['#upload_dir'] = isset($data['#upload_dir']) ? $data['#upload_dir'] : null; // file is uploaded by the storage plugin
        $file_settings['#max_file_size'] = $max_file_size;
        $file_settings['#allowed_extensions'] = $allowed_extensions;
        $file_settings['#allowed_only_images'] = $allow_only_images;
        $file_settings['#class'] = 'drts-form-upload';
        $file_settings['#attributes']['id'] = $form->getFieldId($name) . '-file';
        $file_settings['#ajax_upload'] = false;
        unset($file_settings['#horizontal']);

        // Define element settings
        $data = array(
            '#tree' => true,
            '#type' => $data['#type'],
            '#name' => $name,
            '#title' => (string)@$data['#title'],
            '#description' => @$data['#description'],
            '#required' => $data['#required'],
            '#multiple' => !empty($data['#multiple']),
            '#class' => 'drts-form-upload-container ' . $data['#class'],
            '#children' => [],
            '#max_num_files' => (int)@$file_settings['#max_num_files'],
            '#horizontal' => !empty($data['#horizontal']),
            '#group' => true,
            '#ajax_upload' => !isset($data['#ajax_upload']) || !empty($data['##ajax_upload']),
        ) + $form->defaultFieldSettings();

        // Assign #states to the parent form element
        if (isset($file_settings['#states'])) {
            $data['#states'] = $file_settings['#states'];
            unset($file_settings['#states']);
        }

        // Add current file selection fields
        $current_file_options = $data['#_current_files'] = [];
        $row_attr = isset($file_settings['#row_attributes']) ? $file_settings['#row_attributes'] : [];
        if (!empty($file_settings['#default_value'])
            && is_array($file_settings['#default_value'])
        ) {
            $default_values = $file_settings['#default_value'];
            if (isset($default_values['current'])) {
                // values from previous submit
                $default_values = array_keys($default_values['current']);
            } else {
                unset($default_values['upload']);
                if (!empty($default_values)) {
                    $default_values = $this->_getDefaultValues($default_values);
                }
            }

            if (!empty($default_values)) {
                foreach ($this->_getCurrentFiles($default_values) as $file_id => $file) {
                    $current_file_options[$file_id] = $file;
                    $data['#_current_files'][$file_id] = $file;
                    if (!isset($row_attr[$file_id]['@row']['class'])) {
                        $row_attr[$file_id]['@row']['class'] = 'drts-form-upload-row';
                    } else {
                        $row_attr[$file_id]['@row']['class'] .= ' drts-form-upload-row';
                    }
                }
                // Reorder options as it was stored
                if (!empty($current_file_options)) {
                    $_current_file_options = [];
                    foreach ($default_values as $file_id) {
                        if (isset($current_file_options[$file_id])) {
                            $_current_file_options[$file_id] = $current_file_options[$file_id];
                        }
                    }
                    $current_file_options = $_current_file_options;
                }
            }
        }
        $current_file_element = array(
            '#type' => 'grid',
            '#size' => 'sm',
            '#class' => 'drts-form-upload-current drts-data-table',
            '#empty_text' => isset($file_settings['#empty_text']) ? $file_settings['#empty_text'] : __('There are currently no files uploaded.', 'directories'),
            '#column_attributes' => array(
                'name' => array('style' => 'width:50%;'),
                'icon' => array('style' => 'width:20%;text-align:center;'),
                'size' => array('style' => 'width:15%;'),
                'check' => array('style' => 'text-align:center;'),
            ),
            '#attributes' => array('style' => 'margin-bottom:0;'),
            '#row_attributes' => $row_attr,
            '#disable_template_override' => true,
        );
        $current_file_element['#children'][0] = array(
            'icon' => array(
                '#type' => 'markup',
                '#title' => '',
            ) + $form->defaultFieldSettings(),
            'name' => array(
                '#type' => 'textfield',
                '#title' => __('File Name', 'directories'),
            ) + $form->defaultFieldSettings(),
            'size' => array(
                '#type' => 'item',
                '#title' => __('File Size', 'directories'),
            ) + $form->defaultFieldSettings(),
            'check' => array(
                '#type' => 'hidden',
                '#title' => '',
                '#switch' => false,
                '#render_hidden_inline' => true,
                '#prefix' => '<a href="#" class="drts-form-upload-row-delete ' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-sm ' . DRTS_BS_PREFIX . 'btn-danger"><i class="fas fa-times"></i></a>',
            ) + $form->defaultFieldSettings(),
        );
        if (!empty($current_file_options)) {
            foreach ($current_file_options as $current_file_id => $current_file_option) {
                $current_file_element['#default_value'][$current_file_id] = array(
                    'check' => true,
                    'icon' => '<a target="_blank" href="' . $current_file_option['url'] . '">' .  $current_file_option['icon'] . '</a>',
                    'name' => $current_file_option['name'],
                    'size' => $current_file_option['size'],
                );
            }
        }
        $data['#children'][0]['current'] = $current_file_element;

        // Add upload field if not explicitly disabled
        if (!isset($data['#upload']) || $data['#upload'] !== false) {
            $data['#children'][0]['upload'] = $file_settings;

            if (!isset(self::$_uploadFields[$form->settings['#id']])) {
                self::$_uploadFields[$form->settings['#id']] = [];
            }
            self::$_uploadFields[$form->settings['#id']][$file_settings['#attributes']['id']] = array(
                'name' => $name,
                'route' => isset($file_settings['#upload_route']) ? $file_settings['#upload_route'] : $this->_defaultRoute,
                'uploader_settings' => array(
                    'allowed_extensions' => $allowed_extensions,
                    'max_file_size' => $max_file_size,
                    'image_only' => $allow_only_images,
                    'min_image_width' => isset($file_settings['#min_image_width']) ? $file_settings['#min_image_width'] : null,
                    'min_image_height' => isset($file_settings['#min_image_height']) ? $file_settings['#min_image_height'] : null,
                    'max_image_width' => isset($file_settings['#max_image_width']) ? $file_settings['#max_image_width'] : null,
                    'max_image_height' => isset($file_settings['#max_image_height']) ? $file_settings['#max_image_height'] : null,
                    'max_num_files' => $data['#max_num_files'],
                    'upload' => false,
                ),
                'sortable' => !empty($file_settings['#sortable']),
                'upload_sequential' => !empty($file_settings['#upload_sequential']),
            );

            $form->settings['#pre_render'][__CLASS__] = array(array($this, 'preRenderCallback'), [!empty($data['#ajax_upload'])]);
        }

        $data['#children'][0]['progress'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="' . DRTS_BS_PREFIX . 'progress ' . DRTS_BS_PREFIX . 'mt-1" style="display:none;">'
                . '<div class="' . DRTS_BS_PREFIX . 'progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">'
                . '</div></div>'
        );

        parent::formFieldInit($name, $data, $form);
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        $_values = $data['#_saved_file_ids'] = $data['#_new_file_ids'] = [];

        // File uploading enabled?
        if (isset($data['#children'][0]['upload'])) {
            $file_settings =& $data['#children'][0]['upload'];
            if (!empty($value['current'])) $file_settings['#required'] = false;
            // Validate uploaded file
            $this->_application->Form_Fields_impl('file', $form->settings['#build_id'])->formFieldSubmit(
                $value['upload'],
                $file_settings,
                $form
            );
            if ($form->hasError($data['#name'] . '[upload]')) {
                return;
            }

            if (empty($form->settings['#skip_validate'])) {
                // Process custom validations if any
                foreach ($file_settings['#element_validate'] as $callback) {
                    try {
                        $this->_application->CallUserFuncArray($callback, array($form, &$value['upload'], $file_settings));
                    } catch (Exception\IException $e) {
                        $form->setError($e->getMessage(), $data['#name'] . '[current]');
                    }
                }
            }

            // Save any newly uploaded file
            if (!empty($value['upload'])) {
                if (!$data['#multiple']) {
                    $value['upload'] = array($value['upload']);
                }
                $data['#_saved_file_ids'] = $this->_saveFiles($value['upload']);
            }
        }

        if ($data['#multiple'] || empty($data['#_saved_file_ids'])) {
            // Any current file selected?
            if (!empty($value['current'])) {
                $new_titles = [];
                foreach ($value['current'] as $file_id => $file_info) {
                    if (empty($file_info['check'][0])) {
                        continue;
                    }
                    $_values[$file_id] = $file_id;
                    if (!isset($data['#_current_files'][$file_id])) {
                        // File uploaded via Ajax
                        $data['#_new_file_ids'][] = $file_id;
                        $new_titles[$file_id] = $file_info['name'];
                    } else {
                        if ($data['#_current_files'][$file_id]['name'] !== $file_info['name']) {
                            // Update file title
                            $new_titles[$file_id] = $file_info['name'];
                        }
                    }

                    if (!$data['#multiple']) break;
                }

                if (!empty($new_titles)) {
                    $this->_updateFileTitles($new_titles);
                }
            }
        }

        if (!empty($data['#_saved_file_ids'])) {
            foreach ($data['#_saved_file_ids'] as $file_id) {
                $_values[$file_id] = $file_id;
            }
        }

        $value = [];
        if (!empty($_values)) {
            if (empty($data['#multiple'])) {
                $_values = array_slice($_values, 0, 1, true);
            }
            foreach ($_values as $file_id) {
                $value[] = $file_id;
            }
        }

        if (empty($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('File must be uploaded.', 'directories'), $data);
            }
        } else {
            if ($data['#max_num_files'] && count($value) > $data['#max_num_files']) {
                $form->setError(sprintf(__('You may not upload more than %d files.', 'directories'), $data['#max_num_files']), $data['#name'] . '[current]');
            }
        }
    }

    public function formFieldCleanup(array &$data, Form $form)
    {
        if ($form->isSubmitSuccess()) {
            // Form was successfully submitted
            $this->_onSubmitSuccess($data['#_new_file_ids']);
        } else {
            // Form submit failed, we need to remove files that have been uploaded during the upload process
            $this->_onSubmitFail($data['#_saved_file_ids']);
        }

        // Remove the current upload token and files associated with the token (files uploaded via Ajax)
        if ($token = $this->_application->Form_UploadToken($form->settings['#build_id'], $data['#name'] . '[upload]')) {
            $this->_onCleanup($token, isset($data['#_new_file_ids']) ? $data['#_new_file_ids'] : []);
        }

        $data['#_saved_file_ids'] = $data['#_new_file_ids'] = [];
    }

    public function preRenderCallback(Form $form, $enableUpload)
    {
        $this->_application->Form_Scripts_file();

        $js = [];
        $upload_token_id = empty($form->settings['#build_id']) ? $form->settings['#id'] : $form->settings['#build_id'];
        foreach (self::$_uploadFields[$form->settings['#id']] as $upload_id => $upload) {
            $this->_application->Form_UploadToken(
                $upload_token_id,
                $file_field_id = $upload['name'] . '[upload]',
                array(
                    'max_num_files' => $upload['uploader_settings']['max_num_files'],
                    'upload_settings' => $upload['uploader_settings'],
                )
            );
            $js[] = sprintf('DRTS.Form.field.upload({
    selector: "#%1$s",
    url: "%2$s",
    inputName: "%3$s",
    formData: {
        "drts_form_build_id": "%4$s", 
        "drts_form_file_field_id": "%5$s",
        "%9$s": "json" 
    },
    maxNumFiles: %6$d,
    sortable: %7$s,
    maxNumFileExceededError: "%8$s",
    deleteConfirm: "%10$s",
    upload: %11$s
});',
                $this->_application->H($upload_id),
                $this->_application->MainUrl($upload['route'], array(Request::PARAM_CONTENT_TYPE => 'json')),
                $this->_application->H($upload['name']),
                $this->_application->H($upload_token_id),
                $file_field_id,
                $upload['uploader_settings']['max_num_files'],
                $upload['sortable'] ? 'true' : 'false',
                $this->_application->H(sprintf(__('You may not upload more than %d files', 'directories'), $upload['uploader_settings']['max_num_files'])),
                Request::PARAM_CONTENT_TYPE,
                $this->_application->H(__('Are you sure?', 'directories')),
                empty($enableUpload) ? 'false' : 'true'
            );
        }

        if (empty($js)) return;

        $form->settings['#js_ready'][] = implode(PHP_EOL, $js);
    }

    protected function _onSubmitSuccess(array $newFileIds){}
    protected function _onSubmitFail(array $savedFileIds){}
    protected function _onCleanup(array $token, array $newFileIds){}

    protected function _getDefaultValues(array $defaultValues)
    {
        return $defaultValues;
    }

    abstract protected function _getCurrentFiles(array $values);
    abstract protected function _updateFileTitles(array $titles);
    abstract protected function _saveFiles(array $files);
}
