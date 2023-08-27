<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Component\FrontendSubmit\FrontendSubmitComponent;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\User\AbstractIdentity;

class LoginOrRegister extends Form\Controller
{
    protected $_bundle, $_entity, $_action, $_params = [];

    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $this->_ajaxSubmit = true;
        $show_login_form = $this->getComponent('FrontendSubmit')->isLoginFormEnabled();
        $show_register_form = $this->getComponent('FrontendSubmit')->isRegisterFormEnabled();

        if ($redirect_to = $context->getRequest()->asStr('redirect_to', false)) {
            $redirect_url = $this->Url($redirect_to);
            $url_params = $redirect_url->params;
            if (!empty($url_params['redirect_bundle'])) {
                if (!empty($url_params['redirect_action']) // action param required to check if routable
                    && ($this->_bundle = $this->Entity_Bundle($url_params['redirect_bundle'])) // make sure requested bundle is valid
                ) {
                    $this->_action = $url_params['redirect_action'];
                    if (!empty($url_params['redirect_entity'])) {
                        if (!$this->_entity = $this->Entity_Entity($this->_bundle->entitytype_name, $url_params['redirect_entity'])) {
                            $this->_bundle = $this->_entity = $this->_action = null;
                        }
                    }
                }
            } elseif (!empty($url_params['redirect_bundle_type'])
                && isset($url_params['redirect_action'])
                && $url_params['redirect_action'] === 'add'
                && $this->FrontendSubmit_Submission_bundles($url_params['redirect_bundle_type'])
            ) {
                // Guest user has at least one bundle that is submittable
                $this->_bundle = $url_params['redirect_bundle_type'];
                $this->_action = $url_params['redirect_action'];
            }
            unset($url_params['redirect_bundle'], $url_params['redirect_entity'], $url_params['redirect_bundle_type'], $url_params['redirect_action']);
            $this->_params = $url_params;
        }

        if (!$show_login_form
            && !$this->getPlatform()->isLoginFormRequired()
        ) {
            if (!$this->getPlatform()->isUserRegisterable()
                || !$show_register_form
            ) {
                if (empty($redirect_to)
                    || !isset($this->_bundle)
                    || !isset($this->_action)
                ) {
                    // No form to show, so redirect to platform login
                    $context->setRedirect($this->getPlatform()->getLoginUrl($this->_getRedirectUrl($redirect_to)));
                    return;
                }
            }
        }

        $form = array(
            '#class' => 'drts-frontendsubmit-login-register-form',
            '#build_id' => false,
        );
        if ($show_login_form
            || $this->getPlatform()->isLoginFormRequired()
        ) {
            $login_config = $this->getComponent('FrontendSubmit')->getConfig('login');
            $login_input_type = 'textfield';
            if (!empty($login_config['allow_email'])) {
                // E-mail address allowed
                if (isset($login_config['allow_username'])
                    && empty($login_config['allow_username'])
                ) {
                    // Username not allowed
                    $login_label = __('E-mail Address', 'directories-frontend');
                    $login_input_type = 'email';
                } else {
                    $login_label = __('Username or E-mail Address', 'directories-frontend');
                }
            } else {
                $login_label = __('Username', 'directories-frontend');
            }
            $form['login'] = [
                '#tree' => true,
                '#weight' => 1,
                '#submit_for' => 'login',
                'username' => [
                    '#type' => $login_input_type,
                    '#title' => $login_label,
                    '#weight' => 1,
                    '#required' => true,
                ],
                'password' => [
                    '#type' => 'password',
                    '#title' => __('Password', 'directories-frontend'),
                    '#weight' => 3,
                    '#required' => true,
                ],
                'remember' => [
                    '#type' => 'checkbox',
                    '#title' => __('Remember Me', 'directories-frontend'),
                    '#switch' => false,
                    '#weight' => 10
                ],
                'login' => [
                    '#weight' => 99,
                    '#class' => DRTS_BS_PREFIX . 'form-inline',
                    '#group' => true,
                    'submit' => [
                        '#type' => 'submit',
                        '#btn_label' => __('Login', 'directories-frontend'),
                        '#btn_color' => 'primary',
                        '#submit' => [
                            9 => [[[$this, '_loginUser'], [$context]]], // 9 is weight
                        ],
                        '#submit_id' => 'login',
                        '#value' => 'login',
                        '#weight' => 1,
                    ],
                ],
            ];
            if (!isset($login_config['lost_pass_link'])
                || !empty($login_config['lost_pass_link'])
            ) {
                $form['login']['login']['lost_password'] = [
                    '#type' => 'markup',
                    '#value' => $this->LinkTo(
                        __('Lost your password?', 'directories-frontend'),
                        '/' . $this->getComponent('FrontendSubmit')->getSlug('login') . '/lost_password',
                        ['container' => $this->Filter('frontendsubmit_lost_password_form_container', 'modal'), 'modalSize' => 'lg'],
                        [
                            'class' => DRTS_BS_PREFIX . 'mx-2',
                        ]
                    ),
                    '#weight' => 3,
                ];
            }

            // Allow modifying login form
            $form['login'] = $this->Filter('frontendsubmit_login_form', $form['login']);
        } else {
            $form['login'] = array(
                '#type' => 'markup',
                '#value' => '&nbsp;<a class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-primary" href="' . $this->getPlatform()->getLoginUrl($this->_getRedirectUrl($redirect_to)) . '">'
                    . $this->H(__('Login to continue', 'directories-frontend')) . '</a>',
                '#weight' => 1,
            );
        }

