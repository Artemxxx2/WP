<?php
namespace SabaiApps\Directories\Component\Form;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Controller as BaseController;

abstract class Controller extends BaseController
{
    protected $_submitable = true, $_submitButtons = [],
        $_ajaxSubmit, $_ajaxCancelType, $_ajaxCancelUrl, $_ajaxContainer, $_ajaxTarget, $_ajaxOnCancel,
        $_ajaxOnSuccess, $_ajaxOnSuccessRedirect = true, $_ajaxOnSuccessEdit, $_ajaxOnSuccessDelete,
        $_ajaxOnError, $_ajaxOnErrorRedirect = true,
        $_ajaxOnContent, $_ajaxModalHideOnSend = false, $_ajaxModalHideOnSuccess = true, $_ajaxOnSubmit, 
        $_ajaxLoadingImage = true,
        $_cancelUrl, $_cancelWeight = 99, $_successFlash,
        $_tableContainer,
        $_addDefaultTemplate = true,
        $_isInProgress = false,
        $_storageAsOption = false;

    protected function _doExecute(Context $context)
    {        
        // Initialize form storage bin
        $form_storage = [];
        
        // Check if form build ID has been sent in the request and fill storage if cached with the build ID
        if ($form_build_id = $context->getRequest()->asStr(FormComponent::FORM_BUILD_ID_NAME, null)) {
            if (preg_match('/^[a-f0-9]+$/', $form_build_id)) {
                $form_storage = $this->getComponent('Form')->getFormStorage($form_build_id, $this->_storageAsOption);
                if (null === $form_storage || !is_array($form_storage)) {
                    $form_storage = [];
                }
            } else {
                $form_build_id = null;
            }
        }

        // Fetch form settings
        if (!$form_settings = $this->_getFormSettings($context, $form_build_id, $form_storage)) {
            // Set error message if not set and the returned value is false
            if ($form_settings === false && !$context->isError()) {
                $context->setError();
            }

            return;
        }

        // Build the form
        $form = $this->_buildForm($form_settings, $form_storage);
        
        // Validate form and submit
        if ($this->_submitable) {
            if ($form->submit($context->getRequest()->getParams())
                && !$context->isError()
                && !$form->rebuild
            ) {
                if (!empty($form->settings['#enable_storage'])
                    && !$this->_isInProgress
                ) {
                    // Clear form storage
                    $this->getComponent('Form')->clearFormStorage($form->settings['#build_id'], $this->_storageAsOption);
                }
                if ($form->redirect) {
                    // Redirecting to another site, but should be redirecting back to the form, so do not clear storage here
                    $context->setView()
                        ->addTemplate('form_redirect')
                        ->setAttributes(array(
                            'url' => $form->redirect,
                            'message' => $form->redirectMessage ? $form->redirectMessage : __('Redirecting...', 'directories'),
                        ));
                } else {
                    if (!$context->isRedirect()
                        && !$context->isSuccess()
                        && !($context->isView() && $context->hasTemplate())
                    ) {
                        $context->setSuccess();
                    }
                    if ($context->isSuccess()) {
                        if ($this->_ajaxOnSuccessEdit
                            || $this->_ajaxOnSuccessDelete
                        ) {
                            $context->setSuccess(false); // required to send success messages
                        }
                        $this->Action('form_submit_' . $form->settings['#name'] . '_success', array($form));
                        if (isset($this->_successFlash)) {
                            $context->addFlash($this->_successFlash);
                        }
                    }
                }
                return;
            }
            // If error is set, clear form storage and do not display the form
            if ($context->isError()) {
                if (!empty($form->settings['#enable_storage'])) {
                    $this->getComponent('Form')->clearFormStorage($form->settings['#build_id'], $this->_storageAsOption);
                }
                return;
            }
            
            $context->setView();
        }
        
        $form->settings['#js'] = isset($form->settings['#js']) ? (array)$form->settings['#js'] : [];
        $form->settings['#js_ready2'][] = $this->_ajaxSubmit ?
            $this->_getAjaxFormScript($context, empty($form->settings['#action']) ? (string)$this->Url($context->getRoute()) : $form->settings['#action']) :
            $this->_getFormScript($context);
        $context->form = $form;
        if (!$context->hasTemplate() && $this->_addDefaultTemplate) {
            $context->addTemplate('form_form');
        }
    }
    
    protected function _buildForm(array $settings, array &$storage)
    {
        return $this->Form_Build($settings);
    }

