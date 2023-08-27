<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;

class FileField extends AbstractField
{
    protected static $_elements = [];
    
    public function formFieldInit($name, array &$data, Form $form)
    {
        $data['#multiple'] = !empty($data['#multiple']);
        if ($data['#multiple']) {
            $data['#multiple_max'] = isset($data['#multiple_max']) ? intval($data['#multiple_max']) : 0;
        }
        $data['#id'] = $form->getFieldId($name);
      
        if ($data['#ajax_upload'] = !isset($data['#ajax_upload']) || !empty($data['#ajax_upload'])) {
            self::$_elements[$form->settings['#id']][$name] = $data;
            $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
        }
    }
    
    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (!empty($form->storage['form_upload_files'][$data['#id']])) {
            $selected = [];
            if (!empty($value['selected'])) {
                if (!empty($data['#multiple'])) {
                    $new_value = [];
                    foreach ($value['selected'] as $file_name) {
                        if (isset($form->storage['form_upload_files'][$data['#id']][$file_name])) {
                            $new_value[] = $form->storage['form_upload_files'][$data['#id']][$file_name];
                            $selected[] = $file_name;
                        }
                    }
                } else {
                    $new_value = null;
                    foreach ($value['selected'] as $file_name) {
                        if (isset($form->storage['form_upload_files'][$data['#id']][$file_name])) {
                            $new_value = $form->storage['form_upload_files'][$data['#id']][$file_name];
                            $selected[] = $file_name;
                        }
                    }
                }
                $value = $new_value;
            } else {        
                $value = empty($data['#multiple']) ? null : [];
            }
            // Delete uploaded files that were not selected
            foreach (array_keys($form->storage['form_upload_files'][$data['#id']]) as $file_name) {
                if (!in_array($file_name, $selected)) {
                    unset($form->storage['form_upload_files'][$data['#id']][$file_name]);
                    @unlink($form->storage['form_upload_files'][$data['#id']][$file_name]['saved_file_path']);
                }
            }
        } else {
            $value = empty($data['#multiple']) ? null : [];
        }

        // Fetch value from $_FILES
        $files = $this->_getSubmittedFiles($data['#name'] . '[files]');        

