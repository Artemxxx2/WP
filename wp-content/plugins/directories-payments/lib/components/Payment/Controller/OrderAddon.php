<?php
namespace SabaiApps\Directories\Component\Payment\Controller;

use SabaiApps\Directories\Component\FrontendSubmit;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;

class OrderAddon extends FrontendSubmit\Controller\AbstractSubmitEntity
{
    protected function _getSteps(Context $context, array &$formStorage)
    {    
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
            'addon',
            false,
            false
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
                    [$context->entity->getBundleName(), 'order_addon']
                ),
            );
        }
        return $form;
    }

    public function _submitFormForStepSelectPlan(Context $context, Form\Form $form)
    {
        if (!$plan = $this->_getSelectedPlan($context, $form->storage)) return false; // this should not happen

        $this->getComponent('Payment')->getPaymentComponent(true)
            ->paymentOnSubmit($context->entity, $plan, 'order_addon');
    }

    protected function _getSelectedPlan(Context $context, array $formStorage)
    {
        if (!empty($formStorage['values']['select_plan']['plan'])) {
            return $this->getComponent('Payment')->getPaymentComponent(true)
                ->paymentGetPlan($formStorage['values']['select_plan']['plan']);
        }
    }

    protected function _complete(Context $context, array $formStorage)
    {
        $context->setSuccess($this->getComponent('Payment')->getPaymentComponent(true)->paymentCheckoutUrl());
    }
}
