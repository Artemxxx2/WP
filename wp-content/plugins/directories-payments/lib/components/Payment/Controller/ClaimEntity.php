<?php
namespace SabaiApps\Directories\Component\Payment\Controller;

use SabaiApps\Directories\Component\FrontendSubmit;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Entity;

class ClaimEntity extends FrontendSubmit\Controller\AddChildEntity
{
    protected $_reloadStepsOnNextStep = true;

    protected function _getSteps(Context $context, array &$formStorage)
    {
        if (!$steps = parent::_getSteps($context, $formStorage)) return;

        if (!$this->_isPaymentEnabled($context, $formStorage)) return $steps;

        if (!$this->getUser()->isAnonymous()
            || $this->_isGuestAllowed($context, $formStorage)
        ) {
            $steps['select_plan'] = array('order' => 6);
        }

        return $steps;
    }

    public function _getFormForStepSelectPlan(Context $context, array &$formStorage)
    {
        $parent_bundle = $this->Entity_Bundle($context->entity->getBundleName(), null, '', true);
        $pricing_table_settings = $this->getComponent('Payment')->getConfig('pricing_table');
        $form = $this->Payment_Plans_form(
            $parent_bundle->name,
            $this->Filter('payment_base_plan_types', ['base'], [$parent_bundle->name]),
            false,
            $context->getContainer() === '#drts-modal' || empty($pricing_table_settings['show']) ? false : $pricing_table_settings
        );
        if (!empty($pricing_table_settings['show'])
            || !empty($form['plan']['#options'])
        ) {
            $form['#action'] = $this->_getAddEntityUrl($context);
        } else {
            $this->_submitable = false;
        }
        $this->_cancelUrl = $this->Entity_Url($context->entity);
        return $form;
    }

    public function _submitFormForStepAdd(Context $context, Form\Form $form)
    {
        parent::_submitFormForStepAdd($context, $form);

        if (!$this->_isPaymentEnabled($context, $form->storage)
            || (!$plan = $this->_getSelectedPlan($context, $form->storage))
        ) return;

        if (!$entity = $this->_getEntity($context, $form->storage)) return false; // this should not happen

        if (false === $this->_getPaymentComponent(true)->paymentOnSubmit($entity, $plan, 'claim')) {
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
            [$this->_getBundle($context, $formStorage)->name, 'claim', $formStorage['values']['select_plan']['plan']]
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

    protected function _isGuestAllowed(Context $context, array $formStorage)
    {
        return $this->Filter('claiming_is_guest_claimable', false, [$this->_getBundle($context, $formStorage)]);
    }

    protected function _isPaymentEnabled(Context $context, array $formStorage)
    {
        if ($this->getUser()->isAnonymous()
         && !$this->_isGuestAllowed($context, $formStorage)
        ) return false;

        if ((!$parent_bundle = $this->Entity_Bundle($context->entity->getBundleName()))
            || empty($parent_bundle->info['payment_enable'])
        ) return false;

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
