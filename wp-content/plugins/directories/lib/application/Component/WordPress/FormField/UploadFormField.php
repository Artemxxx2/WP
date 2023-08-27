<?php
namespace SabaiApps\Directories\Component\WordPress\FormField;

use SabaiApps\Directories\Component\Form;

class UploadFormField extends Form\Field\AbstractUploadField
{
    protected $_defaultRoute = '/_drts/wp/upload';
    
    protected function _getCurrentFiles(array $values)
    {
        $files = [];
        foreach ($values as $value) {
            if (!$src = wp_get_attachment_image_src($value, 'drts_thumbnail_scaled', true)) continue;
                    
            $file_path = get_attached_file($value);
            $ext_and_mime_type = wp_check_filetype(basename($file_path));
            $attr = ['src' => $src[0], 'alt' => ''];
            if ($ext_and_mime_type['ext'] === 'svg') {
                $attr['width'] = 150;
            } else {
                $attr['width'] = $src[1];
                $attr['height'] = $src[2];
            }
            $files[$value] = array(
                'name' => get_the_title($value),
                'icon' => sprintf('<img%s />', $this->_application->Attr($attr)),
                'extension' => $ext_and_mime_type['ext'],
                'url' => wp_get_attachment_url($value),
            );
            if ($filesize = @filesize($file_path)) {
                $files[$value]['size'] = size_format($filesize);
            }
        }
        return $files;
    }
    
    protected function _getDefaultValues(array $defaultValues)
    {
        $ret = [];
        foreach ($defaultValues as $value) {
            $ret[] = $value['attachment_id'];
        }
        return $ret;
    }
    
    protected function _updateFileTitles(array $titles)
    {
        foreach ($titles as $id => $title) {
            $res = wp_update_post(array('ID' => $id, 'post_title' => $title), true);
            if (is_wp_error($res)) {
                $this->_application->logError($res->get_error_message());
            }
        }
    }
    
    protected function _saveFiles(array $files)
    {
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $ret = [];
        foreach ($files as $file_uploaded) {
            if (isset($file_uploaded['saved_file_path'])) {
                $file_uploaded['tmp_name'] = $file_uploaded['saved_file_path']; // WP expects tmp_name
            }
            $id = media_handle_sideload($file_uploaded, 0);
            if (is_wp_error($id)) {
                $this->_application->logError($id->get_error_message());
                continue;
            }
            
            $ret[$id] = $id;
        }
        return $ret;
    }
    
    protected function _onSubmitFail(array $savedFileIds)
    {
        // Delete file data that have been created during submit
        if (!empty($savedFileIds)) {
            foreach ($savedFileIds as $id) {
                wp_delete_attachment($id, true);
            }
        }
    }
}