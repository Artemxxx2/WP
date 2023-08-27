<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\User\RegisteredIdentity;

class LostPasswordHelper
{
    public function sendEmail(Application $application, RegisteredIdentity $identity)
    {
        if (empty($identity->email)) {
            throw new Exception\RuntimeException(__('Invalid username or e-mail address.', 'directories-frontend'));
        }

        $tags = $this->emailTags($application, $identity);
        if ($application->getComponent('FrontendSubmit')->getConfig('login', 'lost_pass_custom_email')) {
            $subject = strtr($application->getComponent('FrontendSubmit')->getConfig('login', 'lost_pass_email_subject'), $tags);
            $body = strtr($application->getComponent('FrontendSubmit')->getConfig('login', 'lost_pass_email_body'), $tags);
            $is_html = (bool)$application->getComponent('FrontendSubmit')->getConfig('login', 'lost_pass_email_is_html');
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
        /* translators: Lost password email subject. %s: Site name */
        $subject = sprintf(__('[%s] Password reset', 'directories-frontend'), isset($tags) ? $tags['%%site_name%%'] : '%%site_name%%');
        $body = sprintf(__('Hello %s,', 'directories-frontend'), isset($tags) ? $tags['%%user_name%%'] : '%%user_name%%') . "\r\n\r\n";
        $body .= __('Someone has requested a password reset for the following account:', 'directories-frontend') . "\r\n\r\n";
        /* translators: %s: site name */
        $body .= sprintf(__('Site Name: %s', 'directories-frontend'), (isset($tags) ? $tags['%%site_name%%'] : '%%site_name%%')) . "\r\n";
        /* translators: %s: user login */
        $body .= sprintf(__('Username: %s', 'directories-frontend'), isset($tags) ? $tags['%%user_name%%'] : '%%user_name%%') . "\r\n\r\n";
        $body .= __('To reset your password, visit the following address:', 'directories-frontend') . "\r\n";
        $body .= (isset($tags) ? $tags['%%reset_password_url%%'] : '%%reset_password_url%%') . "\r\n\r\n";
        $body .= __('If this was a mistake, just ignore this email and nothing will happen.', 'directories-frontend') . "\r\n\r\n";
        $body .= (isset($tags) ? $tags['%%site_name%%'] : '%%site_name%%') . "\r\n" . (isset($tags) ? $tags['%%site_url%%'] : '%%site_url%%');

        return [$subject, $body];
    }

    public function emailTags(Application $application, RegisteredIdentity $identity = null)
    {
        if (!isset($identity)) {
            return [
                '%%site_name%%',
                '%%site_url%%',
                '%%user_username%%',
                '%%user_name%%',
                '%%user_email%%',
                '%%reset_password_url%%',
            ];
        }

        return [
            '%%site_name%%' => $application->getPlatform()->getSiteName(),
            '%%site_url%%' => $application->getPlatform()->getSiteUrl(),
            '%%user_username%%' => $identity->username,
            '%%user_name%%' => $identity->name,
            '%%user_email%%' => $identity->email,
            '%%reset_password_url%%' => $application->MainUrl(
                '/' . $application->getComponent('FrontendSubmit')->getSlug('login') . '/reset_password',
                [
                    'key' => $application->getPlatform()->getResetPasswordKey($identity),
                    'id' => $identity->id,
                ],
                '',
                '&'
            ),
        ];
    }
}