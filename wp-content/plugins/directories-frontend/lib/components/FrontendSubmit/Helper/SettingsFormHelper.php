<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;

class SettingsFormHelper
{
    public function help(Application $application, array $config, array $parents)
    {
        $form = [];
        $guest_field_name_prefix = $application->Form_FieldName(array_merge($parents, array('guest')));
        $form['guest'] = array(
            '#weight' => 30,
            '#title' => __('Guest Post Settings', 'directories-frontend'),
            '#tree' => true,
            'collect_name' => array(
                '#type' => 'checkbox',
                '#title' => __('Collect guest name', 'directories-frontend'),
                '#default_value' => !isset($config['guest']['collect_name']) || !empty($config['guest']['collect_name']),
                '#weight' => 1,
                '#horizontal' => true,
            ),
            'require_name' => array(
                '#type' => 'checkbox',
                '#title' => __('Require guest name', 'directories-frontend'),
                '#default_value' => !isset($config['guest']['require_name']) || !empty($config['guest']['require_name']),
                '#weight' => 3,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $guest_field_name_prefix . '[collect_name]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#horizontal' => true,
            ),
            'collect_email' => array(
                '#type' => 'checkbox',
                '#title' => __('Collect e-mail address', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['collect_email']),
                '#weight' => 5,
                '#horizontal' => true,
            ),
            'require_email' => array(
                '#type' => 'checkbox',
                '#title' => __('Require e-mail address', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['require_email']),
                '#weight' => 6,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $guest_field_name_prefix . '[collect_email]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#horizontal' => true,
            ),
            'check_exists' => array(
                '#type' => 'checkbox',
                '#title' => __('Do not allow e-mail address used by registered users', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['check_exists']),
                '#weight' => 7,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $guest_field_name_prefix . '[collect_email]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#horizontal' => true,
            ),
            'collect_url' => array(
                '#type' => 'checkbox',
                '#title' => __('Collect website URL', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['collect_url']),
                '#weight' => 10,
                '#horizontal' => true,
            ),
            'require_url' => array(
                '#type' => 'checkbox',
                '#title' => __('Require website URL', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['require_url']),
                '#weight' => 11,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $guest_field_name_prefix . '[collect_url]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#horizontal' => true,
            ),
        );
        if (Form\Field\TextField::canCheckMx()) {
            $form['guest']['check_mx'] = array(
                '#type' => 'checkbox',
                '#title' => __('Check MX record of e-mail address', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['check_mx']),
                '#weight' => 8,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $guest_field_name_prefix . '[collect_email]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#horizontal' => true,
            );
        }
        $form['guest']['collect_privacy'] = [
            '#type' => 'checkbox',
            '#title' => __('Add a privacy policy consent checkbox', 'directories-frontend'),
            '#default_value' => !empty($config['guest']['collect_privacy']),
            '#weight' => 15,
            '#horizontal' => true,
        ];

        $lost_pass_default_email = $application->FrontendSubmit_LostPassword_defaultEmail();
        $form['login'] = array(
            '#weight' => 10,
            '#title' => __('User Login Settings', 'directories-frontend'),
            '#tree' => true,
            '#element_validate' => [function(Form\Form $form, &$value, $element) {
                if (empty($value['allow_username'])
                    && empty($value['allow_email'])
                ) $value['allow_username'] = true;
            }],
            'form' => array(
                '#type' => 'checkbox',
                '#title' => __('Show user login form', 'directories-frontend'),
                '#default_value' => !isset($config['login']['form']) || !empty($config['login']['form']),
                '#horizontal' => true,
                '#weight' => 1,
            ),
            'allow_username' => [
                '#type' => 'checkbox',
                '#title' => __('Allow login with username', 'directories-frontend'),
                '#default_value' => !isset($config['login']['allow_username']) || !empty($config['login']['allow_username']),
                '#horizontal' => true,
                '#weight' => 2,
                '#states' => [
                    'visible' => [
                        $login_form_selector = 'input[name="' . $application->Form_FieldName(array_merge($parents, ['login', 'form'])) . '"]' => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'allow_email' => [
                '#type' => 'checkbox',
                '#title' => __('Allow login with e-mail address', 'directories-frontend'),
                '#default_value' => !empty($config['login']['allow_email']),
                '#horizontal' => true,
                '#weight' => 3,
                '#states' => [
                    'visible' => [
                        $login_form_selector => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'lost_pass_link' => [
                '#type' => 'checkbox',
                '#title' => __('Show lost password link', 'directories-frontend'),
                '#default_value' => !isset($config['login']['lost_pass_link']) || !empty($config['login']['lost_pass_link']),
                '#horizontal' => true,
                '#weight' => 5,
                '#states' => [
                    'visible' => [
                        $login_form_selector => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'lost_pass_custom_email' => [
                '#type' => 'checkbox',
                '#title' => __('Customize lost password e-mail', 'directories-frontend'),
                '#horizontal' => true,
                '#default_value' => !empty($config['login']['lost_pass_custom_email']),
                '#states' => [
                    'visible' => [
                        $login_form_selector => ['type' => 'checked', 'value' => true],
                        $lost_pass_link_selector = 'input[name="' . $application->Form_FieldName(array_merge($parents, ['login'])) . '[lost_pass_link]"]' => ['type' => 'checked', 'value' => true],
                    ],
                ],
                '#weight' => 6,
            ],
            'lost_pass_email_subject' => [
                '#type' => 'textfield',
                '#field_prefix' => __('E-mail subject', 'directories-frontend'),
                '#description' => $lost_pass_email_tags = $application->System_Util_availableTags($application->FrontendSubmit_LostPassword_emailTags()),
                '#description_no_escape' => true,
                '#horizontal' => true,
                '#default_value' => empty($config['login']['lost_pass_email_subject']) ? $lost_pass_default_email[0] : $config['login']['lost_pass_email_subject'],
                '#states' => [
                    'visible' => [
                        $login_form_selector => ['type' => 'checked', 'value' => true],
                        $lost_pass_link_selector => ['type' => 'checked', 'value' => true],
                        $lost_pass_custom_email_selector = 'input[name="' . $application->Form_FieldName(array_merge($parents, ['login'])) . '[lost_pass_custom_email]"]' => ['type' => 'checked', 'value' => true],
                    ],
                ],
                '#weight' => 7,
            ],
            'lost_pass_email_body' => [
                '#type' => 'textarea',
                '#description' => $lost_pass_email_tags,
                '#description_no_escape' => true,
                '#horizontal' => true,
                '#default_value' => empty($config['login']['lost_pass_email_body']) ? $lost_pass_default_email[1] : $config['login']['lost_pass_email_body'],
                '#rows' => count(explode(PHP_EOL, $lost_pass_default_email[1])),
                '#states' => [
                    'visible' => [
                        $login_form_selector => ['type' => 'checked', 'value' => true],
                        $lost_pass_link_selector => ['type' => 'checked', 'value' => true],
                        $lost_pass_custom_email_selector => ['type' => 'checked', 'value' => true],
                    ],
                ],
                '#weight' => 8,
            ],
        );


        if ($application->getPlatform()->isLoginFormRequired()) {
            $form['login']['form']['#type'] = 'hidden';
        }

        $verify_account_default_email = $application->FrontendSubmit_VerifyAccount_defaultEmail();
        $form['register'] = [
            '#weight' => 20,
            '#title' => __('User Registration Settings', 'directories-frontend'),
            '#tree' => true,
            'form' => array(
                '#type' => 'checkbox',
                '#title' => __('Show user registration form', 'directories-frontend'),
                '#default_value' => !isset($config['register']['form']) || !empty($config['register']['form']),
                '#horizontal' => true,
                '#weight' => 3,
            ),
            'privacy' => [
                '#type' => 'checkbox',
                '#title' => __('Add a privacy policy consent checkbox', 'directories-frontend'),
                '#default_value' => !empty($config['register']['privacy']),
                '#weight' => 5,
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        $register_form_selector = 'input[name="' . $application->Form_FieldName(array_merge($parents, ['register'])) . '[form]"]' => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'verify_email' => [
                '#type' => 'checkbox',
                '#title' => __('Enable e-mail verification', 'directories-frontend'),
                '#default_value' => !empty($config['register']['verify_email']),
                '#weight' => 60,
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        $register_form_selector => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
            'verify_email_settings' => [
                '#title' => __('Email verification settings', 'directories-frontend'),
                '#class' => 'drts-form-label-lg',
                '#states' => [
                    'visible' => [
                        $register_form_selector => ['type' => 'checked', 'value' => true],
                        'input[name="' . $application->Form_FieldName(array_merge($parents, ['register'])) . '[verify_email]"]' => ['type' => 'checked', 'value' => true],
                    ],
                ],
                '#weight' => 90,
                'custom_email' => [
                    '#type' => 'checkbox',
                    '#title' => __('Customize verification e-mail', 'directories-frontend'),
                    '#horizontal' => true,
                    '#default_value' => !empty($config['register']['verify_email_settings']['custom_email']),
                ],
                'email_subject' => [
                    '#type' => 'textfield',
                    '#field_prefix' => __('E-mail subject', 'directories-frontend'),
                    '#description' => $verify_account_email_tags = $application->System_Util_availableTags($application->FrontendSubmit_VerifyAccount_emailTags()),
                    '#description_no_escape' => true,
                    '#horizontal' => true,
                    '#default_value' => empty($config['register']['verify_email_settings']['email_subject']) ? $verify_account_default_email[0] : $config['register']['verify_email_settings']['email_subject'],
                    '#states' => [
                        'visible' => [
                            $custom_email_selector = 'input[name="' . $application->Form_FieldName(array_merge($parents, ['register', 'verify_email_settings'])) . '[custom_email]"]' => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
                'email_body' => [
                    '#type' => 'textarea',
                    '#description' => $verify_account_email_tags,
                    '#description_no_escape' => true,
                    '#horizontal' => true,
                    '#default_value' => empty($config['register']['verify_email_settings']['email_body']) ? $verify_account_default_email[1] : $config['register']['verify_email_settings']['email_body'],
                    '#rows' => count(explode(PHP_EOL, $verify_account_default_email[1])),
                    '#states' => [
                        'visible' => [
                            $custom_email_selector => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
                'check_domain' => [
                    '#type' => 'checkbox',
                    '#title' => __('Blacklist or whitelist e-mail domains', 'directories-frontend'),
                    '#horizontal' => true,
                    '#default_value' => !empty($config['register']['verify_email_settings']['check_domain']),
                ],
                'check_domain_type' => [
                    '#type' => 'select',
                    '#options' => [
                        'blacklist' => __('Blacklist', 'directories-frontend'),
                        'whitelist' => __('Whitelist', 'directories-frontend'),
                    ],
                    '#horizontal' => true,
                    '#default_value' => isset($config['register']['verify_email_settings']['check_domain_type']) ? $config['register']['verify_email_settings']['check_domain_type'] : 'blacklist',
                    '#states' => [
                        'visible' => [
                            $check_domain_selector = 'input[name="' . $application->Form_FieldName(array_merge($parents, ['register', 'verify_email_settings'])) . '[check_domain]"]' => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
                'domains' => [
                    '#type' => 'textarea',
                    '#description' => __('Enter one domain per line. You can also use "*" and "?" wildcard characters.', 'directories-frontend'),
                    '#placeholder' => implode("\r\n", ['user@domain.com', '*@domain.com', 'user?@*.com']),
                    '#horizontal' => true,
                    '#default_value' => isset($config['register']['verify_email_settings']['domains']) ? $config['register']['verify_email_settings']['domains'] : null,
                    '#rows' => 3,
                    '#states' => [
                        'visible' => [
                            $check_domain_selector => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
                'delete' => [
                    '#type' => 'checkbox',
                    '#title' => __('Delete unverified users after X days', 'directories-frontend'),
                    '#horizontal' => true,
                    '#default_value' => !empty($config['register']['verify_email_settings']['delete'])
                ],
                'delete_after' => [
                    '#type' => 'slider',
                    '#horizontal' => true,
                    '#default_value' => isset($config['register']['verify_email_settings']['delete_after']) ? $config['register']['verify_email_settings']['delete_after'] : 30,
                    '#integer' => true,
                    '#min_value' => 1,
                    '#max_value' => 50,
                    '#field_suffix' => __('day(s)', 'directories-frontend'),
                    '#states' => [
                        'visible' => [
                            'input[name="' . $application->Form_FieldName(array_merge($parents, ['register', 'verify_email_settings'])) . '[delete]"]' => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
            ],
        ];

        if ($restrictors = $application->FrontendSubmit_Restrictors()) {
            $bundles = [];
            foreach ($application->getModel('Directory', 'Directory')->fetch(0, 0, array('directory_type', 'directory_name'), array('ASC', 'ASC')) as $directory) {
                foreach ($application->Entity_Bundles_sort(null, 'Directory', $directory->name) as $bundle) {
                    $info = $application->Entity_BundleTypeInfo($bundle);
                    if (empty($info['frontendsubmit_enable'])) continue;

                    $bundles[$bundle->name] = $bundle;
                }
            }
            if (!empty($bundles)) {
                $bundle_labels = [];
                foreach(array_keys($bundles) as $bundle_name) {
                    $bundle = $bundles[$bundle_name];
                    $bundle_labels[$bundle_name] = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel();
                    if (!empty($bundle->info['parent'])
                        && isset($bundles[$bundle->info['parent']])
                    ) {
                        $bundle_labels[$bundle_name] = sprintf(
                            __('%s (per %s)', 'directories-frontend'),
                            $bundle_labels[$bundle_name],
                            $bundles[$bundle->info['parent']]->getLabel('singular')
                        );
                    }
                }
                unset($bundles);
                $form['restrict'] = [
                    '#weight' => 40,
                    '#title' => __('Submission Restriction Settings', 'directories-frontend'),
                    '#tree' => true,
                    'type' => [
                        '#type' => 'select',
                        '#title' => __('Restriction type', 'directories-frontend'),
                        '#options' => ['' => __('— Select —', 'directories-frontend')],
                        '#horizontal' => true,
                        '#default_value' => isset($config['restrict']['type']) ? $config['restrict']['type'] : '',
                    ],
                ];
                $type_field_name = $application->Form_FieldName(array_merge($parents, ['restrict', 'type']));
                foreach (array_keys($restrictors) as $restrictor_name) {
                    if (!$restrictor = $application->FrontendSubmit_Restrictors_impl($restrictor_name, true))continue;

                    $form['restrict']['type']['#options'][$restrictor_name] = $restrictor->frontendsubmitRestrictorInfo('label');
                    if (!$restrictor->frontendsubmitRestrictorEnabled()) {
                        $form['restrict']['type']['#options_disabled'][] = $restrictor_name;
                        continue;
                    }
                    $form['restrict']['settings'][$restrictor_name] = $restrictor->frontendsubmitRestrictorSettingsForm(
                        $bundle_labels,
                        isset($config['restrict']['settings'][$restrictor_name]) ? $config['restrict']['settings'][$restrictor_name] : [],
                        array_merge($parents, ['restrict', 'settings', $restrictor_name])
                    );
                    $form['restrict']['settings'][$restrictor_name]['#states'] = [
                        'visible' => [
                            'select[name="' . $type_field_name . '"]' => ['value' => $restrictor_name],
                        ],
                    ];
                }
            }
        }

        if (!$application->getComponent('FrontendSubmit')->isLoginEnabled()) {
            $form['warning'] = [
                [
                    '#weight' => -1,
                    '#type' => 'item',
                    '#markup' => '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-warning">'
                        . __('The following settings have no effect since login/registration features are disabled.', 'directories-frontend') . '</div>',
                ],
            ];
        }

        return $form;
    }
}
