<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class ResetPassword extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $this->_submitButtons[] = [
            '#btn_label' => __('Reset Password', 'directories-frontend'),
            '#btn_color' => 'primary',
        ];

        return [
            '#header' => [
                [
                    'level' => 'info',
                    'message' => __('Enter your new password below.', 'directories-frontend'),
                ],
            ],
            'password' => [
                '#type' => 'password',
                '#title' => __('New Password', 'directories-frontend'),
                '#required' => true,
            ],
            'password_confirm' => [
                '#type' => 'password',
                '#title' => __('Confirm New Password', 'directories-frontend'),
                '#required' => true,
            ],
            'key' => [
                '#type' => 'hidden',
                '#default_value' => $context->getRequest()->asStr('key'),
            ],
            'id' => [
                '#type' => 'hidden',
                '#default_value' => $context->identity->id,
            ],
        ];
    }

    public function submitForm(\SabaiApps\Directories\Component\Form\Form $form, Context $context)
    {
        if ($form->values['password'] !== $form->values['password_confirm']) {
            $form->setError(__('Passwords do not match.', 'directories-frontend'), 'password_confirm');
            return;
        }

        // Delete cookie
        $this->System_Cookie('drts-frontendsubmit-reset-password', '', time() - 86400);

        $this->getPlatform()->resetPassword($form->values['password'], $form->values['key'], $context->identity);

        $context->setSuccess('/' . $this->getComponent('FrontendSubmit')->getSlug('login'))
            ->addFlash(__('Your password has been reset successfully.', 'directories-frontend'), 'success', 60000);
    }
}