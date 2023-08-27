<?php
namespace SabaiApps\Directories\Component\Payment\Gateway;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment\IPlan;

interface IGateway
{
    public function paymentGatewayInfo($key = null);
    public function paymentIsEnabled();
    public function paymentIsGuestCheckoutEnabled();
    public function paymentIsGuestSignupEnabled();
    public function paymentIsGuestLoginEnabled();
    /*
     * @return string
     */
    public function paymentGetCurrency($symbol = false);
    public function paymentGetPlanIds($bundleName, $lang = null);
    /*
     * @return IPlan|null
     */
    public function paymentGetPlan($id);
    /*
     * @return IOrder|null
     */
    public function paymentGetOrder($orderId);
    /*
     * @return array
     */
    public function paymentGetOrders($entityId, $limit = 0, $offset = 0);
    /*
     * @return array
     */
    public function paymentGetUserOrders($userId, $limit = 0, $offset = 0);    
    /*
     * @return int
     */
    public function paymentCountUserOrders($userId);
    /*
     * @return bool
     */
    public function paymentHasPendingOrder(Entity\Type\IEntity $entity, array $actions);
    
    public function paymentOnSubmit(Entity\Type\IEntity $entity, IPlan $plan, $action);
    /*
     * @return string
     */
    public function paymentCheckoutUrl();
    /*
     * @return bool
     */
    public function paymentIsSubscriptionEnabled();
    /*
    * @return ISubscription|null
    */
    public function paymentGetSubscription($subscriptionId, $itemId);
    /*
     * @return array
     */
    public function paymentGetUserSubscriptions($userId, $limit = 0, $offset = 0);
    /*
     * @return int
     */
    public function paymentCountUserSubscriptions($userId);
    /*
     * @return int
     */
    public function paymentGetClaimOrderId($claimId);

    public function paymentRefundOrder($orderId, $reason = '');

    public function paymentGetPlanId($planId, $lang);

    public function paymentSettingsForm(array $settings, array $parents);
}