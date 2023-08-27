<?php
namespace SabaiApps\Directories\Component\Payment\Controller;

use SabaiApps\Directories\Component\Dashboard;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Entity;

class SubmitPost extends Dashboard\Controller\SubmitPost
{
    protected function _getSteps(Context $context, array &$formStorage)
    {
        if (!$steps = parent::_getSteps($context, $formStorage)) return;

        if (!$this->_isPaymentEnabled($context, $formStorage)) return $steps;
        
        if ($this->Payment_Util_hasPendingOrder($context->entity)) {
            $context->setError(
                __('There are currently one or more pending orders for the item selected.', 'directories-payments'),
                '/' . $this->getPlatform()->getSlug('Dashboard', 'dashboard')
            );
            return;
        }
        
        return array('select_plan' => array('order' => 3));
    }
    
    public function _getFormForStepSelectPlan(Context $context, array &$formStorage)
    {
        $form = $this->Payment_Plans_form(
            $context->entity,
            $this->Filter('payment_base_plan_types', ['base'], [$context->entity->getBundleName()]),
            $context->action === 'upgrade',
            false,
            $this->Filter('payment_submit_post_add_free_plan', $context->action !== 'upgrade', [$context->action, $context->entity])
        );
        if (empty($form['plan']['#options'])) {
            $this->_submitable = false;
        } else {
            $this->_submitable = true;
            $this->_submitButtons[] = array(
                '#btn_color' => 'primary',
                '#btn_label' => $this->Filter(
                    'payment_add_to_cart_text',
                    __('Add to cart', 'directories-payments'),
                    [$context->entity->getBundleName(), 'submit']
                ),
                '#btn_size' => 'lg',
                '#attributes' => ['data-modal-title' => ''], // prevents modal title from changing on submit
            );
        }
        return $form;
    }
    
    public function _submitFormForStepSelectPlan(Context $context, Form\Form $form)
    {
        if (!$this->_isPaymentEnabled($context, $form->storage)
            || (!$plan = $this->_getSelectedPlan($context, $form->storage))
        ) {
            // Make sure payment plan is unassigned
            $bundle = $this->_getBundle($context, $form->storage);
            $status = $this->_getEntityStatus($context, $form, $bundle);
            $this->_updateSubmittedEntity($context, $status, ['payment_plan' => false]);
            return;
        }

        if (false === $this->_getPaymentComponent(true)->paymentOnSubmit($context->entity, $plan, $context->action)) {
            $form->storage['payment_no_checkout'] = true;
        }
    }
    
    protected function _getSelectedPlan(Context $context, array $formStorage)
    {
        if (!empty($formStorage['values']['select_plan']['plan'])) {
            return $this->_getPaymentComponent(true)
                ->paymentGetPlan($formStorage['values']['select_plan']['plan']);
        }
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
    
    protected function _complete(Context $context, array $formStorage)
    {
        if (!empty($formStorage['payment_no_checkout'])
            || !$this->_isPaymentEnabled($context, $formStorage)
            || !$this->_getSelectedPlan($context, $formStorage)
        ) {
            parent::_complete($context, $formStorage);
            return;
        }

        $context->setSuccess($this->getComponent('Payment')->getPaymentComponent(true)->paymentCheckoutUrl());
    }

    protected function _getPaymentComponent($throwError = true)
    {
        return $this->getComponent('Payment')->getPaymentComponent($throwError);
    }

    protected function _isPaymentEnabled(Context $context, array $formStorage)
    {
        try {
            $bundle = $this->_getBundle($context, $formStorage);
        } catch (\Exception $e) {
            return false;
        }
        if (empty($bundle->info['payment_enable'])) return false;

        return $this->_getPaymentComponent(false) ? true : false;
    }
}