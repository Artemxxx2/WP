<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Controller;

class ResendVerifyAccountKey extends Controller
{
    protected function _doExecute(Context $context)
    {
        if (!$this->_checkToken($context, 'frontendsubmit_resend_verify_account_key', true)) {
            $context->setError();
            return;
        }

        try {
            $this->FrontendSubmit_VerifyAccount_sendEmail($context->identity);
            $context->addFlash(__('Please check your email address to verify your account.', 'directories-frontend'))
                ->setSuccess('/' . $this->getComponent('FrontendSubmit')->getSlug('login'));
        } catch (Exception\IException $e) {
            $context->setError($e->getMessage());
        }
    }
}