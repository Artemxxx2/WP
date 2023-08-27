<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class LostPassword extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $this->_cancelUrl = '/' . $this->getComponent('FrontendSubmit')->getSlug('login');
        $this->_submitButtons[] = [
            '#btn_label' => __('Get New Password', 'directories-frontend'),
            '#btn_color' => 'primary',
            '#btn_size' => 'lg',
        ];

        return [
            '#header' => [
                [
                    'level' => 'info',
                    'message' => __('Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'directories-frontend'),
                ],
            ],
            'user' => [
                '#type' => 'textfield',
                '#title' => __('Username or E-mail Address', 'directories-frontend'),
                '#required' => true,
            ],
        ];
    }

    public function submitForm(\SabaiApps\Directories\Component\Form\Form $form, Context $context)
    {
        if (empty($form->values['user'])) {
            $form->setError(__('Invalid username or e-mail address.', 'directories-frontend'), 'user');
            return;
        }

        if (strpos($form->values['user'], '@')) {
            $identity = $this->getPlatform()->getUserIdentityFetcher()->fetchByEmail($form->values['user']);
        } else {
            $identity = $this->getPlatform()->getUserIdentityFetcher()->fetchByUsername($form->values['user']);
        }
        if ($identity->isAnonymous()) {
            $form->setError(__('Invalid username or e-mail address.', 'directories-frontend'), 'user');
            return;
        }

        $this->FrontendSubmit_LostPassword_sendEmail($identity);

        $context->setSuccess('/' . $this->getComponent('FrontendSubmit')->getSlug('login'))
            ->addFlash(__('Password reset email has been sent.', 'directories-frontend'), 'success', 60000);
    }
}