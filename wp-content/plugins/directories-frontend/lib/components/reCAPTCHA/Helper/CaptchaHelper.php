<?php
namespace SabaiApps\Directories\Component\reCAPTCHA\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;

class CaptchaHelper
{
    protected static $_jsLoaded, $_count = 0, $_preRenderAdded = [];

    public function help(Application $application, array $options = [])
    {
        $config = $application->getComponent('reCAPTCHA')->getConfig();
        $version = isset($config['version']) && (int)$config['version'] === 3 ? 3 : 2;
        if (($version === 2 && (empty($config['sitekey']) || empty($config['secret'])))
            || ($version === 3 && (empty($config['sitekey_v3']) || empty($config['secret_v3'])))
        ) {
            return [
                '#type' => 'item',
                '#default_value' => __('reCAPTCHA site/secret keys must be obtained from Google and configured under Directories -> Settings -> reCAPTCHA.', 'directories-frontend'),
            ];
        }

        $options += [
            'trigger' => null,
            'name' => ++self::$_count,
        ];
        if ($version === 3) {
            return $this->_v3($application, $config, $options);
        } else {
            return $this->_v2($application, $config, $options);
        }
    }

    protected function _v2(Application $application, array $config, array $options)
    {
        $options += [
            'size' => $config['size'],
            'type' => $config['type'],
            'theme' => $config['theme'],
            'weight' => 0,
        ];

        if (!self::$_jsLoaded) {
            $js = sprintf(
                'var sabaiReCaptchaCallback = function() {
    if (typeof(grecaptcha) === "undefined"
        || typeof(grecaptcha.render) === "undefined"
    ) return;
    jQuery(".drts-recaptcha-form-field").each(function(i) {
        var $this = jQuery(this), options = {
            sitekey: "%s",
            size: $this.data("size"),
            type: $this.data("type"),
            theme: $this.data("theme")
        };
        $this.data("recaptcha-widget-id", grecaptcha.render($this.attr("id"), options))
            .find("textarea").attr("name", $this.attr("id"));
    });
};',
                $application->H($config['sitekey'])
            );
            $locale = $application->getPlatform()->getLocale();
            if (strpos($locale, '_')) {
                $locale = explode('_', $locale);
                $locale = in_array($locale[0], ['zh', 'pt']) ? $locale[0] . '-' . $locale[1] : $locale[0];
            }
            $application->getPlatform()->addHead(
                '<script type="text/javascript">' . $js . '</script>
<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?onload=sabaiReCaptchaCallback&render=explicit&hl=' . $locale . '" async defer></script>',
                'recaptcha'
            );
            self::$_jsLoaded = true;
        }

