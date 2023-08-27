<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;
use SabaiApps\Framework\User\RegisteredIdentity;

class VerifyAccountHelper
{
    protected $_regex;

    public function help(Application $application, RegisteredIdentity $identity, $key)
    {
        if ($key !== true) {
            // Check key
            if (!$stored_key = $this->hasKey($application, $identity, $key)) {
                throw new Exception\RuntimeException();
            }
            // Check if key expired
            if ($stored_key[1] < time()) {
                throw new Exception\RuntimeException(sprintf(
                    __('Your account verification key has expired. Click <a href="%s">here</a> to resend a verification e-mail.', 'directories-frontend'),
                    $this->resendKeyUrl($application, $identity)
                ));
            }
        }

        $application->getPlatform()
            ->deleteEntityMeta('user', $identity->id, 'frontendsubmit_verify_account_required')
            ->deleteEntityMeta('user', $identity->id, 'frontendsubmit_verify_account_key')
            ->deleteEntityMeta('user', $identity->id, 'frontendsubmit_verify_account_key_sent_at')
            ->setEntityMeta('user', $identity->id, 'frontendsubmit_account_verified_at', time());

        $application->Action('frontendsubmit_account_verified', [$identity]);
    }

    public function unverify(Application $application, RegisteredIdentity $identity, $sendKey = true)
    {
        $application->getPlatform()
            ->setEntityMeta('user', $identity->id, 'frontendsubmit_verify_account_required', time())
            ->deleteEntityMeta('user', $identity->id, 'frontendsubmit_account_verified_at');

        if ($sendKey) $this->sendEmail($application, $identity);

        $application->Action('frontendsubmit_account_unverified', [$identity]);
    }

    public function hasKey(Application $application, RegisteredIdentity $identity, $keyToCheck = null)
    {
        // Get stored key
        if ((!$stored_key = $application->getPlatform()->getEntityMeta('user', $identity->id, 'frontendsubmit_verify_account_key'))
            || (!$stored_key_parts = explode(':', $stored_key))
            || count($stored_key_parts) !== 2
            || (isset($keyToCheck) && $keyToCheck !== $stored_key_parts[1])
        ) {
            return false;
        }

        // Get expiration timestamp
        $expires = 0;
        if ($account_key_lifetime = $application->Filter('frontendsubmit_verify_account_key_lifetime', 86400)) {
            $expires = $stored_key_parts[0] + $account_key_lifetime;
        }

        return [
            $stored_key_parts[1], // key
            $expires, // expiration timestamp
            $application->getPlatform()->getEntityMeta('user', $identity->id, 'frontendsubmit_verify_account_key_sent_at'), // key sent timestamp
        ];
    }

    public function isRequired(Application $application, $id)
    {
        return (empty($id)
            || (!$required_at = $application->getPlatform()->getEntityMeta('user', $id, 'frontendsubmit_verify_account_required'))
        ) ? false : $required_at;
    }

    public function isRequiredByEmail(Application $application, $email)
    {
        if (!$verify_email_settings = $application->getComponent('FrontendSubmit')->getConfig('register', 'verify_email_settings')) return false;

        if (empty($verify_email_settings['check_domain'])) return true; // no domain check, always require verification

        // Init regex
        if (!isset($this->_regex)) {
            $this->_regex = [];
            if (!empty($verify_email_settings['domains'])
                && ($domains = explode(PHP_EOL, (string)$verify_email_settings['domains']))
            ) {
                foreach ($domains as $domain) {
                    $regex = strtr(trim($domain), ['/' => '', '*' => '%%wildcard%%', '?' => '%%wildcard1%%']);
                    $regex = preg_quote($regex);
                    $regex = strtr($regex, ['%%wildcard%%' => '\S*', '%%wildcard1%%' => '\S{1}']);
                    $this->_regex[] = '/^' . $regex . '$/';
                }
            }
        }

        $type = $verify_email_settings['check_domain_type'] === 'whitelist' ? 'white' : 'black';
        foreach ($this->_regex as $regex) {
            if (preg_match($regex, $email)) return $type === 'black'; // matched, return true if blacklist
        }
        return $type === 'white'; // none matched, return true if whitelist
    }

