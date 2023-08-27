<?php
namespace SabaiApps\Directories\Component\Payment;

interface IGateways
{
    /**
     * @return array
     */
    public function paymentGetGatewayNames();

    /**
     * @param $name string
     * @return IGateway
     */
    public function paymentGetGateway($name);
}