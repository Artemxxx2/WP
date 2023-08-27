<?php
namespace SabaiApps\Directories\Component\Dashboard\Controller;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;

class ChangePassword extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $this->_submitButtons[] = [
            '#btn_label' => __('Change password', 'directories-frontend'),
            '#btn_color' => 'primary',
        ];

        return [
            'password' => [
                '#type' => 'password',
                '#title' => __('Current password', 'directories-frontend'),
                '#horizontal' => true,
                '#required' => true,
            ],
            'new_password' => [
                '#type' => 'password',
                '#title' => __('New password', 'directories-frontend'),
                '#horizontal' => true,
                '#required' => true,
            ],
            'new_password_confirm' => [
                '#type' => 'password',
                '#title' => __('Confirm new password', 'directories-frontend'),
                '#horizontal' => true,
                '#required' => true,
            ],
        ];
    }

    public function submitForm(Form\Form $form, Context $context)
    {
        if ($form->values['new_password'] !== $form->values['new_password_confirm']) {
            $form->setError(__('New passwords do not match.', 'directories-frontend'));
            return;
        }

        if (!$this->getPlatform()->isCurrentPassword($form->values['password'], $this->getUser()->getIdentity())) {
            $form->setError(__('Your current password is incorrect.', 'directories-frontend'));
            return;
        }

        $this->getPlatform()->changePassword($form->values['new_password'], $this->getUser()->getIdentity());

        $context->setSuccess()->addFlash(__('Your password changed successfully.', 'directories-frontend'));
    }
}