        if ($this->getPlatform()->isUserRegisterable()) {
            if ($show_register_form) {
                $form['register'] = array(
                    '#tree' => true,
                    '#weight' => 2,
                    '#submit_for' => 'register',
                    'username' => [
                        '#type' => 'textfield',
                        '#title' => __('Username', 'directories-frontend'),
                        '#weight' => FrontendSubmitComponent::REGISTER_FORM_FIELD_WEIGHT_USER_NAME,
                        '#required' => true,
                        '#weight' => 1,
                    ],
                    'email' => [
                        '#type' => 'email',
                        '#title' => __('E-mail Address', 'directories-frontend'),
                        '#weight' => FrontendSubmitComponent::REGISTER_FORM_FIELD_WEIGHT_EMAIL,
                        '#required' => true,
                        '#weight' => 2,
                    ],
                    'password' => [
                        '#type' => 'password',
                        '#title' => __('Password', 'directories-frontend'),
                        '#weight' => FrontendSubmitComponent::REGISTER_FORM_FIELD_WEIGHT_PASSWORD,
                        '#required' => true,
                        '#weight' => 3,
                    ],
                    'password_confirm' => [
                        '#type' => 'password',
                        '#title' => __('Confirm Password', 'directories-frontend'),
                        '#weight' => FrontendSubmitComponent::REGISTER_FORM_FIELD_WEIGHT_PASSWORD_CONFIRM,
                        '#required' => true,
                        '#weight' => 4,
                    ],
                    'register' => array(
                        '#weight' => 99,
                        '#class' => DRTS_BS_PREFIX . 'form-inline',
                        '#group' => true,
                        'submit' => array(
                            '#type' => 'submit',
                            '#btn_label' => __('Register', 'directories-frontend'),
                            '#btn_color' => 'primary',
                            '#submit' => array(
                                9 => [[[$this, '_registerUser'], [$context]]], // 9 is weight
                            ),
                            '#submit_id' => 'register',
                            '#value' => 'register',
                            '#weight' => 1,
                        ),
                    ),
                );
                if ($this->getComponent('FrontendSubmit')->getConfig('register', 'privacy')
                    && ($privacy_policy_link = $this->getPlatform()->getPrivacyPolicyLink())
                ) {
                    $form['register']['privacy_policy'] = array(
                        '#weight' => 91,
                        '#type' => 'checkbox',
                        '#switch' => false,
                        '#title_no_escape' => true,
                        '#title' => $this->_getPrivacyPolicyCheckboxLabel($privacy_policy_link, 'register'),
                        '#required' => true,
                        '#required_error_message' => __('You must agree to continue.', 'directories-frontend'),
                    );
                }

                // Allow modifying register form
                $form['register'] = $this->Filter('frontendsubmit_register_form', $form['register']);
            } else {
                $form['register'] = array(
                    '#type' => 'markup',
                    '#value' => '&nbsp;<a class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-primary" href="' . $this->getPlatform()->getRegisterUrl($this->_getRedirectUrl($redirect_to)) . '">'
                        . $this->H(__('Register an account', 'directories-frontend')) . '</a>',
                    '#weight' => 1,
                );
            }
        } else {
            if ($show_register_form) {
                $form['register'] = [
                    '#type' => 'item',
                    '#markup' => '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-warning">'
                        . __('User registration is currently not allowed.', 'directories-frontend') . '</div>',
                ];
            }
        }

