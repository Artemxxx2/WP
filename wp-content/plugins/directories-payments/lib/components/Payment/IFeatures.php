<?php
namespace SabaiApps\Directories\Component\Payment;

interface IFeatures
{
    public function paymentGetFeatureNames();
    public function paymentGetFeature($name);
}