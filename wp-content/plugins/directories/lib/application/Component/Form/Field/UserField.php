<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;

class UserField extends SelectField
{
    public function formFieldInit($name, array &$data, Form $form)
    {
        $data += array(
            '#select2' => true,
            '#select2_ajax' => true,
            '#select2_ajax_url' => (string)$this->_application->MainUrl(
                '/_drts/form/user',
                array(Request::PARAM_CONTENT_TYPE => 'json')
            ),
            '#select2_tags' => false,
            '#select2_item_image_key' => 'gravatar',
            '#select2_item_text_key' => 'username',
            '#default_options_callback' => array($this, '_getDefaultOptions'),
        );
        
        return parent::formFieldInit($name, $data, $form);        
    }

    public function _getDefaultOptions($defaultValue, array &$options)
    {
        $identities = $this->_application->UserIdentity($defaultValue);
        foreach ($identities as $identity) {
            $options[$identity->id] = $identity->name;
            //$id = $identity->id;
            //$text = $identity->name;
            //$defaultItems[] = array(
            //    'id' => $id,
            //    'text' => $this->_application->H($text),
            //    'username' => $identity->username,
            //    'gravatar' => $this->_application->GravatarUrl($identity->email, 24),
            //);
        }
    }
}