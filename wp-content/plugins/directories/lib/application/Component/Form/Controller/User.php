<?php
namespace SabaiApps\Directories\Component\Form\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class User extends Controller
{
    protected function _doExecute(Context $context)
    {
        if (!$context->getRequest()->isAjax()) {
            $context->setBadRequestError();
            return;
        }

        $term = trim($context->getRequest()->asStr('query'));
        if (strlen($term) < 2) {
            $context->setBadRequestError();
            return;
        }

        $limit = 20;
        $offset = ($context->getRequest()->asInt($this->getPlatform()->getPageParam(), 1) - 1) * $limit;
        $context->identities = $this->getPlatform()->getUserIdentityFetcher()->search($term, $limit, $offset, 'name', 'ASC');
        $context->addTemplate('form_user');
    }
}