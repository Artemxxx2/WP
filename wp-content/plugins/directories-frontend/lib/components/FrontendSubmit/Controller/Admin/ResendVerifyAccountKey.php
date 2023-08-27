<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Exception;

class ResendVerifyAccountKey extends Controller
{
    protected function _doExecute(Context $context)
    {
        if (!$this->_checkToken($context, 'frontendsubmit_admin_resend_verify_account_key', true)
            || (!$unverified_at = $this->FrontendSubmit_VerifyAccount_isRequired($context->identity->id))
        ) {
            $context->setError();
            return;
        }
        try {
            $this->FrontendSubmit_VerifyAccount_sendEmail($context->identity);
            $context->addFlash(__('Verification e-mail sent successfully.', 'directories-frontend'))
                ->setSuccess(
                    false, // false required to send success messages
                    [
                        'title' => sprintf(
                            __('Account unverified since %s, verification e-mail last sent at %s', 'directories-frontend'),
                            $this->System_Date($unverified_at),
                            $this->System_Date_datetime(time())
                        ),
                    ]
                );
        } catch (Exception\IException $e) {
            $context->setError($e->getMessage());
        }
    }
}