        $id = 'drts-recaptcha-form-field-' . $options['name'];
        return [
            '#type' => 'item',
            '#element_validate' => [
                [[$this, '_validateCaptchaV2'], [$application, $config['secret'], $options['trigger'], $id]]
            ],
            '#markup' => '<div class="drts-recaptcha-form-field" id="' . $id . '" data-size="' . $application->H($options['size']) . '" data-type="' . $application->H($options['type']) . '" data-theme="' . $application->H($options['theme']) . '" data-trigger="' . $application->H($options['trigger']) . '"></div>',
            '#weight' => $options['weight'],
        ];
    }

    protected function _v3(Application $application, array $config, array $options)
    {
        $options += [
            'action' => is_numeric($action = preg_replace("%[^A-Za-z0-9_/]%",'', $options['name'])) ? 'default' : $action,
        ];
        if (!self::$_jsLoaded) {
            $js = sprintf(
                'var sabaiReCaptchaCallback = function() {
    jQuery(".drts-recaptcha-form-field").each(function(i) {
        var $this = jQuery(this),
            fetchToken = function () {
            grecaptcha.execute("%s", {action: $this.data("action")}).then(function(token) {
                $this.val(token);
                console.log("reCAPTCHA token updated.");
            });
        };
        fetchToken();
        setInterval(function () { fetchToken(); }, 2 * 60 * 1000);
    });
};',
                $application->H($config['sitekey_v3'])
            );
            $application->getPlatform()->addHead(
                '<script type="text/javascript">' . $js . '</script>
<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?onload=sabaiReCaptchaCallback&render=' . $application->H($config['sitekey_v3']) . '" async defer></script>',
                'recaptcha'
            );
            self::$_jsLoaded = true;
        }

        $id = 'drts-recaptcha-form-field-' . $options['name'];
        return [
            '#type' => 'markup',
            '#submit' => [
                9 => [[[$this, '_validateCaptchaV3'], [$application, $config['secret_v3'], $options['trigger'], $id, $options['action'], $config['score']]]]
            ],
            '#markup' => '<input type="hidden" value="" class="drts-recaptcha-form-field" name="' . $id . '" data-trigger="' . $application->H($options['trigger']) . '" data-action="' . $application->H($options['action']) . '"/>',
        ];
    }

    public function _validateCaptchaV2(Form\Form $form, &$value, $element, Application $application, $secret, $trigger, $id)
    {
        $this->_validateCaptcha($form, $application, $secret, $trigger, $id, $element);
    }

    public function _validateCaptchaV3(Form\Form $form, Application $application, $secret, $trigger, $id, $action, $score)
    {
        $this->_validateCaptcha($form, $application, $secret, $trigger, $id, null, $action, $score);
    }

    public function _validateCaptcha(Form\Form $form, Application $application, $secret, $trigger, $id, $element = null, $action = null, $minScore = null)
    {
        if (isset($trigger) && !$form->getValue($trigger)) return;

        if (!isset($_POST[$id])
            || !strlen($_POST[$id])
        ) {
            if (isset($element)) {
                $form->setError(__('Please fill out this field.', 'directories-frontend'), $element);
            } else {
                $form->setError(__('Failed validating reCAPTCHA value.', 'directories-frontend'));
            }
        } else {
            if (true !== $result = $application->reCAPTCHA_Captcha_verify($secret, $_POST[$id], $action, $minScore)) {
                $error = $result['error'];
                if (!empty($result['error_codes'])) {
                    $error .= ' (' . implode(',', $result['error_codes']) . ')';
                }
                $form->setError($error, isset($element) ? $element : '');
            }
        }
        if (empty(self::$_preRenderAdded[$form->settings['#id']])) {
            $form->settings['#pre_render'][] = array($this, '_preRenderCallback');
            self::$_preRenderAdded[$form->settings['#id']] = true;
        }
    }

    public function _preRenderCallback($form)
    {
        if ($form->hasError()) {
            $form->settings['#js_ready'][] = 'sabaiReCaptchaCallback();';
        }
    }

    public function verify(Application $application, $secret, $value, $action = null, $minScore = null)
    {
        if (empty($secret)) {
            throw new Exception\InvalidArgumentException(__('Invalid reCAPTCHA secret key.', 'directories-frontend'));
        }

        if (empty($value)) {
            throw new Exception\InvalidArgumentException(__('Invalid reCAPTCHA value.', 'directories-frontend'));
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
            'secret' => $secret,
            'response' => $value,
            'remoteip' => $this->_getIp(),
        ]);
        if ((!$json = $this->_getResponse($url))
            || (!$response = json_decode($json, true))
        ) {
            throw new Exception\RuntimeException(__('Failed obtaining valid reCAPTCHA verify response.', 'directories-frontend'));
        }

        // Check response
        if (empty($response['success'])) {
            $error = __('Unknown error.', 'directories-frontend');
            if (!empty($response['error-codes'])) {
                switch ($response['error-codes'][0]) {
                    case 'missing-input-secret':
                    case 'invalid-input-secret':
                        $error = __('Invalid or missing secret parameter.', 'directories-frontend');
                        break;
                    case 'missing-input-response':
                    case 'invalid-input-response':
                        $error = __('Invalid or missing response parameter', 'directories-frontend');
                        break;
                    case 'bad-request':
                        $error = __('Invalid or malformed request.', 'directories-frontend');
                        break;
                }
            }
            return [
                'error' => $error,
                'error_codes' => empty($response['error-codes']) ? [] : $response['error-codes'],
            ];
        }

        // Check action?
        if (isset($action)
            && $action !== $response['action']
        ) {
            return ['error' => __('Invalid action.', 'directories-frontend')];
        }

        // Check score?
        if (!empty($minScore)
            && isset($response['score'])
            && $response['score'] < $minScore
        ) {
            return ['error' => __('Request denied.', 'directories-frontend')];
        }

        return true;
    }

    protected function _getResponse($url)
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['User-Agent: PHP/' . PHP_VERSION],
            ]);

            return curl_exec($curl);
        }

        return file_get_contents($url);
    }

    protected function _getIp()
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) return $_SERVER[$key];
        }
        return '';
    }
}
