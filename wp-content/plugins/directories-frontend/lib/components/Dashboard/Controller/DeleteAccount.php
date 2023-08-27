<?php
namespace SabaiApps\Directories\Component\Dashboard\Controller;

use SabaiApps\Directories\Component\Form\Controller;
use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Context;

class DeleteAccount extends Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        if ($this->getUser()->isAdministrator()) {
            return [
                'message' => [
                    '#type' => 'item',
                    '#markup' => '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-warning">'
                        . __('Your account may not be deleted.', 'directories-frontend') . '</div>',
                ],
            ];
        }

        $this->_ajaxOnSuccessRedirect = true;
        $this->_submitButtons[] = [
            '#btn_label' => __('Delete account', 'directories-frontend'),
            '#btn_color' => 'danger',
        ];

        return [
            'message' => [
                '#type' => 'item',
                '#markup' => '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-warning">'
                    . __('Are you sure you want to permanently delete your account?', 'directories-frontend') . '</div>',
            ],
            'password' => [
                '#type' => 'password',
                '#title' => __('Current password', 'directories-frontend'),
                '#description' => __('Please enter your current password to confirm.', 'directories-frontend'),
                '#horizontal' => true,
                '#required' => true,
            ],
        ];
    }

    public function submitForm(Form $form, Context $context)
    {
        if (!$this->getPlatform()->isCurrentPassword($form->values['password'], $this->getUser()->getIdentity())) {
            $form->setError(__('Your current password is incorrect.', 'directories-frontend'));
            return;
        }

        if (!$this->getPlatform()->deleteAccount($this->getUser()->getIdentity())) {
            $form->setError(__('Your account could not be deleted.', 'directories-frontend'));
            return;
        }

        $context->setSuccess('/' . $this->getComponent('Dashboard')->getSlug('dashboard'));
    }
}