<?php
namespace SabaiApps\Directories\Component\Payment;

interface IPlan
{
    public function paymentPlanId();
    public function paymentPlanName();
    public function paymentPlanBundleName();
    public function paymentPlanType();
    public function paymentPlanTitle();
    public function paymentPlanDescription();
    public function paymentPlanFeatures();
    public function paymentPlanIsFeatured();
    public function paymentPlanPrice($html = false);
    public function paymentPlanTotal();
    public function paymentPlanIsPurchasable();
    public function paymentPlanIsFree();
}
