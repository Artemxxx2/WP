<?php
namespace SabaiApps\Directories\Component\Payment;

interface ISubscription
{
    public function paymentSubscriptionId();
    public function paymentSubscriptionItemId();
    public function paymentSubscriptionName();
    public function paymentSubscriptionStatus();
    public function paymentSubscriptionTotalHtml();
    public function paymentSubscriptionDate();
    public function paymentSubscriptionHtml();
}