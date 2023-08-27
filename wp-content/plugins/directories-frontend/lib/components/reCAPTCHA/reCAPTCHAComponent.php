<?php
namespace SabaiApps\Directories\Component\reCAPTCHA;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class reCAPTCHAComponent extends AbstractComponent implements Display\IElements
{
    const VERSION = '1.3.108', PACKAGE = 'directories-frontend';

    public static function description()
    {
        return 'Adds a CAPTCHA field to forms using Google reCAPTCHA API.';
    }

    public function getDefaultConfig()
    {
        return [
            'sitekey' => '',
            'secret' => '',
            'theme' => 'light',
            'type' => 'image',
            'size' => 'normal',
        ];
    }

    public function displayGetElementNames(Entity\Model\Bundle $bundle)
    {
        return array('recaptcha_captcha');
    }

    public function displayGetElement($name)
    {
        return new DisplayElement\CaptchaDisplayElement($this->_application, $name);
    }

    public function onFormBuildFrontendSubmitLoginOrRegister(array &$form)
    {
        if (defined('DRTS_RECAPTCHA_DISABLE')
            && DRTS_RECAPTCHA_DISABLE
        ) return;

        $options = array(
            'weight' => 98,
        );
        if ($this->_application->getComponent('FrontendSubmit')->getConfig('login', 'recaptcha')) {
            $form['login']['recaptcha'] = $this->_application->reCAPTCHA_Captcha($options + array('name' => 'login', 'trigger' => 'login[login][submit]'));
        }
        if (isset($form['register']['register']['submit'])
            && $this->_application->getComponent('FrontendSubmit')->getConfig('register', 'recaptcha')
        ) {
            $form['register']['recaptcha'] = $this->_application->reCAPTCHA_Captcha($options + array('name' => 'register', 'trigger' => 'register[register][submit]'));
        }
        if (isset($form['guest']['continue'])
            && $this->_application->getComponent('FrontendSubmit')->getConfig('guest', 'recaptcha')
        ) {
            $form['guest']['recaptcha'] = $this->_application->reCAPTCHA_Captcha($options + array('name' => 'guest', 'trigger' => 'guest[continue]'));
        }
    }

    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        $v3_states = [
            'visible' => [
                '[name="' . $this->_name . '[version]"]' => ['value' => 3],
            ],
        ];
        $v2_states = [
            'visible' => [
                '[name="' . $this->_name . '[version]"]' => ['value' => 2],
            ],
        ];
        $form['fields'][$this->_name] = array(
            '#component' => $this->_name,
            '#tab' => 'FrontendSubmit',
            '#title' => __('reCAPTCHA API Settings', 'directories-frontend'),
            'version' => array(
                '#type' => 'select',
                '#title' => __('reCAPTCHA API version', 'directories-frontend'),
                '#options' => array(
                    2 => __('reCAPTCHA v2 (Checkbox)', 'directories-frontend'),
                    3 => __('reCAPTCHA v3', 'directories-frontend'),
                ),
                '#default_value' => isset($this->_config['version']) ? $this->_config['version'] : 2,
                '#horizontal' => true,
            ),
            'sitekey_v3' => array(
                '#title' => __('reCAPTCHA API site key', 'directories-frontend'),
                '#type' => 'textfield',
                '#default_value' => $this->_config['sitekey_v3'],
                '#horizontal' => true,
                '#states' => $v3_states,
            ),
            'secret_v3' => array(
                '#title' => __('reCAPTCHA API secret key', 'directories-frontend'),
                '#type' => 'textfield',
                '#default_value' => $this->_config['secret_v3'],
                '#horizontal' => true,
                '#states' => $v3_states,
            ),
            'score' => [
                '#title' => __('reCAPTCHA score threshold', 'directories-frontend'),
                '#type' => 'slider',
                '#min_value' => 0,
                '#max_value' => 1,
                '#step' => 0.1,
                '#default_value' => isset($this->_config['score']) ? $this->_config['score'] : 0.5,
                '#horizontal' => true,
                '#numeric' => true,
                '#states' => $v3_states,
            ],
            'sitekey' => array(
                '#title' => __('reCAPTCHA API site key', 'directories-frontend'),
                '#type' => 'textfield',
                '#default_value' => $this->_config['sitekey'],
                '#horizontal' => true,
                '#states' => $v2_states,
            ),
            'secret' => array(
                '#title' => __('reCAPTCHA API secret key', 'directories-frontend'),
                '#type' => 'textfield',
                '#default_value' => $this->_config['secret'],
                '#horizontal' => true,
                '#states' => $v2_states,
            ),
            'size' => array(
                '#type' => 'select',
                '#title' => __('reCAPTCHA size', 'directories-frontend'),
                '#options' => array(
                    'normal' => __('Normal', 'directories-frontend'),
                    'compact' => __('Compact', 'directories-frontend'),
                ),
                '#default_value' => isset($this->_config['size']) ? $this->_config['size'] : 'normal',
                '#horizontal' => true,
                '#states' => $v2_states,
            ),
            'type' => array(
                '#type' => 'select',
                '#title' => __('reCAPTCHA type', 'directories-frontend'),
                '#options' => array(
                    'image' => __('Image', 'directories-frontend'),
                    'audio' => __('Audio', 'directories-frontend'),
                ),
                '#default_value' => isset($this->_config['type']) ? $this->_config['type'] : 'image',
                '#horizontal' => true,
                '#states' => $v2_states,
            ),
            'theme' => array(
                '#type' => 'select',
                '#title' => __('reCAPTCHA theme', 'directories-frontend'),
                '#options' => array(
                    'light' => __('Light', 'directories-frontend'),
                    'dark' => __('Dark', 'directories-frontend'),
                ),
                '#default_value' => isset($this->_config['theme']) ? $this->_config['theme'] : 'light',
                '#states' => array(
                    'visible' => array(
                        'select[name="' . $this->_name . '[size]"]' => array('type' => 'one', 'value' => ['normal', 'compact']),
                    ),
                ),
                '#horizontal' => true,
                '#states' => $v2_states,
            ),
        );
    }

    public function onFormBuildDirectoryAdminSettingsForm(&$form)
    {
        $form['FrontendSubmit']['FrontendSubmit']['login']['recaptcha'] = [
            '#type' => 'checkbox',
            '#title' => __('Add reCAPTCHA field', 'directories-frontend'),
            '#default_value' => $this->_application->getComponent('FrontendSubmit')->getConfig('login', 'recaptcha'),
            '#horizontal' => true,
            '#weight' => 50,
            '#states' => [
                'visible' => [
                    'input[name="FrontendSubmit[login][form]"]' => ['type' => $form['FrontendSubmit']['login']['form']['#type'] === 'hidden' ? 'value' : 'checked', 'value' => true],
                ],
            ],
        ];
        $form['FrontendSubmit']['FrontendSubmit']['register']['recaptcha'] = [
            '#type' => 'checkbox',
            '#title' => __('Add reCAPTCHA field', 'directories-frontend'),
            '#default_value' => $this->_application->getComponent('FrontendSubmit')->getConfig('register', 'recaptcha'),
            '#horizontal' => true,
            '#weight' => 50,
            '#states' => [
                'visible' => [
                    'input[name="FrontendSubmit[register][form]"]' => ['type' => 'checked', 'value' => true],
                ],
            ],
        ];
        $form['FrontendSubmit']['FrontendSubmit']['guest']['recaptcha'] = [
            '#type' => 'checkbox',
            '#title' => __('Add reCAPTCHA field', 'directories-frontend'),
            '#default_value' => $this->_application->getComponent('FrontendSubmit')->getConfig('guest', 'recaptcha'),
            '#horizontal' => true,
            '#weight' => 50,
        ];
    }

    public function onFrontendsubmitCollectGuestInfoFilter(&$bool)
    {
        $bool = $bool || $this->_application->getComponent('FrontendSubmit')->getConfig('guest', 'recaptcha');
    }

    public static function events()
    {
        return array(
            // Make sure the callback is called after FrontendSubmit component
            'directoryadminsettingsformfilter' => 99,
        );
    }
}
