<?php
namespace SabaiApps\Directories\Component\Search\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Search\SearchComponent;

class Search extends Form\Controller
{    
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $this->_submitable = $context->getContainer() === '#drts-content';
        return $this->Search_Form(
            $context->bundle,
            $this->Search_Form_params($context),
            $context->settings ?: [],
            $this->Filter('search_form_submit_url', $context->bundle->getPath() . '/search', [$context->bundle]),
            $this->_getDefaultSubmitCallback($context)
        );
    }
    
    public function submitForm(Form\Form $form, Context $context)
    {
        $params = $context->getRequest()->getParams();
        unset(
            $params[Form\FormComponent::FORM_SUBMIT_BUTTON_NAME],
            $params[Form\FormComponent::FORM_BUILD_ID_NAME],
            $params[SearchComponent::FORM_SEARCH_PARAM_NAME]
        );
        $this->Action('search_search', array($context->bundle, $params));
        if (defined('DRTS_FIX_URI_TOO_LONG') && DRTS_FIX_URI_TOO_LONG) {
            $search_form_params_cache_id = md5(serialize($params));
            $this->_application->getPlatform()->setCache($params, 'search-form-params-' . $search_form_params_cache_id, 600);
            $params = [SearchComponent::FORM_SEARCH_PARAM_NAME => $search_form_params_cache_id];
        } else {
            $params[SearchComponent::FORM_SEARCH_PARAM_NAME] = 1;
        }

        $context->setRedirect($this->Url($context->bundle->getPath(), $params));
    }
}