    final protected function _getFormSettings(Context $context, $buildId, array &$storage)
    {
        // Load the form settings
        $form = $this->_doGetFormSettings($context, $storage);

        // Make sure an array is returned by the _getForm() method if displaying a form
        if (!is_array($form)) return $form;
        
        // Get all inherited class names
        if (!isset($form['#inherits'])) {
            $form['#inherits'] = [];
        }
        $class = get_class($this);
        while (__CLASS__ !== $class = get_parent_class($class)) {
            $form['#inherits'][] = $this->_getFormName($class);
        }

        // Auto define form name if not alreaady set, otherwise add to #inherits
        if (!isset($form['#name']) || strlen($form['#name']) === 0) {
            $form['#name'] = $this->_getFormName(get_class($this));
        } else {
            $form['#inherits'][] = $this->_getFormName(get_class($this));
        }
        
        // Initialize some required form properties
        if (isset($buildId)
            && !isset($form['#build_id'])
        ) {
            $form['#build_id'] = $buildId;
        }
        $form['#initial_storage'] = $storage;
        $form['#storage_as_option']  = $this->_storageAsOption;
        if (!isset($form['#action'])) {
            $form['#action'] = $this->Url($context->getRoute());
        }

        // Create form cancel link
        $cancel_link = null;
        if ($context->getRequest()->isXhr()
            && ($ajax_param = $context->getRequest()->isAjax())
            && $ajax_param !== '#drts-content'
        ) {
            if (!isset($this->_ajaxSubmit)) {
                $this->_ajaxSubmit = true;
            }
            if (!$context->getRequest()->isModal()) { // no cancel link for modal
                // Create cancel link that will close the form
                $cancel_link = $this->_getAjaxCancelLink($ajax_param);
            }
            if (!$this->_ajaxOnSuccessRedirect) {
                // No redirection, so show flash messages immediately and do not them for the next page load
                $context->setFlashEnabled(false);
            }
        } else {
            if (!isset($this->_ajaxSubmit)) {
                $this->_ajaxSubmit = false;
            }
            if (isset($this->_cancelUrl)) {
                $cancel_link = sprintf(
                    '<a href="%1$s" class="%2$btn %2$sbtn-link drts-form-cancel">%3$s</a>',
                    $this->Url($this->_cancelUrl),
                    DRTS_BS_PREFIX,
                    $this->H(__('cancel', 'directories'))
                );
            }
        }

        if ($this->_submitable         
            && ($submits = $this->Form_SubmitButtons($this->_submitButtons, array('default_callback' => $this->_getDefaultSubmitCallback($context))))
        ) {
            if (isset($cancel_link)) {
                $submits['cancel'] = array(
                    '#type' => 'markup',
                    '#markup' => $cancel_link,
                    '#weight' => $this->_cancelWeight,
                );
            }
            if (!isset($form[FormComponent::FORM_SUBMIT_BUTTON_NAME])) {
                $form[FormComponent::FORM_SUBMIT_BUTTON_NAME] = $submits;
            } else {
                $form[FormComponent::FORM_SUBMIT_BUTTON_NAME] += $submits;
            }
        }

        return $form;
    }
    
    protected function _getFormName($controllerClass)
    {
        $parts = explode('\\', substr($controllerClass, strlen('SabaiApps\\Directories\\Component\\')));
        unset($parts[1]); // remove Controller part
        return strtolower(implode('_', $parts));
    }
    
    protected function _getDefaultSubmitCallback(Context $context)
    {
        return array(array($this, 'submitForm'), array($context));
    }

