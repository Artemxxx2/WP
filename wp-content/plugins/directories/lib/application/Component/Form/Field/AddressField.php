<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class AddressField extends FieldsetField
{
    public function formFieldInit($name, array &$data, Form $form)
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

    protected function _getFormFields(array $data, Form $form)
    {
        $title_or_description = '#title';
        $street_attr = isset($data['#attr_street']) ? $data['#attr_street'] : [];
        if (isset($data['#class_street'])) {
            $street_attr['class'] = $data['#class_street'];
        }
        $ret = array(
            'street' => array(
                $title_or_description => array_key_exists('#title_street', $data) ? $data['#title_street'] : __('Address Line 1', 'directories'),
                '#type' => 'textfield',
                '#attributes' => $street_attr,
                '#default_value' => @$data['#default_value']['street'],
                '#weight' => 1,
            ),
        );
        if (empty($data['#disable_street2'])) {
            $ret['street2'] = array(
                $title_or_description => array_key_exists('#title_street2', $data) ? $data['#title_street2'] : __('Address Line 2', 'directories'),
                '#type' => 'textfield',
                '#attributes' => isset($data['#class_street2']) ? array('class' => $data['#class_street2']) : [],
                '#required' => !empty($data['#require_street2']),
                '#default_value' => @$data['#default_value']['street2'],
                '#weight' => 2,
            );
        }
        if (@$data['#city_type'] !== 'disabled') {
            $attr = isset($data['#class_city']) ? array('class' => $data['#class_city']) : [];
            $ret['city'] = array(
                $title_or_description => array_key_exists('#title_city', $data) ? $data['#title_city'] : __('City', 'directories'),
                '#attributes' => isset($data['#city']) ? array('data-default-value' => $data['#city']) + $attr : $attr,
                '#weight' => 3,
            );
            if (!empty($data['#city_type'])) {
                $options = $data['#city_type'] !== 'select'
                    ? $this->_application->callHelper($data['#city_type'])
                    : @$data['#cities']['options'];
                $ret['city'] += array(
                    '#type' => 'select',
                    '#options' => array('' => '') + (array)$options,
                    '#default_value' => isset($data['#default_value']['city']) ? $data['#default_value']['city'] : @$data['#cities']['default'][0],
                    '#empty_value' => '',
                );
            } else {
                $ret['city'] += array(
                    '#type' => 'textfield',
                    '#default_value' => isset($data['#default_value']['city']) ? $data['#default_value']['city'] : @$data['#city'],
                );
            }
            if (@$data['#province_type'] !== 'disabled') {
                $ret['city']['#prefix'] = '<div class="' . DRTS_BS_PREFIX . 'form-row"><div class="' . DRTS_BS_PREFIX . 'col-sm-6">';
                $ret['city']['#suffix'] = '</div>';
            }
        } elseif (isset($data['#city'])) {
            // Add hidden field for geolocation search
            $ret['city'] = array(
                '#type' => 'markup',
                '#value' => sprintf('<input class="%1$s" type="hidden" value="%2$s" data-default-value="%2$s" />', isset($data['#class_city']) ? $data['#class_city'] : '', $this->_application->H($data['#city'])),
                '#weight' => 5,
            );
        }
        if (@$data['#province_type'] !== 'disabled') {
            $attr = isset($data['#class_province']) ? array('class' => $data['#class_province']) : [];
            $ret['province'] = array(
                $title_or_description => array_key_exists('#title_province', $data) ? $data['#title_province'] : __('State / Province / Region', 'directories'),
                '#attributes' => isset($data['#province']) ? array('data-default-value' => $data['#province']) + $attr : $attr,
                '#weight' => 4,
            );
            if (!empty($data['#province_type'])) {
                $options = $data['#province_type'] !== 'select'
                    ? $this->_application->callHelper($data['#province_type'])
                    : @$data['#provinces']['options'];
                $ret['province'] += array(
                    '#type' => 'select',
                    '#options' => array('' => '') + (array)$options,
                    '#default_value' => isset($data['#default_value']['province']) ? $data['#default_value']['province'] : @$data['#provinces']['default'][0],
                    '#empty_value' => '',
                );
            } else {
                $ret['province'] += array(
                    '#type' => 'textfield',
                    '#default_value' => isset($data['#default_value']['province']) ? $data['#default_value']['province'] : @$data['#province'],
                );
            }
            if (@$data['#city_type'] !== 'disabled') {
                $ret['province']['#prefix'] = '<div class="' . DRTS_BS_PREFIX . 'col-sm-6">';
                $ret['province']['#suffix'] = '</div></div>';
            }
        } elseif (isset($data['#province'])) {
            // Add hidden field for geolocation search
            $ret['province'] = array(
                '#type' => 'markup',
                '#value' => sprintf('<input class="%1$s-hidden" type="hidden" value="%2$s" data-default-value="%2$s" />', isset($data['#class_province']) ? $data['#class_province'] : '', $this->_application->H($data['#province'])),
                '#weight' => 5,
            );
        }
        if (@$data['#zip_type'] !== 'disabled') {
            $attr = isset($data['#class_zip']) ? array('class' => $data['#class_zip']) : [];
            $ret['zip'] = array(
                $title_or_description => array_key_exists('#title_zip', $data) ? $data['#title_zip'] : __('Postal / Zip Code', 'directories'),
                '#attributes' => isset($data['#zip']) ? array('data-default-value' => $data['#zip']) + $attr : $attr,
                '#weight' => 5,
            );
            if (!empty($data['#zip_type'])) {
                $options = $data['#zip_type'] !== 'select'
                    ? $this->_application->callHelper($data['#zip_type'])
                    : @$data['#zips']['options'];
                $ret['zip'] += array(
                    '#type' => 'select',
                    '#options' => array('' => '') + (array)$options,
                    '#default_value' => isset($data['#default_value']['zip']) ? $data['#default_value']['zip'] : @$data['#zips']['default'][0],
                    '#empty_value' => '',
                );
            } else {
                $ret['zip'] += array(
                    '#type' => 'textfield',
                    '#default_value' => isset($data['#default_value']['zip']) ? $data['#default_value']['zip'] : @$data['#zip'],
                );
            }
            if (@$data['#country_type'] !== 'disabled') {
                $ret['zip']['#prefix'] = '<div class="' . DRTS_BS_PREFIX . 'form-row"><div class="' . DRTS_BS_PREFIX . 'col-6">';
                $ret['zip']['#suffix'] = '</div>';
            }
        } elseif (isset($data['#zip'])) {
            // Add hidden field for geolocation search
            $ret['zip'] = array(
                '#type' => 'markup',
                '#value' => sprintf('<input class="%1$s-hidden" type="hidden" value="%2$s" data-default-value="%2$s" />', isset($data['#class_zip']) ? $data['#class_zip'] : '', $this->_application->H($data['#zip'])),
                '#weight' => 5,
            );
        }
        if (@$data['#country_type'] !== 'disabled') {
            $attr = isset($data['#class_country']) ? array('class' => $data['#class_country']) : [];
            $ret['country'] = array(
                $title_or_description => array_key_exists('#title_country', $data) ? $data['#title_country'] : __('Country', 'directories'),
                '#attributes' => isset($data['#country']) ? array('data-default-value' => $data['#country']) + $attr : $attr,
                '#weight' => 6,
            );
            if (!empty($data['#country_type'])) {
                if ($data['#country_type'] === 'select') {
                    $options = isset($data['#countries']['options']) ? $data['#countries']['options'] : $this->_application->System_Countries();
                } else {
                    $options = $this->_application->callHelper($data['#country_type']);
                }
                $ret['country'] += array(
                    '#type' => 'select',
                    '#options' => array('' => '') + (array)$options,
                    '#default_value' => isset($data['#default_value']['country']) ? $data['#default_value']['country'] : @$data['#countries']['default'][0],
                    '#empty_value' => '',
                );
            } else {
                $ret['country'] += array(
                    '#type' => 'textfield',
                    '#default_value' => isset($data['#default_value']['country']) ? $data['#default_value']['country'] : @$data['#country'],
                );
            }
            if (@$data['#zip_type'] !== 'disabled') {
                $ret['country']['#prefix'] = '<div class="' . DRTS_BS_PREFIX . 'col-6">';
                $ret['country']['#suffix'] = '</div></div>';
            }
        } elseif (isset($data['#country'])) {
            // Add hidden field for geolocation search
            $ret['country'] = array(
                '#type' => 'markup',
                '#value' => sprintf('<input class="%1$s-hidden" type="hidden" value="%2$s" data-default-value="%2$s" />', isset($data['#class_country']) ? $data['#class_country'] : '', $this->_application->H($data['#country'])),
                '#weight' => 6,
            );
        }

        return $ret;
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        parent::formFieldSubmit($value, $data, $form);

        if ($form->hasError()) return;

        $value = array_filter($value);

        foreach (array('city', 'province', 'zip', 'country') as $key) {
            if (isset($data['#' . $key . '_type']) && $data['#' . $key . '_type'] === 'disabled' && strlen((string)@$data['#' . $key])) {
                $value[$key] = $data['#' . $key];
            }
        }
    }
}
