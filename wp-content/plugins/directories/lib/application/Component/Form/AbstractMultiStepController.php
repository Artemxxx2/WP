<?php
namespace SabaiApps\Directories\Component\Form;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;

abstract class AbstractMultiStepController extends Controller
{
    const STEP_PARAM_NAME = '_drts_form_step';
    
    protected $_steps, $_reloadStepsOnNextStep = false, $_doneCheckStep;
    
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {
        // Reset all properties
        $this->_submitable = true;
        $this->_submitButtons = [];
        $this->_ajaxSubmit = null;
        $this->_ajaxCancelType = null;
        $this->_ajaxCancelUrl = null;
        $this->_ajaxOnCancel = 'function(target){}';
        $this->_ajaxOnSuccess = null;
        $this->_ajaxOnSuccessRedirect = true;
        $this->_ajaxOnError = null;
        $this->_ajaxOnErrorRedirect = true;
        $this->_ajaxOnContent = null;
        $this->_cancelUrl = null;
        $this->_cancelWeight = 99;
        $this->_successFlash = null;
        
        if (!$this->_steps = $this->_getAndSortSteps($context, $formStorage)) return false;

        // Make sure the current step is the same as the step submitted.
        // They may not be the same when the browser back button was used.
        if (!$this->_doneCheckStep) {
            if (($buttons = $context->getRequest()->get(FormComponent::FORM_SUBMIT_BUTTON_NAME))
                && ($submitted_step = array_shift($buttons))
                && isset($formStorage['step']) // this may be null for some reason on some sites
                && $submitted_step !== $formStorage['step']
                && isset($formStorage['values'][$submitted_step]) // make sure the step is already processed previously
            ) {
                $formStorage['step'] = $submitted_step;
            }
            $this->_doneCheckStep = true; // should be checked once only
        }

        if (isset($formStorage['step'])
            && ($formStorage['step'] !== $this->_getFirstStep($context))
        ) {
            $context->currentStep = $formStorage['step'];
            // Get form for the current step
            if (!$form = $this->_getForm($context->currentStep, $context, $formStorage)) {
                if ($form === false) {
                    if (!$context->isError()) {
                        $context->setError('An error occurred while fetching form for step ' . $context->currentStep);
                    }
                    return false;
                }
                $this->_complete($context, $formStorage);
                return;
            }
        } else {
            $formStorage['step'] = $context->currentStep = $this->_getFirstStep($context);
            // Get form for the current step
            if (!$form = $this->_getForm($context->currentStep, $context, $formStorage)) {
                if ($form === false) {
                    if (!$context->isError()) {
                        $context->setError('An error occurred while fetching form for step ' . $context->currentStep);
                    }
                    return false;
                }
                $this->_complete($context, $formStorage);
                return;
            }
            $form['#disable_back_btn'] = true;            
        }
        if (false !== $this->_submitButtons) {
            if (empty($this->_submitButtons)) {
                $this->_submitButtons[$context->currentStep] = array(
                    '#btn_label' => false !== $this->_getNextStep($context, $formStorage) ? __('Next &raquo;', 'directories') : null,
                    '#btn_color' => 'primary',
                    '#btn_size' => 'lg',
                    '#weight' => 10,
                    '#attributes' => array('data-modal-title' => 'false'), // prevent modal title from changing
                );
            }
            if (empty($form['#disable_back_btn'])) {
                $this->_submitButtons['back'] = array(
                    '#btn_label' => __('&laquo; Previous', 'directories'),
                    '#btn_color' => 'outline-secondary',
                    '#weight' => -10,
                    '#submit' => array(
                        0 => array(
                            array(array($this, 'previousForm'), array($context))
                        ),
                    ),
                    '#skip_validate' => true, // skip validating the currently displayed form
                    '#attributes' => array(
                        'data-modal-title' => 'false',
                        'class' => $context->getRequest()->isAjax() ? 'drts-form-back-btn' : 'drts-form-back-btn drts-form-back-btn-no-ajax',
                    ),
                );
            }
        } else {
            $this->_submitButtons = []; // must be an array to prevent default buttons from being added by Controller 
        }
        $name = $this->_getFormName(get_class($this));
        $form['#enable_storage'] = true;
        if (!isset($form['#token_id'])) {
            $form['#token_id'] = $name;
        }
        $form['#name'] = $name . '_' . $context->currentStep;
        
        return $form;
    }
    
    protected function _buildForm(array $settings, array &$storage)
    {
        $values = isset($storage['values'][$storage['step']]) ? $storage['values'][$storage['step']] : null;
        return $this->Form_Build($settings, true, $values);
    }

    public function previousForm(Form $form, Context $context)
    {
        if (empty($form->settings['#back_to'])) {
            if (false === $previous_step = $this->_getPreviousStep($context)) {
                // this should never happen
                throw new Exception\RuntimeException('Previus step does not exist');
            }
        } else {
            $previous_step = $form->settings['#back_to'];
        }
        $context->clearTemplates(); // clear template files specified by the other step
        $form->storage['step'] = $previous_step;
        $form->values = $form->storage['values'][$form->storage['step']];
        $form->rebuild = true;
        $form->settings = $this->_getFormSettings($context, $form->settings['#build_id'], $form->storage);
        return false; // stop processing the form
    }

