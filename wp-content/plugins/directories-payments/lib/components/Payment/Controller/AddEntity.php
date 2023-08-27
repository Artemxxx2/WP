<?php
namespace SabaiApps\Directories\Component\Payment\Controller;

use SabaiApps\Directories\Component\FrontendSubmit;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Entity;

class AddEntity extends FrontendSubmit\Controller\AddEntity
{
    protected $_reloadStepsOnNextStep = true;

    protected function _hideSelectPlan(Context $context, array &$formStorage)
    {
        if (!isset($formStorage['hide_select_plan'])) {
            $formStorage['hide_select_plan'] = false;
            $plan_id = trim($context->getRequest()->asStr('plan'));
            if (strlen($plan_id)
                && ($bundle = $this->_getBundle($context, $formStorage))
            ) {
                if (!empty($plan_id)) {
                    if (($payment_component = $this->_getPaymentComponent(false))
                        && ($plan = $payment_component->paymentGetPlan($plan_id))
                        && $plan->paymentPlanBundleName() === $bundle->name
                        && in_array($plan->paymentPlanType(), $this->Filter('payment_base_plan_types', ['base'], [$bundle->name]))
                    ) {
                        $formStorage['hide_select_plan'] = $plan_id;
                    }
                } else {
                    if ($this->Payment_Plans_noPaymentPlanEnabled($bundle->name)) {
                        $formStorage['hide_select_plan'] = 0;
                    }
                }
            }
        }
        return $formStorage['hide_select_plan'];
    }

    protected function _getSteps(Context $context, array &$formStorage)
    {
        if (!$steps = parent::_getSteps($context, $formStorage)) return;

        if (!$this->_isPaymentEnabled($context, $formStorage)) return $steps;

        if (!$this->getUser()->isAnonymous()
            || $this->_isGuestAllowed()
        ) {
            if (false !== ($plan_id = $this->_hideSelectPlan($context, $formStorage))) {
                $formStorage['values']['select_plan']['plan'] = $plan_id;
            } else {
                $steps['select_plan'] = array('order' => 6);
            }
        }

        return $steps;
    }

    protected function _getPageTitle(Context $context, array $formStorage)
    {
        if (!$this->_isPaymentEnabled($context, $formStorage)
            || (!$plan = $this->_getSelectedPlan($context, $formStorage))
        ) return parent::_getPageTitle($context, $formStorage);

        $bundle = $this->_getBundle($context, $formStorage);

        return sprintf(
            __('%s: %s'),
            $bundle->getGroupLabel(),
            $bundle->getLabel('add') . ' - ' . $plan->paymentPlanTitle()
        );
    }

    public function _getFormForStepSelectPlan(Context $context, array &$formStorage)
    {
        $pricing_table_settings = $this->getComponent('Payment')->getConfig('pricing_table');
        $form = $this->Payment_Plans_form(
            $bundle = $this->_getBundle($context, $formStorage)->name,
            $this->Filter('payment_base_plan_types', ['base'], [$bundle]),
            false,
            $context->getContainer() === '#drts-modal' || empty($pricing_table_settings['show']) ? false : $pricing_table_settings
        );
        if (empty($pricing_table_settings['show'])
            && isset($form['plan']['#options'])
            && empty($form['plan']['#options'])
        ) {
            $this->_submitable = false;
        }

        return $form;
    }

    public function _submitFormForStepAdd(Context $context, Form\Form $form)
    {
        parent::_submitFormForStepAdd($context, $form);

        if (!$this->_isPaymentEnabled($context, $form->storage)
            || (!$plan = $this->_getSelectedPlan($context, $form->storage))
        ) return;

        if (!$entity = $this->_getEntity($context, $form->storage)) return false; // this should not happen

        if (false === $this->_getPaymentComponent(true)->paymentOnSubmit($entity, $plan, 'add')) {
            $form->storage['payment_no_checkout'] = true;
        }
    }

    protected function _complete(Context $context, array $formStorage)
    {
        if (!empty($formStorage['payment_no_checkout'])
            || !$this->_isPaymentEnabled($context, $formStorage)
            || !$this->_getSelectedPlan($context, $formStorage)
        ) {
            parent::_complete($context, $formStorage);
            return;
        }

        $entity = $this->_getEntity($context, $formStorage);

        // Set cookie to track guest user
        if ($this->getUser()->isAnonymous()) {
            $this->FrontendSubmit_GuestAuthorCookie($entity);
        }

        $context->setSuccess($this->_getPaymentComponent(true)->paymentCheckoutUrl());
    }

    protected function _getEntityStatus(Context $context, Form\Form $form, Entity\Model\Bundle $bundle)
    {
        if (!$this->_isPaymentEnabled($context, $form->storage)
            || !$this->_getSelectedPlan($context, $form->storage)
        ) {
            return parent::_getEntityStatus($context, $form, $bundle);
        }
        return $this->Entity_Status($bundle->entitytype_name, 'draft');
    }

    protected function _getSelectedPlan(Context $context, array $formStorage)
    {
        if (!empty($formStorage['values']['select_plan']['plan'])) {
            return $this->_getPaymentComponent(true)
                ->paymentGetPlan($formStorage['values']['select_plan']['plan']);
        }
    }

    protected function _getSubmitButtonForStepAdd(Context $context, array &$formStorage)
    {
        if (!$this->_isPaymentEnabled($context, $formStorage)
            || !$this->_getSelectedPlan($context, $formStorage)
        ) {
            return parent::_getSubmitButtonForStepAdd($context, $formStorage);
        }

        return $this->Filter(
            'payment_add_to_cart_text',
            __('Add to cart', 'directories-payments'),
            [$this->_getBundle($context, $formStorage)->name, 'add', $formStorage['values']['select_plan']['plan']]
        );
    }

    protected function _getRedirectGuestUrlParams(Context $context, array $formStorage)
    {
        $ret = parent::_getRedirectGuestUrlParams($context, $formStorage);
        $ret[] = 'plan';

        return $ret;
    }

    protected function _getPaymentComponent($throwError = true)
    {
        return $this->getComponent('Payment')->getPaymentComponent($throwError);
    }

    protected function _isGuestAllowed()
    {
        if (!$payment_component = $this->_getPaymentComponent(false)) return false;

        return $payment_component->paymentIsGuestCheckoutEnabled()
            || $payment_component->paymentIsGuestSignupEnabled()
            || $payment_component->paymentIsGuestLoginEnabled();
    }

    protected function _isPaymentEnabled(Context $context, array $formStorage)
    {
        if ($this->getUser()->isAnonymous()
            && !$this->_isGuestAllowed()
        ) return false;

        try {
            $bundle = $this->_getBundle($context, $formStorage);
        } catch (\Exception $e) {
            return false;
        }
        if (empty($bundle->info['payment_enable'])) return false;

        return $this->_getPaymentComponent(false) ? true : false;
    }

    protected function _getSubmitEntityFormOptions(Context $context, array &$formStorage, $wrap = null)
    {
        $ret = parent::_getSubmitEntityFormOptions($context, $formStorage, $wrap);
        if ($this->_isPaymentEnabled($context, $formStorage)
            && ($plan = $this->_getSelectedPlan($context, $formStorage))
        ) {
            $ret['payment_plan'] = $plan;
        }
        return $ret;
    }
}
