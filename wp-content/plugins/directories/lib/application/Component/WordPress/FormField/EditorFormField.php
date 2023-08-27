<?php
namespace SabaiApps\Directories\Component\WordPress\FormField;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Request;

class EditorFormField extends Form\Field\AbstractField
{
    static protected $_exclude;
    
    public function formFieldInit($name, array &$data, Form\Form $form)
    {
        if (!isset(self::$_exclude)) {
            self::$_exclude = array('content');
            add_filter('mce_buttons', array(__CLASS__, 'mceButtonsFilter'), 99, 2);
            add_filter('quicktags_settings', array(__CLASS__, 'quickTagsSettingsFilter'), 99, 2);
        }
        $data['#id'] = $form->getFieldId($name);
        // Do not disable more tag from the post content field
        if ($name === 'post_content[0]') {
            self::$_exclude[] = $data['#id'] . '-editor';
        }
        
        if (empty($data['#rows'])) $data['#rows'] = get_option('default_post_edit_rows', 5);
    }
    
    public function formFieldSubmit(&$value, array &$data, Form\Form $form)
    {
        // Do not mess with markdown formatted text
        $data['#no_trim'] = true;

        if (false !== $validated = $this->_application->Form_Validate_text($form, $value, $data, true, true)) {
            $value = $validated;
        }
    }

    public function formFieldRender(array &$data, Form\Form $form)
    {
        $args = array(
            'wpautop' => true,
            'textarea_name' => $data['#name'],
            'textarea_rows' => $data['#rows'],
            'quicktags' => !Request::isAjax() && empty($data['#no_quicktags']),
            'tinymce' => !Request::isAjax() && empty($data['#no_tinymce']),
            'media_buttons' => empty($data['#no_media_buttons']) && current_user_can('upload_files'),
        );
        $editor_id = $data['#id'] . '-editor';
        ob_start();
        wp_editor(isset($data['#default_value']) ? $data['#default_value'] : '', $editor_id, $args);
        $this->_render(ob_get_clean(), $data, $form);
    }
    
    public static function mceButtonsFilter($buttons, $id)
    {
        return in_array($id, self::$_exclude) ? $buttons : array_diff($buttons, array('wp_more'));
    }
	
    public static function quickTagsSettingsFilter($settings, $id)
    {
        if (!in_array($id, self::$_exclude)) {
            $settings['buttons'] = str_replace(',more', '', $settings['buttons']);
        }
        return $settings;
    }
}