    public function sendEmail(Application $application, RegisteredIdentity $identity)
    {
        if (empty($identity->email)) {
            throw new Exception\RuntimeException(__('Invalid username or e-mail address.', 'directories-frontend'));
        }

        $key = sha1($identity->email . time());
        $timestamp = time();
        $application->getPlatform()
            ->setEntityMeta('user', $identity->id, 'frontendsubmit_verify_account_key', $timestamp . ':' . $key)
            ->setEntityMeta('user', $identity->id, 'frontendsubmit_verify_account_key_sent_at', $timestamp);
        $tags = $this->emailTags($application, $identity, $key);
        if (($verify_email_settings = $application->getComponent('FrontendSubmit')->getConfig('register', 'verify_email_settings'))
            && !empty($verify_email_settings['custom_email'])
        ) {
            $subject = strtr($verify_email_settings['email_subject'], $tags);
            $body = strtr($verify_email_settings['email_body'], $tags);
            $is_html = !empty($verify_email_settings['email_is_html']);
        } else {
            list($subject, $body) = $this->defaultEmail($application, $tags);
            $is_html = false;
        }
        if (!$application->getPlatform()->mail($identity->email, $subject, $body, ['is_html' => $is_html])) {
            throw new Exception\RuntimeException('Failed sending email to ' . $identity->email);
        }
    }

    public function defaultEmail(Application $application, array $tags = null)
    {
        /* translators: Verify account email subject. %s: Site name */
        $subject = sprintf(__('[%s] Verify your account', 'directories-frontend'), isset($tags) ? $tags['%%site_name%%'] : '%%site_name%%');
        $body = sprintf(__('Hello %s,', 'directories-frontend'), isset($tags) ? $tags['%%user_name%%'] : '%%user_name%%') . "\r\n\r\n";
        $body .= __('Please click the following link to verify your account:', 'directories-frontend') . "\r\n";
        $body .= (isset($tags) ? $tags['%%verify_account_url%%'] : '%%verify_account_url%%') . "\r\n\r\n";
        $body .= __('If this was a mistake, just ignore this email and nothing will happen.', 'directories-frontend') . "\r\n\r\n";
        $body .= (isset($tags) ? $tags['%%site_name%%'] : '%%site_name%%') . "\r\n" . (isset($tags) ? $tags['%%site_url%%'] : '%%site_url%%');

        return [$subject, $body];
    }

    public function emailTags(Application $application, RegisteredIdentity $identity = null, $key = null)
    {
        if (!isset($identity)) {
            return [
                '%%site_name%%',
                '%%site_url%%',
                '%%user_name%%',
                '%%user_email%%',
                '%%verify_account_url%%',
            ];
        }

        return [
            '%%site_name%%' => $application->getPlatform()->getSiteName(),
            '%%site_url%%' => $application->getPlatform()->getSiteUrl(),
            '%%user_name%%' => $identity->name,
            '%%user_email%%' => $identity->email,
            '%%verify_account_url%%' => $application->MainUrl(
                '/' . $application->getComponent('FrontendSubmit')->getSlug('login') . '/verify_account',
                ['key' => $key, 'id' => $identity->id],
                '',
                '&'
            ),
        ];
    }

    public function resendKeyUrl(Application $application, RegisteredIdentity $identity)
    {
        return $application->MainUrl(
            '/' . $application->getComponent('FrontendSubmit')->getSlug('login') . '/resend_verify_account_key',
            [
                'id' => $identity->id,
                Request::PARAM_TOKEN => $application->Form_Token_create('frontendsubmit_resend_verify_account_key', 1800, true),
            ]
        );
    }

    public function deleteUnverified(Application $application, $days, &$logs)
    {
        $users = $application->getPlatform()->getUsersByMeta(
            'frontendsubmit_verify_account_required',
            time() - ($days * 86400),
            100,
            0,
            'ASC',
            true,
            '<'
        );
        foreach ($users as $user) {
            if ($application->IsAdministrator($user)) continue;

            try {
                $application->getPlatform()->deleteAccount($user);
            } catch (Exception\IException $e) {
                $logs['error'][] = $e->getMessage();
                continue;
            }
            $logs['success'][] = sprintf(__('Deleted unverified user account: %s', 'directories-frontend'), $user->username);
        }
    }

