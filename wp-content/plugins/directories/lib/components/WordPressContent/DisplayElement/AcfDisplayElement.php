<?php
namespace SabaiApps\Directories\Component\WordPressContent\DisplayElement;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Form;

class AcfDisplayElement extends Display\Element\AbstractElement
{
    protected static $_fieldGroups = [], $_initialized = false;

    protected function _displayElementInfo(Bundle $bundle)
    {
        return [
            'type' => 'content',
            'label' => _x('ACF Fields', 'ACF', 'directories'),
            'description' => _x('Adds ACF fields to frontend form', 'ACF', 'directories'),
            'default_settings' => [
                'field_group' => null,
            ],
            'icon' => 'far fa-list-alt',
            'designable' => ['margin', 'padding'],
        ];
    }

    protected function _displayElementSupports(Bundle $bundle, Display\Model\Display $display)
    {
        return class_exists('ACF', false)
            && $display->type === 'form'
            && $bundle->entitytype_name === 'post';
    }

    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return [
            'field_group' => [
                '#type' => 'select',
                '#title' => _x('Field group', 'ACF', 'directories'),
                '#options' => $this->_getFieldGroupOptions($bundle),
                '#required' => true,
                '#horizontal' => true,
                '#default_value' => $settings['field_group'],
            ],
        ];
    }

    protected function _getFieldGroupOptions(Bundle $bundle)
    {
        $options = [];
        if ($field_groups = acf_get_field_groups(['post_type' => $bundle->name])) {
            foreach ($field_groups as $field_group) {
                $options[$field_group['ID']] = $field_group['title'];
            }
        }
        return $options;
    }

    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        if (!class_exists('ACF', false)
            || $this->_application->getPlatform()->isAdmin()
        ) return;

        if (!self::$_initialized) {
            acf_enqueue_scripts(); // do not use acf_form_head() to prevent form submission on display
            // Populate field values from $_POST if any
            if (!empty($_POST['_acf_form'])
                && !empty($_POST['acf'])
            ) {
                add_filter('acf/pre_render_fields', function ($fields) {
                    foreach (array_keys($fields) as $k) {
                        $key = $fields[$k]['key'];
                        if (isset($_POST['acf'][$key])) {
                            // Some fields such as the google_map field send data as json encoded string
                            if (is_string($_POST['acf'][$key])
                                && (null !== $decoded = json_decode(wp_unslash($_POST['acf'][$key]), true))
                                && json_last_error() === JSON_ERROR_NONE
                            ) {
                                $fields[$k]['value'] = $decoded;
                            } else {
                                $fields[$k]['value'] = $_POST['acf'][$key];
                            }
                        }
                    }
                    return $fields;
                });
            }
            self::$_initialized = true;
        }

        $settings = $element['settings'];
        if (empty($settings['field_group'])) return;

        // Allow field group to be rendered 1 time per post type
        if (!empty(self::$_fieldGroups[$bundle->name][$settings['field_group']])) return;

        self::$_fieldGroups[$bundle->name][$settings['field_group']] = true;
        $args = [
            'form' => false, // do not output <form> tag and submit button
            'field_groups' => [$settings['field_group']],
            'post_id' => isset($var['#entity']) && ($post_id = $var['#entity']->getId()) ? $post_id : false,
            'honeypot' => false,
            'return' => false, // no redirect
        ];
        ob_start();
        acf_form($args);
        return ob_get_clean();
    }

    public static function validateFormCallback(Form\Form $form)
    {
        if (empty($_POST['_acf_form'])) return;

        // Get form
        if (!acf_verify_nonce('acf_form')) {
            $form->setError(__('An error occurred while processing the form.', 'directories'));
            return;
        }
        if (!$acf_form = acf_decrypt($_POST['_acf_form'])) {
            $form->setError(__('Could not fetch ACF form.', 'directories'));
            return;
        }
        if (!$acf_form = json_decode($acf_form, true)) {
            $form->setError(__('Could not reconstruct ACF form.', 'directories'));
            return;
        }

        // kses
        if ($acf_form['kses']
            && isset($_POST['acf'])
        ) {
            $_POST['acf'] = wp_kses_post_deep($_POST['acf']);
        }

        // Validate
        if (!acf_validate_save_post()
            && ($errors = acf_get_validation_errors())
        ) {
            foreach ($errors as $error) {
                if (strpos($error['input'], 'acf[') === 0
                    && ($field = acf_get_field(substr($error['input'], 4, -1)))
                ) {
                    $label = $field['label'];
                } else {
                    $label = '';
                }
                $form->setError($error['message'], $label);
            }
        }
    }

    public static function submitFormCallback(Form\Form $form)
    {
        if (empty($_POST['_acf_form'])
            || $form->hasError()
        ) return;

        // Below should not throw error since already validated, but just in case.
        if ((!$acf_form = acf_decrypt($_POST['_acf_form']))
            || (!$acf_form = json_decode($acf_form, true))
        ) {
            $form->setError(__('Could not submit ACF form.', 'directories'));
            return;
        }

        // Submit
        $acf_form['post_id'] = $form->settings['#entity']->getId();
        acf()->form_front->submit_form($acf_form);
    }

    protected function _displayElementReadableInfo(Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [];
        if (!empty($settings['field_group'])
            && ($field_groups = $this->_getFieldGroupOptions($bundle))
            && isset($field_groups[$settings['field_group']])
        ) {
            $ret['field_group'] = [
                'label' => _x('Field group', 'ACF', 'directories'),
                'value' => $field_groups[$settings['field_group']],
            ];
        }

        return ['settings' => ['value' => $ret]];
    }
}
