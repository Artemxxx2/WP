<?php
namespace SabaiApps\Directories\Component\Payment;

interface IOrder
{
    public function paymentOrderId($display = false);
    public function paymentOrderName();
    public function paymentOrderAction();
    public function paymentOrderAdminUrl();
    public function paymentOrderStatus();
    public function paymentOrderTotalHtml();    
    public function paymentOrderTimestamp();   
    public function paymentOrderEntityId();
    public function paymentOrderEntityType();
    public function paymentOrderHtml();
    public function paymentOrderWasItemDeactivated();
    /**
     * @return IPlan
     * @throws \SabaiApps\Directories\Exception\RuntimeException
     */
    public function paymentOrderPlan();
}