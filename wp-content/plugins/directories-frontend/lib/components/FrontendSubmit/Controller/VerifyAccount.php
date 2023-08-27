<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Exception;

class VerifyAccount extends Controller
{
    protected function _doExecute(Context $context)
    {
        try {
            $this->FrontendSubmit_VerifyAccount($context->identity, $context->key);
        } catch (Exception\IException $e) {
            $context->setError($e->getMessage());
            return;
        }

        // Login and redirect
        if (($logged_in = $this->getPlatform()->setCurrentUser($context->identity->id))
            && ($redirect = $this->_getSavedRedirect($context->identity->id))
        ) {
            if (is_string($redirect)) {
                $url = $redirect;
            } else {
                $url = $this->Url($redirect['route'], $redirect['params']);
            }
        } else {
            if ($logged_in) {
                if ($this->isComponentLoaded('Dashboard')
                    && ($dashboard_slug = $this->getComponent('FrontendSubmit')->getSlug('dashboard'))
                ) {
                    $url = '/' . $dashboard_slug;
                }
            } elseif ($login_slug = $this->getComponent('FrontendSubmit')->getSlug('login')) {
                $url = '/' . $login_slug;
            }
            if (!isset($url)) {
                $url = $this->getPlatform()->getSiteUrl();
            }
            $url = $this->Filter('frontendsubmit_after_login_register_url', $url, ['register', $context->identity]);
        }
        $context->addFlash(__('Account verified successfully.', 'directories-frontend'))->setSuccess($url);
    }

    protected function _getSavedRedirect($id)
    {
        if ($redirect = $this->getPlatform()->getEntityMeta('user', $id, 'frontendsubmit_verify_account_redirect')) {
            $this->getPlatform()->deleteEntityMeta('user', $id, 'frontendsubmit_verify_account_redirect');
            $redirect = @unserialize($redirect);
        }
        return $redirect;
    }
}