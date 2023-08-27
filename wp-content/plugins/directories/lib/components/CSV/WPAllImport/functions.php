<?php
function SabaiApps_Directories_Component_CSV_WPAllImport_import_attachment($fieldName, $postId, $attachmentId, $imagePath, $importOptions) {
    $meta_key = \SabaiApps\Directories\Component\CSV\WPAllImport\Importer::IMPORTED_ATTACHMENTS_META_KEY;
    if (!$attachment_ids = get_post_meta($postId, $meta_key, true)) {
        $attachment_ids = [];
    }
    $attachment_ids[$fieldName][$attachmentId] = $attachmentId;
    update_post_meta($postId, $meta_key, $attachment_ids);
}

function SabaiApps_Directories_Component_CSV_WPAllImport_create_function($fieldName) {
    $func = 'SabaiApps_Directories_Component_CSV_WPAllImport_import_attachment_' . $fieldName;
    if (!function_exists($func)) {
        eval('function ' . $func . '($postId, $attachmentId, $imagePath, $importOptions){
            SabaiApps_Directories_Component_CSV_WPAllImport_import_attachment("' . $fieldName . '", $postId, $attachmentId, $imagePath, $importOptions);
        }');
    }
    return $func;
}