        if (!empty($redirect_to)) {
            $form['redirect_to'] = array(
                '#type' => 'hidden',
                '#value' => $redirect_to,
            );
            if (isset($this->_bundle)
                && isset($this->_action)
                && $this->Filter(
                    'frontendsubmit_guest_allowed',
                    true,
                    [$this->_bundle, $this->_action]
                )
                && (is_string($this->_bundle) // should have at least one bundle allowed if string (bundle type) given
                    || $this->_action !== 'add' // currently can only check for add action
                    || $this->HasPermission('entity_create_' . $this->_bundle->name)
                )
            ) {
                $config = $this->getComponent('FrontendSubmit')->getConfig('guest');
                $form['guest'] = array(
                    '#tree' => true,
                    '#weight' => 3,
                    '#submit_for' => 'guest',
                    'continue' => array(
                        '#type' => 'submit',
                        '#btn_label' => __('Continue as guest', 'directories-frontend') . '',
                        '#btn_color' => 'primary',
                        '#value' => 'continue',
                        '#weight' => 99,
                        '#submit' => array(
                            9 => [[[$this, '_continue'], [$context]]], // 9 is weight
                        ),
                        '#submit_id' => 'guest',
                    ),
                );
                if ($this->getComponent('FrontendSubmit')->isCollectGuestInfo()) {
                    if (!empty($config['collect_name'])
                        || !isset($config['collect_name'])
                    ) {
                        $form['guest']['name'] = [
                            '#type' => 'textfield',
                            '#title' => __('Your Name', 'directories-frontend'),
                            '#weight' => 1,
                            '#default_value' => null,
                            '#max_length' => 255,
                            '#required' => !empty($config['require_name']),
                        ];
                    }
                    if (!empty($config['collect_email'])) {
                        $form['guest']['email'] = array(
                            '#type' => 'email',
                            '#title' => __('E-mail Address', 'directories-frontend'),
                            '#weight' => 3,
                            '#default_value' => null,
                            '#check_mx' => !empty($config['check_mx']),
                            '#check_exists' => !empty($config['check_exists']),
                            '#max_length' => 255,
                            '#required' => !empty($config['require_email']),
                        );
                    }
                    if (!empty($config['collect_url'])) {
                        $form['guest']['url'] = array(
                            '#type' => 'url',
                            '#title' => __('Website URL', 'directories-frontend'),
                            '#weight' => 5,
                            '#default_value' => null,
                            '#max_length' => 255,
                            '#required' => !empty($config['require_url']),
                        );
                    }
                    if (!empty($config['collect_privacy'])
                        && ($privacy_policy_link = $this->getPlatform()->getPrivacyPolicyLink())
                    ) {
                        $form['guest']['privacy_policy'] = array(
                            '#weight' => 91,
                            '#type' => 'checkbox',
                            '#switch' => false,
                            '#title_no_escape' => true,
                            '#title' => $this->_getPrivacyPolicyCheckboxLabel($privacy_policy_link, 'guest'),
                            '#required' => true,
                            '#required_error_message' => __('You must agree to continue.', 'directories-frontend'),
                        );
                    }
                }
            }
        }

        $context->addTemplate($this->getPlatform()->getAssetsDir('directories-frontend') . '/templates/frontendsubmit_login_register_form');

