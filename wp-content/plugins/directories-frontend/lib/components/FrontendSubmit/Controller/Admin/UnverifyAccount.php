<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller\Admin;

use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form\Controller;
use SabaiApps\Directories\Exception;

class UnverifyAccount extends Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $this->_submitButtons = null;
        $this->_ajaxOnSuccessRedirect = $this->_ajaxOnErrorRedirect = false;
        $this->_ajaxOnSuccess = 'function (result, target, trigger) {
    target.find(".drts-modal-close").click();
    if (result.messages) DRTS.flash(result.messages, "success");
    $(DRTS).trigger("drts_frontendsubmit_unverify_account", {
        id: ' . $context->identity->id . ',
        title: result.title
    });
}';
        $has_key = $this->FrontendSubmit_VerifyAccount_hasKey($context->identity);
        $has_valid_key = $has_key && $has_key[1] > time();
        $form = [
            'send_key' => [
                '#type' => 'checkbox',
                '#title' => __('Send verification e-mail', 'directories-frontend'),
                '#default_value' => !$has_valid_key,
                '#horizontal' => true,
                '#weight' => 2,
            ],
            'id' => [
                '#type' => 'hidden',
                '#default_value' => $context->identity->id,
            ],
        ];
        if ($has_key) {
            if (empty($has_key[2])) {
                $time = 'N/A';
            } else {
                $time = $this->System_Date_datetime($has_key[2]);
                if ($has_valid_key) {
                    $time = sprintf(__('%s (expires at %s)', 'directories-frontend'), $time, $this->System_Date_datetime($has_key[1]));
                } else {
                    $time = sprintf(__('%s (expired)', 'directories-frontend'), $time);
                }
            }
            $form['#header'][] = [
                'level' => 'info',
                'message' => sprintf(__('Verification e-mail was last sent at %s.', 'directories-frontend'), $time),
            ];
        }

        return $form;
    }

    public function submitForm(Form $form, Context $context)
    {
        try {
            $this->FrontendSubmit_VerifyAccount_unverify($context->identity, false);
            $context->addFlash(__('Account unverified.', 'directories-frontend'));
        } catch (Exception\IException $e) {
            $context->setError($e->getMessage());
            return;
        }
        if (!empty($form->values['send_key'])) {
            try {
                $this->FrontendSubmit_VerifyAccount_sendEmail($context->identity);
                $context->addFlash(__('Verification e-mail sent successfully.', 'directories-frontend'));
                $key_sent = true;
            } catch (Exception\IException $e) {
                $context->addFlash($e->getMessage(), 'danger');
            }
        }
        $context->setSuccess(
            false, // false required to send success messages
            [
                'title' => sprintf(
                    __('Account unverified since %s, verification e-mail last sent at %s', 'directories-frontend'),
                    $this->System_Date(time()),
                empty($key_sent) ? 'N/A' : $this->System_Date_datetime(time())
                ),
            ]
        );
    }
}