<?php
namespace SabaiApps\Directories\Component\WordPress\Controller;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;

class UploadFile extends Form\Controller\AbstractUploadFile
{
    protected function _saveFile(array $file, array $token)
    {
        // Hide DB errors to prevent from returning invalid JSON
        $this->getDB()->getConnection()->getWpdb()->hide_errors();

        if (!defined('ALLOW_UNFILTERED_UPLOADS')) {
            define('ALLOW_UNFILTERED_UPLOADS', true); // lets admins upload files without an extension
        }
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        
        $id = media_handle_sideload($file, 0);
        if (is_wp_error($id)) {
            throw new Exception\RuntimeException($id->get_error_message());
        }
        
        if (!$src = wp_get_attachment_image_src($id, 'drts_thumbnail_scaled', true)) {
            // this should not happen
            throw new Exception\RuntimeException('No attachment image src');
        }
        
        unset($file['error']);
        
        return array(
            'id' => $id,
            'title' => get_the_title($id),
            'thumbnail' => $src[0],
            'size_hr' => size_format($file['size']),
            'extension' => $file['file_ext'],
            'url' => wp_get_attachment_url($id),
        ) + $file;
    }
}