    final public function submitForm(Form $form, Context $context)
    {
        // Save submitted form values
        unset($form->values[FormComponent::FORM_SUBMIT_BUTTON_NAME]);
        $form->storage['values'][$context->currentStep] = $form->values;

        // Call submit callback if any exists
        if (false === $this->_submitForm($context->currentStep, $context, $form)) return;
        
        // Return if error or redirect
        if ($context->isError()
            || $context->isRedirect()
        ) return;

        // One or more steps may have been skipped, so make sure there are more steps afterwards.
        if (false === $next_step = $this->_getNextStep($context, $form->storage, $this->_reloadStepsOnNextStep)) {
            if (!$form->redirect
                && !$this->_isInProgress
            ) {
                // Add the _complete() method to the end of the callback stack
                $form->settings['#submit'][] = array(array(array($this, 'complete'), array($context)));
            }
            return;
        }

        $context->clearTemplates(); // clear template files specified by the current step
        
        // Advance to the next step
        $form->storage['step'] = $next_step;
        if (!$form->redirect) {
            $form->rebuild = true;
            $form->settings['#submit'][] = array(array(array($this, 'getFormSettingsCallback'), array($context)));
        }
    }
    
    final public function complete(Form $form, Context $context)
    {
        $this->_complete($context, $form->storage);
    }
    
    public function getFormSettingsCallback(Form $form, Context $context)
    {
        $settings = $this->_getFormSettings($context, $form->settings['#build_id'], $form->storage);
        unset($settings['#submit']); // prevent submit callbacks of the next step form being called
        $form->settings = $settings;
    }

    final protected function _skipStep(Context $context, array &$formStorage, $skipTo = null)
    {
        if (!isset($skipTo)) {
            if (!$skipTo = $this->_getNextStep($context, $formStorage)) {
                return false;
            }
        }
        $context->currentStep = $formStorage['step'] = $skipTo;
        return $context->currentStep;
    }
    
    protected function _skipStepAndGetForm(Context $context, array &$formStorage, $skipTo = null)
    {
        return ($step = $this->_skipStep($context, $formStorage, $skipTo)) ? $this->_getForm($step, $context, $formStorage) : [];
    }
    
    protected function _getAndSortSteps(Context $context, array &$formStorage)
    {
        if (!$steps = $this->_getSteps($context, $formStorage)) return false;
        
        foreach (array_keys($steps) as $i => $step_name) {
            if (!isset($steps[$step_name]['order'])) {
                $steps[$step_name]['order'] = $i;
            }
        }
        uasort($steps, function ($a, $b) { return $a['order'] <= $b['order'] ? -1 : 1; });
        return $steps;
    }
    
    protected function _getNextStep(Context $context, array &$formStorage, $reloadSteps = false)
    {
        if ($context->currentStep === false) return false;
        
        $steps = array_keys($reloadSteps ? $this->_getAndSortSteps($context, $formStorage) : $this->_steps);
        
        $next_step_key_index = array_search($context->currentStep, $steps) + 1;
        
        return isset($steps[$next_step_key_index]) ? $steps[$next_step_key_index] : false;
    }
    
    protected function _getPreviousStep(Context $context)
    {
        if ($context->currentStep === false) end(array_values($this->_steps));
        
        $steps = array_keys($this->_steps);
        $previous_step_key_index = array_search($context->currentStep, $steps) - 1;
        
        return isset($steps[$previous_step_key_index]) ? $steps[$previous_step_key_index] : false;
    }
    
    protected function _getFirstStep(Context $context)
    {
        return current(array_keys($this->_steps));
    }
    
    protected function _getForm($step, Context $context, array &$formStorage)
    {
        do {
            $callback = isset($this->_steps[$step]['callback']) ? $this->_steps[$step]['callback'] : array($this, '_getFormForStep' . $this->_camelize($step));
        } while ((!$form = $this->CallUserFuncArray($callback, array($context, &$formStorage, $step)))
            && false !== $form
            && ($step = $this->_skipStep($context, $formStorage))
        );
        
        return $form;
    }
    
    protected function _submitForm($step, Context $context, Form $form)
    {
        if (isset($this->_steps[$step]['submit_callback'])) {
            $callback = $this->_steps[$step]['submit_callback'];
            if (is_callable($callback)) {
                return $this->CallUserFuncArray($callback, array($context, $form, $step));
            }
        } else {
            $method = '_submitFormForStep' . $this->_camelize($step);
            if (method_exists($this, $method)) {
                return $this->CallUserFuncArray(array($this, $method), array($context, $form, $step));
            }
        }
    }
    
    protected function _camelize($str)
    {
        return str_replace(' ', '', ucwords(str_replace(array('_', '-'), ' ', $str)));
    }
    
    protected function _isBack(Context $context)
    {
        return (bool)$this->_isButtonPressed($context, 'back');
    }
    
    protected function _isButtonPressed(Context $context, $button = 0)
    {
        if ($context->getRequest()->has(FormComponent::FORM_BUILD_ID_NAME)
            && ($buttons = $context->getRequest()->get(FormComponent::FORM_SUBMIT_BUTTON_NAME))
            && ($keys = array_keys($buttons))
            && array_shift($keys) === $button
        ) {
            return $buttons[$button];
        }
        return false;
    }
    
    protected function _getSubimttedValues(Context $context, array &$formStorage)
    {
        $values = null;
        // Pass form values if form has been submitted. Usually, this is not needed to initialize form settings
        // but the entity form needs to check values to see if any form fields have been added dynamically (via JS) by the user.
        if ($context->getRequest()->isPostMethod()
            && $context->getRequest()->has(FormComponent::FORM_BUILD_ID_NAME)
        ) {
            if ($this->_isBack($context)) {
                if (isset($formStorage['values'][$context->currentStep])) {
                    $values = $formStorage['values'][$context->currentStep];
                }
            } elseif ($this->_isButtonPressed($context, $context->currentStep)) {
                $values = $context->getRequest()->getParams();
            }
        }
        
        return $values;
    }

    /**
     * @return array
     */
    abstract protected function _getSteps(Context $context, array &$formStorage);
    
    abstract protected function _complete(Context $context, array $formStorage);
}