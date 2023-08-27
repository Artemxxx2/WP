<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class TextField extends AbstractField
{    
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (isset($data['#separator'])) {
            // value is an array, so it must be converted to a string
            if (isset($data['#default_value']) && is_array($data['#default_value'])) {
                $data['#default_value'] = implode($data['#separator'], $data['#default_value']);
            }
            if (isset($data['#value']) && is_array($data['#value'])) {
                $data['#value'] = implode($data['#separator'], $data['#value']);
            }
        } else {
            if (isset($data['#max_length'])
                && is_numeric($data['#max_length'])
            ) {
                if (!empty($data['#max_length'])) {
                    $data['#attributes']['maxlength'] = (int)$data['#max_length'];
                }
            } else {
                $data['#attributes']['maxlength'] = 255;
            }
        }
        
        if (!empty($data['#states']['slugify'])) {
            $data['#slugify'] = true;
        }

        if (!empty($data['#mask']) || !empty($data['#slugify'])) {
            if (!empty($data['#mask'])) {
                if (!isset($data['#class'])) {
                    $data['#class'] = 'drts-form-type-textfield-mask';
                } else {
                    $data['#class'] .= ' drts-form-type-textfield-mask';
                }
                $data['#attributes']['data-mask'] = $data['#mask'];
                if (!isset($data['#placeholder'])) {
                    $data['#placeholder'] = $data['#mask'];
                }
            }
            if (!empty($data['#slugify'])) {
                if (isset($data['#attributes']['class'])) {
                    $data['#attributes']['class'] .= ' drts-form-type-textfield-slugify';
                } else {
                    $data['#attributes']['class'] = 'drts-form-type-textfield-slugify';
                }
            }
            
            if (!isset($form->settings['#pre_render'][__CLASS__])) {
                $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
            }
        }

        if (!empty($data['#add_clear'])) {
            if (isset($data['#attributes']['class'])) {
                $data['#attributes']['class'] .= ' drts-form-type-textfield-with-clear';
            } else {
                $data['#attributes']['class'] = 'drts-form-type-textfield-with-clear';
            }
        }

        // Auto populate field?
        if (!isset($data['#default_value'])
            || (is_string($data['#default_value']) && !strlen($data['#default_value']) && !isset($form->settings['#entity']))
        ) {
            if (isset($data['#autopopulate'])) {
                switch ($data['#autopopulate']) {
                    case 'email':
                        $data['#default_value'] = $this->_application->getUser()->email;
                        break;
                    case 'url':
                        $data['#default_value'] = $this->_application->getUser()->url;
                        break;
                    case 'username':
                        $data['#default_value'] = $this->_application->getUser()->username;
                        break;
                    case 'name':
                        $data['#default_value'] = $this->_application->getUser()->name;
                        break;
                }
            }
        }
        
        if (!isset($data['#attributes']['placeholder'])) {
            if (!isset($data['#placeholder'])) {
                if ($data['#type'] === 'url'
                    || (isset($data['#char_validation']) && $data['#char_validation'] === 'url')
                ) {
                    $data['#attributes']['placeholder'] = 'https://';
                }
            } else {
                $data['#attributes']['placeholder'] = $data['#placeholder'];
            }
        }
    }
    
    public function validateEmail(Form $form, &$value, $element)
    {
        list(, $domain) = explode('@', $value);
        if (!$domain || !checkdnsrr($domain, 'MX')) {
            $form->setError(__('Invalid domain name.', 'directories'), $element);
        }
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (!empty($data['#skip_validate_text'])) return;

        if (isset($data['#separator'])) {
            $value = explode($data['#separator'], $value);
            foreach (array_keys($value) as $key) {
                if (false === $validated = $this->_application->Form_Validate_text($form, $value[$key], $data, null, false)) {
                    return;
                }
                if (!strlen($validated)) {
                    unset($value[$key]);
                    continue;
                }
                $value[$key] = $validated;
            }
            if (empty($value)) {
                if ($form->isFieldRequired($data)) {
                    $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Please fill out this field.', 'directories'), $data);
                }
            } else {
                if (!empty($data['#max_selection'])) {
                    if (count($value) > $data['#max_selection']) {
                        $form->setError(sprintf(__('Maximum of %d items is allowed for this field.', 'directories'), $data['#max_selection']), $data);
                    }
                }
            }
        } else {
            if (false !== $validated = $this->_application->Form_Validate_text($form, $value, $data)) {
                $value = $validated;
            }
        }
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $this->_render($this->_renderInput($data, $form), $data, $form);
    }
    
    protected function _renderInput(array $data, Form $form, $type = null)
    {
        if (!isset($type)) {
            $type = $data['#type'];
        }
        switch ($type) {
            case 'number':
                if (isset($data['#min_value'])) {
                    $data['#attributes']['min'] = $data['#min_value'];
                }
                if (isset($data['#max_value'])) {
                    $data['#attributes']['max'] = $data['#max_value'];
                }
                if (isset($data['#step'])) {
                    $data['#attributes']['step'] = $data['#step'];
                }
                break;
            case 'password':
            case 'search':
                break;
            default:
                $type = 'text';
        }
        
        $input = $this->_getInput($data, $form, $type);
        
        if (!isset($data['#field_prefix'])
            && !isset($data['#field_suffix'])
            && empty($data['#add_clear'])
        ) return $input;
        
        $has_addon = false;
        $ret = [];
        if (isset($data['#field_prefix'])) {
            if (empty($data['#field_prefix_no_addon'])) {
                $has_addon = true;
                $ret[] = '<div class="' . DRTS_BS_PREFIX . 'input-group-prepend"><span class="' . DRTS_BS_PREFIX . 'input-group-text">' . $data['#field_prefix'] . '</span></div>';
            } else {
                $ret[] = $data['#field_prefix'];
            }
        }
        $ret[] = $input;
        if (isset($data['#field_suffix'])) {
            if (empty($data['#field_suffix_no_addon'])) {
                $has_addon = true;
                $ret[] = '<div class="' . DRTS_BS_PREFIX . 'input-group-append"><span class="' . DRTS_BS_PREFIX . 'input-group-text">' . $data['#field_suffix'] . '</span></div>';
            } else {
                $ret[] = $data['#field_suffix'];
            }
        }
        
        if (!empty($data['#add_clear'])) {
            $ret[] = '<i class="drts-clear fas fa-times-circle"></i>';
        }

        return $has_addon ? '<div class="' . DRTS_BS_PREFIX . 'input-group">' . implode(PHP_EOL, $ret) . '</div>' : implode(PHP_EOL, $ret);       
    }
    
    public function preRenderCallback(Form $form)
    {        
        $this->_application->getPlatform()
            ->addJsFile('jquery.maskedinput.min.js', 'jquery-maskedinput', array('jquery'), null, true, true)
            ->addJsFile('latinise.min.js', 'latinise', null, null, true, true);

        // Add js
        $form->settings['#js_ready'][] = sprintf(
            '(function() {
    var form = $("#%s");
    form.find(".drts-form-type-textfield-mask input[type=text]").each(function(){
        var $this = $(this);
        $this.mask($this.data("mask") + ""); 
    });
    form.on("change", ".drts-form-type-textfield-slugify", function(){
        var $this = $(this);
        $this.val($this.val().latinise().replace(/\W+/g, "_").toLowerCase());
        if ($this.attr("maxlength")) {
            $this.val($this.val().slice(0, $this.attr("maxlength")));
        }
    });
    $(DRTS).on("clonefield.sabai", function (e, data) {
        if (data.clone.hasClass("drts-form-type-textfield-mask")) {
            var input = data.clone.find("input[type=text]");
            input.mask(input.data("mask"));
        }
    });
})();',
            $form->settings['#id']
        );
    }
    
    public static function canCheckMx()
    {
        return function_exists('checkdnsrr')
            && version_compare(PHP_VERSION, '5.2.4', '>='); // 2nd parameter of checkdnsrr added in 5.2.4
    }
}