        return $form;
    }

    protected function _getPrivacyPolicyCheckboxLabel($link, $type)
    {
        if (!$label = $this->Filter('frontendsubmit_privacy_policy_check_label', '', [$link, $type])) {
            $label = sprintf($this->H(__('I have read and agree to the %s', 'directories-frontend')), $link);
        }
        return $label;
    }

    public function _registerUser(Form\Form $form, Context $context)
    {
        if ($form->values['register']['password'] !== $form->values['register']['password_confirm']) {
            $form->setError(__('Passwords do not match.', 'directories-frontend'));
            return;
        }

        try {
            $user_id = $this->getPlatform()->registerUser(
                $form->values['register']['username'],
                $form->values['register']['email'],
                $form->values['register']['password'],
                $form->values['register']
            );
            if (!$identity = $this->UserIdentity($user_id)) {
                throw new Exception\RuntimeException(sprintf(__('User with user ID %d does not exist.', 'directories-frontend'), $user_id));
            }
            $this->Action('frontendsubmit_register', [$identity, $form->values['register']]);
        } catch (Exception\RuntimeException $e) {
            $form->setError(strip_tags($e->getMessage()), 'register');
            return;
        }

        $redirect_to = empty($form->values['redirect_to']) ? null : $form->values['redirect_to'];

        if ($this->getComponent('FrontendSubmit')->getConfig('register', 'verify_email')
            && $this->FrontendSubmit_VerifyAccount_isRequiredByEmail($identity->email)
        ) {
            if ($register_success_msg = $this->Filter('frontendsubmit_register_success_message', __('Your account has been created successfully.', 'directories-frontend'))) {
                $context->addFlash($register_success_msg);
            }
            $context->setSuccess('/' . $this->getComponent('FrontendSubmit')->getSlug('login'));
            try {
                $this->FrontendSubmit_VerifyAccount_unverify($identity);
                if ($register_need_verify_msg = $this->Filter('frontendsubmit_register_need_verify_message', __('Please check your email address to verify your account.', 'directories-frontend'))) {
                    $context->addFlash($register_need_verify_msg);
                }
            } catch (Exception\IException $e) {
                $context->addFlash($e->getMessage(), 'danger');
            }
            if ($redirect_url = $this->_getRedirectUrl($redirect_to, [], 'register', $identity, false)) {
                $this->getPlatform()->setEntityMeta('user', $identity->id, 'frontendsubmit_verify_account_redirect', serialize(is_string($redirect_url) ? $redirect_url : [
                    'route' => $redirect_url->route,
                    'params' => $redirect_url->params,
                ]));
            }
            return;
        }

        // Redirect to login page if setting current user failed for some reason
        if (!$this->getPlatform()->setCurrentUser($user_id)) {
            $redirect_url = '/' . $this->getComponent('FrontendSubmit')->getSlug('login');
        } else {
            $redirect_url = $this->_getRedirectUrl($redirect_to, [], 'register', $identity);
        }

        $context->setSuccess($redirect_url);

        $this->Action('frontendsubmit_register_success', [$identity, $context]);
    }

    public function _loginUser(Form\Form $form, Context $context)
    {
        $login_name = $form->values['login']['username'];
        $login_config = $this->getComponent('FrontendSubmit')->getConfig('login');
        if (!empty($login_config['allow_email'])) {
            // E-mail address allowed
            if (isset($login_config['allow_username'])
                && empty($login_config['allow_username'])
            ) {
                // Only e-mail address is allowed
                if (!filter_var($login_name, FILTER_VALIDATE_EMAIL)) {
                    $form->setError(__('Please enter a valid e-mail address.', 'directories-frontend'), 'login[username]');
                    return;
                }
            }
        } else {
            // Only username allowed
            if (filter_var($login_name, FILTER_VALIDATE_EMAIL)) {
                $form->setError(__('Please enter a valid username.', 'directories-frontend'), 'login[username]');
                return;
            }
        }
        try {
            $user_id = $this->getPlatform()->loginUser(
                $login_name,
                $form->values['login']['password'],
                !empty($form->values['login']['remember']),
                $form->values['login']
            );
            if (!$identity = $this->UserIdentity($user_id)) {
                throw new Exception\RuntimeException(sprintf(__('User with user ID %d does not exist.', 'directories-frontend'), $user_id));
            }
            $this->Action('frontendsubmit_login', [$identity, $form->values]);
        } catch (Exception\RuntimeException $e) {
            $form->setError(strip_tags($e->getMessage()), 'login');
            return;
        }

        if ($this->getComponent('FrontendSubmit')->getConfig('register', 'verify_email')
            && $this->FrontendSubmit_VerifyAccount_isRequired($identity->id)
        ) {
            $this->getPlatform()->logoutUser();
            $form->setError(sprintf(
                __('Your account requires verification. Please check your email address to verify your account or click <a href="%s">here</a> to resend a verification email.', 'directories-frontend'),
                $this->FrontendSubmit_VerifyAccount_resendKeyUrl($identity)
            ));
            return;
        }

        $context->setSuccess($this->_getRedirectUrl(empty($form->values['redirect_to']) ? null : $form->values['redirect_to'], [], 'login', $identity));

        $this->Action('frontendsubmit_login_success', [$identity, $context]);
    }

    public function _continue(Form\Form $form, Context $context)
    {
        $redirect_url = $this->_getRedirectUrl(
            empty($form->values['redirect_to']) ? null : $form->values['redirect_to'],
            ['_guest' => [
                'name' => isset($form->values['guest']['name']) ? $form->values['guest']['name'] : null,
                'email' => isset($form->values['guest']['email']) ? $form->values['guest']['email'] : null,
                'url' => isset($form->values['guest']['url']) ? $form->values['guest']['url'] : null,
            ]],
            'guest'
        );
        $context->setSuccess($redirect_url);
    }

    protected function _getRedirectUrl($url, array $params = [], $type = 'login', AbstractIdentity $identity = null, $returnDefault = true)
    {
        if (isset($this->_bundle)
            && isset($this->_action)
        ) {
            if (!isset($this->_entity)) {
                switch ($this->_action) {
                    case 'add':
                        if (is_string($this->_bundle)) {
                            if ((!$component_name = $this->Entity_BundleTypes($this->_bundle))
                                || !$this->isComponentLoaded($component_name)
                            ) break;

                            $bundle_type = $this->_bundle;
                        } else {
                            $params['bundle'] = $this->_bundle->name;
                            $bundle_type = $this->_bundle->type;
                        }
                        $url = $this->Url('/' . $this->FrontendSubmit_AddEntitySlug($bundle_type), $params + $this->_params, '', '&');
                }
            } else {
                if (empty($this->_bundle->info['parent'])) {
                    $url = $this->Entity_Url(
                        $this->_entity,
                        '/' . $this->_action,
                        $params + $this->_params,
                        '',
                        '&'
                    );
                } else {
                    // Redirect to parent page with action path
                    $url = $this->Entity_Url(
                        $this->_entity,
                        '/' . $this->_bundle->info['slug'] . (empty($this->_bundle->info['public']) ? '_' : '/') . $this->_action,
                        $params + $this->_params,
                        '',
                        '&'
                    );
                }
            }
        }

        if ((!$url = $this->Filter('frontendsubmit_after_login_register_url', $url, [$type, $identity]))
            && $returnDefault
        ) {
            $url = $this->_getDefaultRedirectUrl($type, $identity);
        }

        return $url;
    }

    protected function _getDefaultRedirectUrl($type, AbstractIdentity $identity = null)
    {
        if ($type === 'login'
            && isset($identity)
            && $this->getPlatform()->isAdministrator($identity->id)
        ) {
            $url = rtrim($this->getPlatform()->getSiteAdminUrl(), '/') . '/';
        } elseif ($this->isComponentLoaded('Dashboard')
            && ($dashboard_slug = $this->getComponent('Dashboard')->getSlug('dashboard'))
        ) {
            $url = (string)$this->Url('/' . $dashboard_slug);
        } else {
            $url = rtrim($this->getPlatform()->getSiteUrl(), '/') . '/';
        }

        return $url;
    }
}