    public function wordPressUsersColumn(Application $application)
    {
        $vars = [
            'verifyAccountUrl' => (string)$application->AdminUrl('/_drts/frontendsubmit/verify_account', [
                Request::PARAM_TOKEN => $application->Form_Token_create('frontendsubmit_admin_verify_account', 1800, true),
            ], '', '&'),
            'unverifyAccountUrl' => (string)$application->AdminUrl('/_drts/frontendsubmit/unverify_account', [], '', '&'),
            'resendVerifyAccountKeyUrl' => (string)$application->AdminUrl('/_drts/frontendsubmit/resend_verify_account_key', [
                Request::PARAM_TOKEN => $application->Form_Token_create('frontendsubmit_admin_resend_verify_account_key', 1800, true),
            ], '', '&'),
            'verifyAccountText' => __('Verify account', 'directories-frontend'),
            'unverifyAccountText' => __('Unverify account', 'directories-frontend'),
            'resendVerifyAccountKeyText' => __('Send verification e-mail', 'directories-frontend'),
        ];
        $js_handle = 'drts-frontendsubmit-verify-account-wordpress';
        $application->getPlatform()->loadDefaultAssets()
            ->addJsFile('form.min.js', 'drts-form', ['drts']) // for modal ajax form
            ->addJsFile('frontendsubmit-verify-account-wordpress.min.js', $js_handle, null, 'directories-frontend')
            ->addJsInline($js_handle, 'DRTS.FrontendSubmit.verifyAccountWP(' . $application->JsonEncode($vars) . ');');

        // Add status column
        add_filter('manage_users_columns', function ($columns) use ($application) {
            $columns['drts_frontendsubmit_verify'] = __('Status', 'directories-frontend');
            return $columns;
        });

        // Display status column
        add_filter('manage_users_custom_column', function ($output, $column, $userId) use ($application) {
            if ($column === 'drts_frontendsubmit_verify') {
                $output = '<span class="drts">';
                if ($required_timestamp = $this->isRequired($application, $userId)) {
                    $key_sent_at = $application->getPlatform()->getEntityMeta('user', $userId, 'frontendsubmit_verify_account_key_sent_at');
                    $title = sprintf(
                        __('Account unverified since %s, verification e-mail last sent at %s', 'directories-frontend'),
                        $application->System_Date($required_timestamp),
                        $key_sent_at ? $application->System_Date_datetime($key_sent_at) : 'N/A'
                    );
                    $output .= '<span rel="sabaitooltip" class="' . DRTS_BS_PREFIX . 'text-danger" title="' . $application->H($title) . '"><i class="fas fa-times-circle fa-lg"></i></span>';
                } elseif ($verified_at = $application->getPlatform()->getEntityMeta('user', $userId, 'frontendsubmit_account_verified_at')) {
                    $title = sprintf(
                        __('Account verified on %s', 'directories-frontend'),
                        $application->System_Date($verified_at)
                    );
                    $output .= '<span rel="sabaitooltip" class="' . DRTS_BS_PREFIX . 'text-success" title="' . $application->H($title) . '"><i class="fas fa-check-circle fa-lg"></i></span>';
                } else {
                    $output .= '<span rel="sabaitooltip" class="" title=""><i class="fas fa-lg"></i></span>';
                }
                $output .= '</span>';
            }
            return $output;
        }, 10, 3);

        // Add verify account actions
        add_filter('user_row_actions', function ($actions, $wpuser) use ($application, $vars) {
            if (!user_can($wpuser, 'edit_users')) {
                $user_id = $wpuser->ID;
                if ($this->isRequired($application, $user_id)) {
                    $actions['drts_frontendsubmit_verify_account'] = '<a href="#" data-user-id="' . $user_id . '">' . $application->H($vars['verifyAccountText']) . '</a>';
                    $actions['drts_frontendsubmit_resend_verify_account_key'] = '<a href="#" data-user-id="' . $user_id . '">' . $application->H($vars['resendVerifyAccountKeyText']) . '</a>';
                } else {
                    $actions['drts_frontendsubmit_unverify_account'] = '<a href="#" data-user-id="' . $user_id . '">' . $application->H($vars['unverifyAccountText']) . '</a>';
                }
            }
            return $actions;
        }, 10, 2);

        // Add verified/unverified filter
        add_action('restrict_manage_users', function ($which) use ($application) {
            if ($which !== 'top') return;

            $name = 'drts_frontendsubmit_verify_account_status';
            $options = [
                '' => __('Verified/Unverified', 'directories-frontend'),
                'verified' =>  __('Show all verified', 'directories-frontend'),
                'unverified' =>  __('Show all unverified', 'directories-frontend'),
            ];
            $selected = isset($_GET[$name]) ? $_GET[$name] : '';
            $html = ['<select name="' . $name . '">'];
            foreach (array_keys($options) as $option_key) {
                $_selected = $selected && $selected == $option_key ? ' selected="selected"' : '';
                $html[] = '<option value="' . $application->H($option_key) . '"' . $_selected . '>' . $application->H($options[$option_key]) . '</option>';
            }
            $html[] = '</select>';
            echo implode(PHP_EOL, $html);
        });

        // Filter users by verification status
        add_filter('pre_get_users', function ($query) {
            if (is_admin() && 'users.php' == $GLOBALS['pagenow']) {
                $name = 'drts_frontendsubmit_verify_account_status';
                if (!empty($_GET[$name])
                    && in_array($_GET[$name], ['verified', 'unverified'])
                ) {
                    if ($_GET[$name] === 'verified') {
                        $key = 'frontendsubmit_account_verified_at';
                        $value = 0;
                        $compare = '>';
                    } else {
                        $key = 'frontendsubmit_verify_account_required';
                        $value = '';
                        $compare = '!=';
                    }
                    $query->set('meta_query', [[
                        'key' => $GLOBALS['wpdb']->prefix . 'drts_' . $key,
                        'value' => $value,
                        'compare' => $compare,
                    ]]);
                }
            }
        });
    }
}