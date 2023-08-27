<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class UploadHelper
{
    protected static $_imageTypes = [
        'gif' => IMAGETYPE_GIF,
        'jpeg' => IMAGETYPE_JPEG,
        'jpg' => IMAGETYPE_JPEG,
        'jpe' => IMAGETYPE_JPEG,
        'png' => IMAGETYPE_PNG,
        'bmp' => IMAGETYPE_BMP,
        //'tif' => [IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM],
        //'tiff' => [IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM],
    ];

    protected static $_videoMimeTypes = ['video/mp4', 'video/mpeg', 'video/mpg',
        'video/x-ms-asf', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv', 'video/quicktime'];
    
    public function help(Application $application, array $file, array $options = [])
    {
        $default_options = [
            'allowed_extensions' => ['gif', 'jpeg', 'jpg', 'pdf', 'png', 'txt', 'zip', 'webp'],
            'max_file_size' => 1024 * 1024 * 5,  // 5MB
            'image_extensions' => ['gif', 'jpg', 'jpeg', 'png', 'webp', 'svg'],
            'image_only' => false,
            'max_image_width' => null,
            'max_image_height' => null,
            'min_image_width' => null,
            'min_image_height' => null,
            'upload_dir' => null,
            'upload_file_name_prefix' => '',
            'upload_file_name_max_length' => null,
            'upload_file_permission' => 0644,
            'hash_upload_file_name' => true,
            'skip_mime_type_check' => false,
            'upload' => true,
            'check_tmp_name' => true,
        ];
        $options += $default_options;
        
        
        // Initialize the file array
        $file += [
            'file_ext' => null,
            'is_image' => false,
            'is_video' => false,
            'width' => null,
            'height' => null,
            'saved_file_path' => null,
            'saved_file_name' => null,
            'error' => UPLOAD_ERR_OK,
        ];

        if ($file['error'] != UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error = 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = 'The uploaded file was only partially uploaded.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = 'No file was uploaded.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error = 'Missing a temporary folder.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error = 'Failed to write file to disk.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error = 'File upload stopped by PHP extension.';
                    break;
                default:
                    $error = 'Unknown error';
                    break;
            }

            throw new Exception\RuntimeException($error, $file['error']);
        }

        if ($options['check_tmp_name']) {
            if (empty($file['tmp_name'])
                || $file['tmp_name'] == 'none'
                || ($options['upload'] && !is_uploaded_file($file['tmp_name']))
            ) {
                throw new Exception\RuntimeException(__('No valid file was uploaded.', 'directories'), UPLOAD_ERR_NO_FILE);
            }
        }

        $this->_checkExtensionAndMimeType($application, $file, $options);
        $this->_checkMaxSize($application, $file, $options);

        // Some additional checks if image file
        if ($file['is_image']) {
            $image_size = $this->_checkMaxImageDimension($application, $file, $options);
            $file['width'] = $image_size[0];
            $file['height'] = $image_size[1];
        } else {
            if ($options['image_only']) {
                throw new Exception\RuntimeException(__('Only image files are allowed.', 'directories'));
            }
        }

        if ($options['upload']) {
            $this->_doUploadFile($application, $file, $options);
        }

        return $file;
    }
    
    protected function _doUploadFile(Application $application, &$file, array $options)
    {
        if (!isset($options['upload_dir'])) {
            return;
        }
            
        $application->ValidateDirectory($options['upload_dir'], true);

        if ($options['hash_upload_file_name']) {
            // Get a unique file name for new file in the upload directory
            if (!$file_name = self::getUniqueFileName($file, $options['upload_dir'], $options['upload_file_name_prefix'], $options['upload_file_name_max_length'])) {
                throw new Exception\RuntimeException(sprintf(__('Could not get unique file name for file %s.', 'directories'), $file['name']));
            }
        } else {
            $file_name = $file['name'];
            if (file_exists($options['upload_dir'] . '/' . $file_name)) {
                throw new Exception\RuntimeException(sprintf(__('File %s (%s) already exists.', 'directories'), $file_name, $options['upload_dir'] . '/' . $file_name));
            }
        }
        $file_path = $options['upload_dir'] . '/' . $file_name;
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception\RuntimeException(sprintf(__('Failed saving file %s to %s.', 'directories'), $file['tmp_name'], $file_path));
        }
        @chmod($file_path, $options['upload_file_permission']);
        $file['saved_file_name'] = $file_name;
        $file['saved_file_path'] = $file_path;
    }

    protected function _checkExtensionAndMimeType(Application $application, &$file, array $options)
    {
        $allowed_extensions = $options['image_only'] ? $options['image_extensions'] : $options['allowed_extensions'];

        // There must be allowed extensions defined for additional security
        if (empty($allowed_extensions)) {
            throw new Exception\RuntimeException('Allowed file extensions may not be empty.');
        }

        // Check file extension
        if ('' == $file['file_ext'] = self::getFileExtension($file['name'])) {
            throw new Exception\RuntimeException(sprintf(__('Invalid file extension: %s', 'directories'), $file['name']));
        }

        if (!in_array($file['file_ext'], $allowed_extensions)) {
            throw new Exception\RuntimeException(sprintf(__('Invalid file extension: %s', 'directories'), $file['file_ext']));
        }

        // Return if no associated mime type for the file extension
        $possible_mime_types = (array)self::getPossibleMimeTypes($file['file_ext']);
        $possible_mime_types[] = 'application/octet-stream';
        if (!$allowed_mime_types = $application->Filter('core_upload_mime_types', $possible_mime_types, [$file['file_ext']])) {
            throw new Exception\RuntimeException(__('No matching mime types for the file were found.', 'directories'));
        }

        // Check image type if the file is an image file
        $image_types = $this->_getImageTypes();
        if (isset($image_types[$file['file_ext']])
            && ($image_type = self::getImageType($file['tmp_name']))
            && in_array($image_type, (array)$image_types[$file['file_ext']])
        ) {
            $file['is_image'] = true;

            if (!in_array($file['file_ext'], $options['image_extensions'])) {
                throw new Exception\RuntimeException(sprintf(__('Invalid file extension: %s', 'directories'), $file['file_ext']));
            }
            
            if (!isset($file['type'])) {
                $file['type'] = 'image/' . $file['file_ext']; 
            }

            return;
        }
        
        if ($options['skip_mime_type_check']
            || (defined('DRTS_SKIP_MIME_TYPE_CHECK') && DRTS_SKIP_MIME_TYPE_CHECK)
        ) {
            return;
        }

        // Check if the file mime type corresponds with the allowed mime types for the file extension
        $file_mime = stripslashes($file['type']);
        if (function_exists('finfo_open')) {
            if ($finfo = @finfo_open(FILEINFO_MIME_TYPE)) {
                if (($file_finfo_mime = finfo_file($finfo, $file['tmp_name']))
                    && false === strpos($file_finfo_mime, ' ')
                ) {
                    $file_mime = $file_finfo_mime;
                }
                finfo_close($finfo);
            }
        }
        foreach ($allowed_mime_types as $allowed_mime_type) {
            if (false !== strpos($file_mime, $allowed_mime_type)) {
                $file['type'] = $allowed_mime_type;
                if (in_array($file['type'], self::$_videoMimeTypes)) {
                    $file['is_video'] = true;
                } elseif ($file['file_ext'] === 'svg') { // svg files come here since image type check does not work
                    if (!in_array($file['file_ext'], $options['image_extensions'])) continue;

                    $file['is_image'] = true;
                }

                return;
            }
        }

        // File extension does not match any of the expected file types.
        throw new Exception\RuntimeException(sprintf(
            __('Invalid file extension: %s', 'directories'),
            $file['file_ext'] . '(' . ($file['type'] !== $file_mime ? $file['type'] . ' -> ' . $file_mime : $file['type']) . ')'
        ));
    }

    protected function _getImageTypes()
    {
        $ret = self::$_imageTypes;
        if (defined('IMAGETYPE_WEBP')) {
            $ret['webp'] = IMAGETYPE_WEBP;
        }
        return $ret;
    }

    protected function _checkMaxSize(Application $application, $file, array $options)
    {
        if (empty($options['max_file_size'])) return;

        if ($file['size'] > $options['max_file_size']) {
            throw new Exception\RuntimeException(sprintf(
                __('File size may not exceed %s KB.', 'directories'),
                round($options['max_file_size'] / 1024, 1)
            ));
        }
    }

    protected function _checkMaxImageDimension(Application $application, $file, array $options)
    {
        if (empty($file['width']) || empty($file['height'])) {
            if ((!$image_size = @getimagesize($file['tmp_name']))
                && (!$image_size = $this->_maybeGetSvgImageSize($file))
            ) {
                throw new Exception\RuntimeException(__('Failed detecting image dimensions.', 'directories'));
            }
        } else {
            $image_size = [$file['width'], $file['height']];
        }

        if ((!empty($options['max_image_width']) && $image_size[0] > $options['max_image_width']) // check width
            || (!empty($options['max_image_height']) && $image_size[1] > $options['max_image_height']) // check height
        ) {
            throw new Exception\RuntimeException(sprintf(
                __('Image size must not be larger than W%d x H%d pixels.', 'directories'),
                $options['max_image_width'],
                $options['max_image_height']
            ));
        }

        if ((!empty($options['min_image_width']) && $image_size[0] < $options['min_image_width']) // check width
            || (!empty($options['min_image_height']) && $image_size[1] < $options['min_image_height']) // check height
        ) {
            throw new Exception\RuntimeException(sprintf(
                __('Image size must be larger than W%d x H%d pixels.', 'directories'),
                $options['min_image_width'],
                $options['min_image_height']
            ));
        }

        return $image_size;
    }

    protected function _maybeGetSvgImageSize(array $file)
    {
        if ($file['file_ext'] !== 'svg') return;

        if (!function_exists('simplexml_load_file')
            || (!$xml = simplexml_load_file($file['tmp_name']))
            || (!$xml_att = $xml->attributes())
        ) return;
            
        if (!empty($xml_att->width)
            && !empty($xml_att->height)
        ) {
            return [$xml_att->width, $xml_att->height];
        }
        
        if ($xml_att->viewBox === '') return;

        $view_box = $xml_att->viewBox;
        $view_box = str_replace(',', ' ', $view_box);
        $view_box = str_replace('  ', ' ', $view_box);
        $view_box = explode(' ', $view_box);
        if (count($view_box) !== 4) return;

        $min_x = (float)$view_box[0];
        $min_y = (float)$view_box[1];
        $max_x = (float)$view_box[2];
        $max_y = (float)$view_box[3];
        return [$max_x - $min_x, $max_y - $min_y];
    }

    public static function getFileExtension($fileName)
    {
        if (!$file_ext_pos = strrpos($fileName, '.')) return '';

        return strtolower(substr($fileName, $file_ext_pos + 1));
    }

    public static function getImageType($filePath)
    {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($filePath);
        }

        if ($image_size = @getimagesize($filePath)) {
            return $image_size[2];
        }

        return false;
    }

    public static function getUniqueFileName($file, $uploadDir, $fileNamePrefix = '', $fileNameMaxLength = null)
    {
        $file_ext = is_array($file) ? $file['file_ext'] : self::getFileExtension($file);
        $filename_prefix = (string)$fileNamePrefix;
        $filename_max_length = intval($fileNameMaxLength);
        do {
            $filename_hash = md5(uniqid(mt_rand(), true));
            // truncate hash if the file name length will exceed the max file name length
            if (!empty($filename_max_length)
                && ($hash_maxlength = $filename_max_length - (strlen($filename_prefix) + strlen($file_ext) + 1))
                && strlen($filename_hash) > $hash_maxlength
            ) {
                $filename_hash = substr($filename_hash, 0, $hash_maxlength);
            }
            $file_name = $filename_prefix . $filename_hash . '.' . $file_ext;
        } while (file_exists($uploadDir . '/' . $file_name));

        return $file_name;
    }

    public static function getPossibleMimeTypes($ext)
    {
        switch ($ext) {
            case 'hqx':
                return 'application/mac-binhex40';
            case 'csv':
                return [
                    'text/x-comma-separated-values', 'text/comma-separated-values', 'application/vnd.ms-excel',
                    'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain'
                ];
            case 'json':
                return ['application/json', 'text/plain'];
            case 'doc':
            case 'dot':
            case 'docm':
            case 'dotm':
            case 'word':
                return ['application/msword', 'application/vnd.ms-office'];
            case 'docx':
            case 'dotx':
                return ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/msword', 'application/x-zip'];
            case 'pdf':
                return ['application/pdf', 'application/x-download'];
            case 'ppd':
            case 'psd':
                return 'application/photoshop';
            case 'ai':
            case 'eps':
            case 'ps':
                return 'application/postscript';
            case 'smi':
            case 'smil':
                return 'application/smil';
            case 'xla':
            case 'xls':
            case 'xlt':
            case 'xlsx':
            case 'xltx':
            case 'xlsm':
                return ['application/excel', 'application/vnd.ms-excel', 'application/msexcel'];
            case 'ppt':
            case 'pptx':
                return ['application/powerpoint', 'application/vnd.ms-powerpoint'];
            case 'csh':
                return 'application/x-csh';
            case 'dcr':
            case 'dir':
            case 'dxr':
                return 'application/x-director';
            case 'spl':
                return 'application/x-futuresplash';
            case 'gtar':
                return 'application/x-gtar';
            case 'phps':
                return ['application/x-httpd-php', 'text/php', 'application/x-httpd-php-source'];
            case 'php':
            case 'php3':
            case 'php4':
            case 'phtml':
                return ['application/x-httpd-php', 'text/php'];
            case 'js':
                return 'application/x-javascript';
            case 'sh':
                return 'application/x-sh';
            case 'swf':
            case 'swc':
            case 'rf':
                return 'application/x-shockwave-flash';
            case 'sit':
                return 'application/x-stuffit';
                
            case 'tar':
                return 'application/x-tar';
            case 'gtar':
            case 'tgz':
                return ['application/x-gtar', 'application/x-gzip'];
            case 'tcl':
                return 'application/x-tcl';
            case 'xhtml':
            case 'xht':
                return 'application/xhtml+xml';
            case 'xhtml':
                return 'application/xml';
            case 'ent':
                return 'application/xml-external-parsed-entity';
            case 'dtd':
            case 'mod':
                return 'application/xml-dtd';
            case 'gz':
                return 'application/x-gzip';
            case 'zip':
                return ['application/x-zip', 'application/zip', 'application/x-zip-compressed'];
            case 'au':
            case 'snd':
                return 'audio/basic';
            case 'mid':
            case 'midi':
            case 'kar':
                return 'audio/midi';
            case 'mp1':
                return ['audio/mpeg', 'audio/mpg', 'audio/mp1'];
            case 'mp2':
                return ['audio/mpeg', 'audio/mpg', 'audio/mp2'];
            case 'mp3':
                return ['audio/mpeg', 'audio/mpg', 'audio/mp3'];
            case 'mpg':
                return ['audio/mpeg', 'audio/mpg'];
            case 'aif':
            case 'aiff':
                return 'audio/x-aiff';
            case 'm3u':
                return 'audio/x-mpegurl';
            case 'ram':
            case 'rm':
            case 'ra':
                return 'audio/x-pn-realaudio';
            case 'rpm':
                return 'audio/x-pn-realaudio-plugin';
            case 'wav':
                return ['audio/x-wav', 'audio/wav'];
            case 'bmp':
                return 'image/bmp';
            case 'gif':
                return 'image/gif';
            case 'iff':
                return 'image/iff';
            case 'jb2':
                return 'image/jb2';
            case 'jpeg':
            case 'jpg':
            case 'jpe':
                return ['image/jpeg', 'image/jpg'];
            case 'jpx':
                return 'image/jpx';
            case 'png':
                return 'image/png';
            case 'tiff':
            case 'tif':
                return ['image/tif', 'image/tiff'];
            case 'wbmp':
                return 'image/vnd.wap.wbmp';
            case 'pnm':
                return 'image/x-portable-anymap';
            case 'pbm':
                return 'image/x-portable-bitmap';
            case 'pgm':
                return 'image/x-portable-graymap';
            case 'ppm':
                return 'image/x-portable-pixmap';
            case 'xbm':
                return ['image/x-xbitmap', 'image/xbm'];
            case 'xpm':
                return 'image/x-xpixmap';
            case 'svg':
                return ['image/svg', 'image/svg+xml'];
            case 'ics':
            case 'ifb':
                return 'text/calendar';
            case 'css':
                return 'text/css';
            case 'html':
            case 'htm':
                return 'text/html';
            case 'asc':
            case 'txt':
                return 'text/plain';
            case 'rtf':
                return ['text/rtf', 'application/rtf'];          
            case 'sgml':
            case 'sgm':
                return 'text/x-sgml';
            case 'tsv':
                return 'text/tab-seperated-values';
            case 'wml':
                return 'text/vnd.wap.wml';
            case 'wmls':
                return 'text/vnd.wap.wmlscript';
            case 'xml':
                return ['text/xml', 'application/xml'];
            case 'xsl':
                return 'text/xsl';          
            case 'mp4':
                return ['video/mp4', 'video/mpeg4'];
            case 'mpeg':
            case 'mpg':
            case 'mpe':
                return ['video/mpeg', 'video/mpg'];
            case 'qt':
            case 'mov':
                return 'video/quicktime';
            case 'flv':
                return 'video/x-flv';             
            case 'asf':
            case 'asx':
                return 'video/x-ms-asf';
            case 'avi':
                return 'video/x-msvideo';          
            case 'wmv':
                return 'video/x-ms-wmv';
            case 'eml':
                return 'message/rfc822';
            case 'webp':
                return 'image/webp';
            default:
        }
    }
}