    private function _getAjaxCancelLink($ajaxParam)
    {
        if ($this->_ajaxCancelType === 'hide' || isset($this->_ajaxOnCancel)) {
            return sprintf(
                '<a class="%1$sbtn %1$sbtn-link drts-form-cancel" href="#" onclick="jQuery(\'%2$s\').hide(\'fast\'); var callback = %4$s; callback.call(this, jQuery(\'%2$s\')); return false">%3$s</a>',
                DRTS_BS_PREFIX,
                $this->H($ajaxParam),
                $this->H(__('cancel', 'directories')),
                isset($this->_ajaxOnCancel) ? str_replace('"', "'", $this->_ajaxOnCancel) : 'function(target){}'
            );
        }
        
        if ($this->_ajaxCancelType === 'remote' && isset($this->_cancelUrl)) {
            return $this->LinkTo(
                __('cancel', 'directories'),
                $this->_cancelUrl,
                array('container' => $ajaxParam, 'url' => $this->_ajaxCancelUrl, 'scroll' => true),
                array('class' => '' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-link drts-form-cancel')
            );
        }
    }
    
    protected function _getFormScript(Context $context)
    {
        return 'DRTS.Form.form("#__FORM_ID__");';
    }
    
    protected function _getAjaxFormScript(Context $context, $url)
    {
        if ($url instanceof \SabaiApps\Framework\Application\Url) {
            $url->fragment = '';
            $url->separator = '&';
        } else {
            if ($pos = strpos($url, '#')) {
                $url = substr($url, 0, $pos);
            }
        }
        if (!isset($this->_ajaxOnSuccess)) {
            // Highlight on edit success?
            if (isset($this->_ajaxOnSuccessEdit)) {
                $this->_ajaxOnSuccess = 'function (result, target, trigger) {
    if (target.attr("id") === "drts-modal") {
        target.find(".drts-modal-close").click();
    } else {
        target.hide();
    }
    if ($.effects && $.effects.effect.highlight) {
        $("' . $this->H($this->_ajaxOnSuccessEdit, ENT_COMPAT) . '").effect("highlight", {}, 1000);
    }
}';
                $this->_ajaxOnSuccessRedirect = false;
            }
            // Highlight and remove on delete success?
            if (isset($this->_ajaxOnSuccessDelete)) {
                $this->_ajaxOnSuccess = 'function (result, target, trigger) {
    if (target.attr("id") === "drts-modal") {
        target.find(".drts-modal-close").click();
    } else {
        target.hide();
    }
    var deleteTarget = $("' . $this->H($this->_ajaxOnSuccessDelete, ENT_COMPAT) . '"),
        options = {color:"#f8d7da"};
    if ($.effects && $.effects.effect.highlight) {
        deleteTarget.find("> td").effect("highlight", options, 400).end().effect("highlight", options, 400, function () {
            var $this = $(this);
            $this.fadeTo("fast", 0, function () {
                $this.slideUp("fast", function () {
                    $this.remove();
                });
            });
        });
    } else {
        deleteTarget.fadeTo("fast", 0, function () {
            var $this = $(this);
            $this.slideUp("fast", function () {
                $this.remove();
            });
        });
    }
}';
                $this->_ajaxOnSuccessRedirect = false;
            }
        }
        
