<?php
namespace SabaiApps\Directories\Component\CSV\Importer;

use SabaiApps\Directories\Component\Entity;

class WPImporter extends AbstractImporter implements IWpAllImportImporter
{
    public function csvImporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        switch ($this->_name) {
            case 'wp_post_parent':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Content item ID type', 'directories'),
                        '#description' => __('Select the type of data used to specify exported items.', 'directories'),
                        '#options' => array(
                            'id' => __('ID', 'directories'),
                            'slug' => __('Slug', 'directories'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                );
            case 'wp_image':
            case 'wp_file':
                $form = array(
                    'location' => array(
                        '#type' => 'select',
                        '#title' => __('File location', 'directories'),
                        '#options' => array(
                            '' => __('— Select —', 'directories'),
                            'upload' => __('Upload zip archive', 'directories'),
                            'local' => __('Local folder', 'directories'),
                            'url' => 'URL',
                            'media' => __('Media library (specify attachment ID)', 'directories'),
                        ),
                        '#options_description' => array(
                            'upload' => __('Upload a zip archive file containing all files.', 'directories'),
                            'local' => __('Specify the path to the directory where all files are located.', 'directories'),
                            'url' => __('Download from URL(s) specified in each CSV column.', 'directories'),
                        ),
                        '#default_value' => 'upload',
                    ),
                    'file' => array(
                        '#type' => 'file',
                        '#title' => __('Upload zip archive', 'directories'),
                        '#upload_dir' => get_temp_dir(),
                        '#allowed_extensions' => array('zip'),
                        '#states' => array(
                            'visible' => array(
                                sprintf('[name="%s[location]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'upload'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, array('location'))) === 'upload'; },
                    ),
                    'local' => array(
                        '#title' => __('Local folder', 'directories'),
                        '#type' => 'textfield',
                        '#states' => array(
                            'visible' => array(
                                sprintf('[name="%s[location]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'local'),
                            ),
                        ),
                        '#placeholder' => '/path/to/local/folder',
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, array('location'))) === 'local'; },
                    ),
                );
                $form += $this->_acceptMultipleValues($field, $enclosure, $parents);
                return $form;
        }
    }
    
    public function csvImporterDoImport(Entity\Model\Field $field, array $settings, $column, $value, &$formStorage, array &$logs)
    {
        switch ($this->_name) {
            case 'wp_post_content':
            case 'wp_term_description':
                return $value;
            case 'wp_post_status':
                return $value === 'published' ? 'publish' : $value; // "published" was used in Sabai Directory
            case 'wp_post_parent':
                if ($settings['type'] === 'slug') {
                    if (!isset($this->_parentBundle)) {
                        if ((!$parent_bundle_name = $field->Bundle->info['parent'])
                            || (!$this->_parentBundle = $this->_application->Entity_Bundle($parent_bundle_name))
                        ) return false;
                    }
                    
                    if (!$entity = $this->_application->Entity_Types_impl($this->_parentBundle->entitytype_name)
                        ->entityTypeEntityBySlug($this->_parentBundle->name, $value)
                    ) return;
                    
                    return $entity->getId();
                } else {
                    return $value;
                }   
            case 'wp_image':
            case 'wp_file':
                if (!empty($settings['_multiple'])) {
                    if (!$values = explode($settings['_separator'], $value)) return;
                } else {
                    $values = array($value);
                }

                if (!isset($formStorage['wp_attachments'])) {
                    $formStorage['wp_attachments'] = [];
                }

                if ($settings['location'] === 'media') {
                    foreach (array_keys($values) as $key) {
                        // Make sure attachment exists
                        if (!get_post_status($values[$key])) {
                            unset($values[$key]);
                        }
                    }
                    $formStorage['wp_attachments'] = $values;
                    return $values;
                }

                if ($settings['location'] === 'url') {
                    return $this->_saveFiles(
                        $settings,
                        $values,
                        null,
                        $formStorage['wp_attachments'],
                        $this->_name === 'wp_image',
                        $logs
                    );
                }
        
                $field_name = $field->getFieldName();
                if (!isset($formStorage[$field_name]['file_dir'])) {
                    if ($upload_dir = $this->_getUploadDir($settings, $logs)) {
                        $formStorage[$field_name]['file_dir'] = $upload_dir;
                    } else {
                        $formStorage[$field_name]['file_dir'] = false;
                    }
                }
                if (!$formStorage[$field_name]['file_dir']) return;
            
                return $this->_saveFiles(
                    $settings,
                    $values,
                    $formStorage[$field_name]['file_dir'],
                    $formStorage['wp_attachments'],
                    $this->_name === 'wp_image',
                    $logs
                );
        }
    }

    public function csvImporterOnComplete(Entity\Model\Field $field, array $settings, $column, &$formStorage, array &$logs)
    {
        @unlink($settings['file']['saved_file_path']);
    }
    
    protected function _getUploadDir(array $settings, array &$logs)
    {   
        if ($settings['location'] === 'local') {
            if (@is_dir($settings['local'])) {
                return rtrim($settings['local'], '/');
            }
            $logs['error'][] = 'Local folder was not found: ' . $settings['local'];
            return;
        }
            
        if ($settings['location'] !== 'upload') return;        
        
        $ret = null;
        if ($archive = @$settings['file']['saved_file_path']) {
            $this->_application->getPlatform()->unzip($archive, dirname($archive));
            $possible_file_dir = array(
                dirname($archive) . '/' . substr($settings['file']['name'], 0, -1 * (strlen($settings['file']['file_ext']) + 1)), // check sub directory with folder name
                dirname($archive)
            );
            foreach ($possible_file_dir as $file_dir) {
                if (@is_dir($file_dir)) {
                    $ret = $file_dir;
                    break;
                }
            }
        }
        
        return rtrim($ret, '/');
    }

    protected function _saveFiles(array $settings, array $values, $tmpDir, &$attachments, $isImage, array &$logs)
    {
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $ret = [];
        foreach ($values as $value) {
            if (strpos($value, '|')
                && ($_value = explode('|', $value))
            ) {
                $value = $_value[0];
                $name = $_value[1];
            } else {
                $name = $value;
            }

            if (isset($attachments[$value])) {
                $ret[$value] = $attachments[$value];
                continue;
            }

            if (!empty($tmpDir)) {
                $file_path = $tmpDir . '/' . $value;
                if (!file_exists($file_path)) {
                    $logs['error'][] = 'File was not found: ' . $file_path;

                    continue;
                }
            } else {
                if (strpos($value, 'https://') === 0
                    || strpos($value, 'http://') === 0
                ) {
                    try {
                        $file_path = $this->_application->getPlatform()->downloadUrl($value);
                    } catch (\Exception $e) {
                        $logs['error'][] = $e->getMessage();
                        continue;
                    }
                }
            }

            // Append extension if image and no extension
            if ($isImage) {
                if ((!$pos = strrpos($value, '.'))
                    || !in_array(substr($value, $pos), ['.gif', '.png', '.jpeg', '.jpg', '.svg'])
                ) {
                    if ((!$image_size = @getimagesize($file_path))
                        || empty($image_size[2])
                        || !in_array($image_size[2], [IMG_GIF, IMG_PNG, IMG_JPEG])
                    ) {
                        $logs['error'][] = $value . ': Invalid image';
                        continue;
                    }

                    switch ($image_size[2]) {
                        case IMG_GIF:
                            $name .= '.gif';
                            break;
                        case IMG_PNG:
                            $name .= '.png';
                            break;
                        default:
                            $name .= '.jpeg';
                    }
                }
            }

            $file = array(
                'tmp_name' => $file_path,
                'name' => $name,
            );
            $id = media_handle_sideload($file, 0);
            if (is_wp_error($id)) {
                $logs['error'][] = $id->get_error_message();
                continue;
            }

            $attachments[$value] = $ret[$value] = $id;
        }

        return array_values($ret);
    }

    public function csvWpAllImportImporterAddField(\RapidAddon $addon, Entity\Model\Field $field)
    {
        switch ($this->_name) {
            case 'wp_image':
                if ($field->getFieldMaxNumItems() !== 1) {
                    include_once dirname(__DIR__) . '/WPAllImport/functions.php';
                    $func_name = SabaiApps_Directories_Component_CSV_WPAllImport_create_function($field->getFieldName());
                    $addon->import_images($func_name, $field->Bundle->getLabel('singular') . ' - ' . $field->getFieldLabel());
                } else {
                    $addon->add_title($field->getFieldLabel());
                    $addon->add_field($field->getFieldName(), '', 'image');
                }
                return true;
            case 'wp_file':
                if ($field->getFieldMaxNumItems() !== 1) {
                    include_once dirname(__DIR__) . '/WPAllImport/functions.php';
                    $func_name = SabaiApps_Directories_Component_CSV_WPAllImport_create_function($field->getFieldName());
                    $addon->import_files($func_name, $field->Bundle->getLabel('singular') . ' - ' . $field->getFieldLabel());
                } else {
                    $addon->add_title($field->getFieldLabel());
                    $addon->add_field($field->getFieldName(), '', 'file');
                }
                return true;
        }
    }

    public function csvWpAllImportImporterDoImport(\RapidAddon $addon, Entity\Model\Field $field, array $data, $options, array $article)
    {
        switch ($this->_name) {
            case 'wp_image':
            case 'wp_file':
                if (empty($data[$field->getFieldName()]['attachment_id'])) return;

                return [$data[$field->getFieldName()]['attachment_id']];
        }
    }
}