<?php
namespace SabaiApps\Directories\Component\Map\FormField;

use SabaiApps\Directories\Component\Form;

class LatLngFormField extends Form\Field\FieldsetField
{
    public function formFieldInit($name, array &$data, Form\Form $form)
    {
        $data = array(
            '#tree' => true,
            '#children' => array(
                0 => $this->_getFormFields($data, $form),
            ),
            '#group' => true,
        ) + $data;
        
        parent::formFieldInit($name, $data, $form);
    }
    
    public function formFieldSubmit(&$value, array &$data, Form\Form $form)
    {
        parent::formFieldSubmit($value, $data, $form);
        
        if (empty($value['lat']) || empty($value['lng'])) {
            $value = null;
        }
    }
    
    protected function _getFormFields(array $data, Form\Form $form)
    {
        return array(
            'lat' => array(
                '#type' => 'textfield',
                '#default_value' => is_numeric($data['#default_value']['lat']) ? $data['#default_value']['lat'] : '',
                '#numeric' => true,
                '#title' => __('Latitude', 'directories'),
                '#attributes' => array('class' => 'drts-map-field-lat'),
                '#prefix' => '<div class="' . DRTS_BS_PREFIX . 'form-row"><div class="' . DRTS_BS_PREFIX . 'col-sm-6">',
                '#suffix' => '</div>',
            ),
            'lng' => array(
                '#type' => 'textfield',
                '#default_value' => is_numeric($data['#default_value']['lng']) ? $data['#default_value']['lng'] : '',
                '#numeric' => true,
                '#title' => __('Longitude', 'directories'),
                '#attributes' => array('class' => 'drts-map-field-lng'),
                '#prefix' => '<div class="' . DRTS_BS_PREFIX . 'col-sm-6">',
                '#suffix' => '</div></div>',
            ),
        );
    }
}