        return sprintf('DRTS.Form.ajaxForm("#__FORM_ID__", "%1$s", "%2$s", {
    onSuccess: %3$s,
    onError: %4$s,
    onContent: %5$s,
    onSuccessRedirect: %6$s,
    onErrorRedirect: %7$s,
    target: "%8$s",
    scroll: true,
    onSubmit: %9$s,
    loadingImage: %10$s,
    modalHideOnSend: %11$s,
    modalHideOnSuccess: %12$s,
});',
            $this->H($this->_ajaxContainer ? $this->_ajaxContainer: $context->getContainer()),
            $url,
            $this->_ajaxOnSuccess ? $this->_ajaxOnSuccess : 'null',
            $this->_ajaxOnError ? $this->_ajaxOnError : 'null',
            $this->_ajaxOnContent ? $this->_ajaxOnContent : 'null',
            $this->_ajaxOnSuccessRedirect ? 'true' : 'false',
            $this->_ajaxOnErrorRedirect ? 'true' : 'false',
            $this->_ajaxTarget ? $this->H($this->_ajaxTarget) : (($target = $context->getTarget()) ? $this->H($target) : ''),
            $this->_ajaxOnSubmit ? $this->_ajaxOnSubmit : 'null',
            $this->_ajaxLoadingImage ? 'true' : 'false',
            $this->_ajaxModalHideOnSend ? 'true' : 'false',
            $this->_ajaxModalHideOnSuccess ? 'true' : 'false'
        );
    }
    
    protected function _makeTableSortable(Context $context, array &$element, array $sortableHeaders, array $timestampHeaders = [], $currentSort = null, $currentOrder = 'DESC', array $params = [])
    {
        if ($element['#type'] !== 'tableselect') return;
        
        $link_options = array(
            'container' => isset($this->_tableContainer) ? $this->_tableContainer : $context->getContainer(),
            'no_escape' => true
        );
        foreach ($sortableHeaders as $header_name) {
            if (is_array($header_name)) {
                $no_escape = true;
                $title = @$header_name['title'];
                $header_name = $header_name['name'];
            } else {
                $no_escape = false;
            }
            if (!isset($element['#header'][$header_name])) continue;
            
            if (!is_array($element['#header'][$header_name])) {
                $element['#header'][$header_name] = array(
                    'label' => $element['#header'][$header_name],
                    'no_escape' => true,
                );
            }
            $header_label = $element['#header'][$header_name]['label'];
            $attr = array(
                'title' => isset($title) ? $title : sprintf(__('Sort by: %s', 'directories'), $header_label),
                'data-modal-title' => '', // keep current modal title
            );
            if (!$no_escape) {
                $header_label = $this->H($header_label);
            }
            $_params = array('sort' => $header_name) + $params;
            if ($currentSort === $header_name) {
                $class = $currentOrder === 'ASC' ? 'up' : 'down';
                $header_label = $header_label . ' <i class="fas fa-sort-' . $class . '"></i>';
                $_params['order'] = $currentOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                $header_label = $header_label . ' <i class="fas fa-sort"></i>';
            }
            $element['#header'][$header_name]['label'] = $this->LinkTo(
                $header_label,
                $this->Url((string)$context->getRoute(), $_params),
                $link_options,
                $attr
            );
        }
    }
    
    protected function _getSubimttedValues(Context $context, array &$formStorage)
    {
        $values = null;
        // Pass form values if form has been submitted. Usually, this is not needed to initialize form settings
        // but the entity form needs to check values to see if any form fields have been added dynamically (via JS) by the user.
        if ($context->getRequest()->isPostMethod()
            && $context->getRequest()->has(FormComponent::FORM_BUILD_ID_NAME)
        ) {
            $values = $context->getRequest()->getParams();
        }
        
        return $values;
    }

    public function submitForm(Form $form, Context $context){}

    abstract protected function _doGetFormSettings(Context $context, array &$storage);

    protected function _initProgress(Context $context, $progressMsg = null)
    {
        $context->addTemplate('system_progress');
        $context->progress_message = $progressMsg;
        $this->_ajaxSubmit = true;
        $this->_ajaxOnSubmit = $this->Form_Progress_formSubmitJs();
        $this->_ajaxOnSuccess = $this->Form_Progress_onSuccessJs();
        $this->_ajaxOnSuccessRedirect = $this->_ajaxOnErrorRedirect = false;
        $this->_ajaxLoadingImage = $this->_ajaxModalHideOnSuccess = false;
    }

    protected function _isInProgress(Context $context, $done, $total, $message = null, array $logs = [])
    {
        $this->_isInProgress = true;
        $attr = [
            'total' => $total,
            'next' => $done + 1,
            'percent' => round(($done / $total) * 100),
            'message' => $message,
        ];
        if (isset($message)) {
            $this->logDebug(strip_tags($message));
        }
        if (!empty($logs)) {
            $attr += $this->_logProgress($logs);
        }
        $context->setSuccess(null, $attr);
    }

    protected function _logProgress(array $logs)
    {
        $ret = [];
        foreach (array_keys($logs) as $level) {
            foreach ((array)$logs[$level] as $log) {
                switch ($level) {
                    case 'success':
                        $this->logDebug(strip_tags($log));
                        $ret[$level][] = $log;
                        break;
                    case 'info':
                    case 'notice':
                        $this->logNotice(strip_tags($log));
                        $ret['info'][] = $log;
                        break;
                    case 'warning':
                        $this->logWarning(strip_tags($log));
                        $ret[$level][] = $log;
                        break;
                    case 'error':
                        $this->logError(strip_tags($log));
                        $ret[$level][] = $log;
                        break;
                    default:
                }
            }
        }
        return $ret;
    }

    protected function _completeProgress(Context $context, $url = null, $message = null, array $logs = [], $downloadFile = null)
    {
        $attr = [];
        if (isset($downloadFile)) {
            $attr['download_file'] = $downloadFile;
        }
        if ($url === false) {
            // No redirection
            if (isset($message)) {
                $attr['message'] = $message;
            }
        } else {
            if (isset($message)) {
                $context->setFlashEnabled()->addFlash($message);
            }
        }
        if (!empty($logs)) {
            $attr += $this->_logProgress($logs);
        }
        if (isset($message)) {
            $this->logDebug(strip_tags($message));
        }
        $context->setSuccess($url, $attr);

    }
}