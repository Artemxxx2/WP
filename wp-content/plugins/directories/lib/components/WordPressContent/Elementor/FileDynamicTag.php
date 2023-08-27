<?php
namespace SabaiApps\Directories\Component\WordPressContent\Elementor;

use ElementorPro\Modules\DynamicTags\Module;

class FileDynamicTag extends ImageDynamicTag
{
    protected $_dynamicTagFieldTypes = ['wp_file'];

    public function get_name()
    {
        return 'drts-file';
    }

    public function get_title()
    {
        return 'Directories' . ' - ' .  __('File Field', 'directories');
    }

    public function get_categories()
    {
        return [
            Module::MEDIA_CATEGORY,
        ];
    }
}