<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Exception;

class VerifyAccount extends Controller
{
    protected function _doExecute(Context $context)
    {
        if (!$this->_checkToken($context, 'frontendsubmit_admin_verify_account', true)) {
            $context->setError();
            return;
        }

        if ($this->FrontendSubmit_VerifyAccount_isRequired($context->identity->id)) {
            try {
                $this->FrontendSubmit_VerifyAccount($context->identity, true);
            } catch (Exception\IException $e) {
                $context->setError($e->getMessage());
                return;
            }
        }
        $context->addFlash(__('Account verified successfully.', 'directories-frontend'))
            ->setSuccess(false); // false required to send success messages
    }
}