        if (empty($files)) {
            if (empty($value)
                && $form->isFieldRequired($data)
            ) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('File must be uploaded.', 'directories'), $data);
            }

            return;
        }

        // Init upload options
        $options = $this->_getUploadSettings($data);

        if (!empty($data['#multiple'])) {
            // Get maximum number of upload files
            $max_upload_num = $data['#multiple_max'];

            // Iterate through files data until the max limit is reached
            foreach (array_keys($files['name']) as $i) {
                $_file = array(
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'size' => $files['size'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                );

                try {
                    $_file = $this->_application->Upload($_file, $options);
                } catch (Exception\RuntimeException $e) {
                    if ($e->getCode() !== UPLOAD_ERR_NO_FILE) {
                        throw $e;
                    }

                    // No file, so just skip its process
                    continue;
                }

                $value[] = $_file;

                --$max_upload_num;
                if ($max_upload_num === 0) break;
            }

            if (empty($value) && $form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('File must be uploaded.', 'directories'), $data);
            }

        } else {
            try {
                $file = $this->_application->Upload($files, $options);
            } catch (Exception\RuntimeException $e) {
                if ($e->getCode() !== UPLOAD_ERR_NO_FILE) {
                    throw $e;
                }

                // No file
                if ($form->isFieldRequired($data)) {
                    $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('File must be uploaded.', 'directories'), $data);
                }

                return;
            }

            $value = $file;
        }
        
        foreach ((array)$value as $file) {
            if (isset($file['saved_file_path'])) {
                // Save the file path of uploaded file so that the file can be removed upon cleanup process in case the form submit failed
                $data['#_uploaded_files'][] = $file['saved_file_path'];
            }
        }
    }
    
    protected function _getSubmittedFiles($name)
    {
        if (empty($_FILES)) return [];

        if (isset($_FILES[$name])) return $_FILES[$name];

        if (false === $pos = strpos($name, '[')) return [];

        $base = substr($name, 0, $pos);
        $key = str_replace(array(']', '['), array('', '"]["'), substr($name, $pos + 1, -1));
        $code = array(sprintf('if (!isset($_FILES["%1$s"]["name"]["%2$s"]) || $_FILES["%1$s"]["error"]["%2$s"] === UPLOAD_ERR_NO_FILE) return [];', $base, $key));
        $code[] = '$file = [];';
        foreach (array('name', 'type', 'size', 'tmp_name', 'error') as $property) {
            $code[] = sprintf('$file["%1$s"] = $_FILES["%2$s"]["%1$s"]["%3$s"];', $property, $base, $key);
        }
        $code[] = 'return $file;';

        return eval(implode(PHP_EOL, $code));
    }

    protected function _getUploadSettings(array $data)
    {
        return array(
            'allowed_extensions' => !empty($data['#allowed_extensions']) ? $data['#allowed_extensions'] : null,
            'max_file_size' => !empty($data['#max_file_size']) ? $data['#max_file_size'] : null,
            'image_only' => isset($data['#allow_only_images']) ? $data['#allow_only_images'] : null,
            'max_image_width' => !empty($data['#max_image_width']) ? $data['#max_image_width'] : null,
            'max_image_height' => !empty($data['#max_image_height']) ? $data['#max_image_height'] : null,
            'min_image_width' => !empty($data['#min_image_width']) ? $data['#min_image_width'] : null,
            'min_image_height' => !empty($data['#min_image_height']) ? $data['#min_image_height'] : null,
            'upload_dir' => !empty($data['#upload_dir']) ? $data['#upload_dir'] : null,
            'upload_file_name_prefix' => !empty($data['#upload_file_name_prefix']) ? $data['#upload_file_name_prefix'] : null,
            'upload_file_name_max_length' => !empty($data['#upload_file_name_max_length']) ? $data['#upload_file_name_max_length'] : null,
            'upload_file_permission' => !empty($data['#upload_file_permission']) ? $data['#upload_file_permission'] : 0644,
            'hash_upload_file_name' => isset($data['#hash_upload_file_name']) ? $data['#hash_upload_file_name'] : true,
            'skip_mime_type_check' => isset($data['#skip_mime_type_check']) ? $data['#skip_mime_type_check'] : false,
        );
    }

    public function formFieldCleanup(array &$data, Form $form)
    {
        if ($form->isSubmitSuccess() // form submission did not fail
            || empty($data['#_uploaded_files']) // no new file upload
        ) return;

        // Form submission failed, so remove the files that have been uploaded in the process
        foreach ($data['#_uploaded_files'] as $file_path) @unlink($file_path);
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $form->settings['#attributes']['enctype'] = 'multipart/form-data';
        if (empty($data['#multiple'])
            || $data['#ajax_upload'] // UploadFile controller can accept file one by one only
        ) {
            $name = $data['#name'] . '[files]';
        } else {
            $name = $data['#name'] . '[files][]';
            $data['#attributes']['multiple'] = 'multiple';
        }
        // Any previously uploaded files?
        if (!empty($form->storage['form_upload_files'][$data['#id']])) {
            $selected = [];
            foreach ($form->storage['form_upload_files'][$data['#id']] as $file_name => $file) {
                $selected[] = sprintf(
                    '<span class="%1$sbadge %1$sbadge-secondary drts-form-file-uploaded">%2$s <i class="fas fa-times"></i><input name="%3$s[selected][]" type="hidden" value="%4$s" checked="checked" /></span>',
                    DRTS_BS_PREFIX,
                    $this->_application->H($file['name']),
                    $this->_application->H($data['#name']),
                    $this->_application->H($file_name)
                );
            } 
        }
        $html = sprintf(
            '<input type="file" name="%s"%s />',
            $this->_application->H($name),
            $this->_application->Attr($data['#attributes'])
        );
        if (!empty($data['#ajax_upload'])
            || empty($data['#upload_dir'])
        ) {
            $html = sprintf(
                '<span class="%1$sbtn %1$sbtn-sm %1$sbtn-outline-secondary drts-form-file-button">
    <i class="fas fa-folder-open"></i> <span>%2$s</span>
    %3$s
</span>',
                DRTS_BS_PREFIX,
                $this->_application->H(__('Choose File', 'directories')),
                $html
            );
        }
        if (!empty($selected)) {
            $html .= implode(PHP_EOL, $selected);
        }
        if (!empty($data['#ajax_upload'])) {
            // Append progress bar
            $html .= '<div class="' . DRTS_BS_PREFIX . 'progress ' . DRTS_BS_PREFIX . 'mt-2 ' . DRTS_BS_PREFIX . 'mb-0" style="display:none;">
    <div class="' . DRTS_BS_PREFIX . 'progress-bar ' . DRTS_BS_PREFIX . 'progress-bar-striped ' . DRTS_BS_PREFIX . 'progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
</div>';
        }
        $this->_render($html, $data, $form); 
    } 

    public function preRenderCallback($form)
    {
        if (empty($form->settings['#build_id'])) return; // requires form build ID

        $this->_application->Form_Scripts(array('file'));
        foreach (self::$_elements[$form->settings['#id']] as $name => $data) {
            $max_num_files = $data['#multiple'] ? (int)$data['#multiple_max'] : 1;
            $this->_application->Form_UploadToken(
                $form->settings['#build_id'],
                $data['#id'],
                array(
                    'max_num_files' => $max_num_files,
                    'upload_settings' => $this->_getUploadSettings($data),
                )
            );
            $form->settings['#js_ready'][] = sprintf('DRTS.Form.field.file("#%1$s", "%2$s", {
    uploadUrl: "%3$s",
    formData: {"drts_form_build_id": "%4$s", "drts_form_file_field_id": "%5$s"},
    maxNumFiles: %6$d,
    onMaxNumFileExceededError: function (num) {alert("%7$s");}
});',
                $this->_application->H($data['#id']),
                $this->_application->H($name),
                isset($data['#upload_url']) ? $data['#upload_url'] : $this->_application->MainUrl('/_drts/form/upload', array(Request::PARAM_CONTENT_TYPE => 'json')),
                $this->_application->H($form->settings['#build_id']),
                $this->_application->H($data['#id']),
                $max_num_files,
                sprintf(__('You may not upload more than %d files', 'directories'), $max_num_files)
            );
        }
    }
}