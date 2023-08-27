<?php
namespace SabaiApps\Directories\Component\WooCommerce;

use SabaiApps\Directories\Component\Payment\ISubscription;

class PaymentSubscription implements ISubscription
{
    protected $_subscription, $_itemId, $_itemName, $_entityId;

    public function __construct(\WC_Subscription $subscription, $itemId, $itemName, $entityId)
    {
        $this->_subscription = $subscription;
        $this->_itemId = $itemId;
        $this->_itemName = $itemName;
        $this->_entityId = $entityId;
    }

    public function paymentSubscriptionId()
    {
        return $this->_subscription->get_id();
    }

    public function paymentSubscriptionItemId()
    {
        return $this->_itemId;
    }

    public function paymentSubscriptionName()
    {
        return $this->_itemName;
    }

    public function paymentSubscriptionStatus()
    {
        $status = $this->_subscription->get_status();
        $name = wcs_get_subscription_status_name($status);
        switch ($status) {
            case 'active':
                return array($name, 'success');
            case 'cancelled':
            case 'expired':
                return array($name, 'danger');
            case 'pending':
            case 'pending-cancel':
            case 'on-hold':
                return array($name, 'warning');
            default:
                return $name;
        }
    }

    public function paymentSubscriptionTotalHtml()
    {
        return $this->_subscription->get_formatted_line_subtotal($this->_subscription->get_item($this->_itemId));
    }

    public function paymentSubscriptionDate()
    {
        return $this->_subscription->get_date_to_display();
    }

    public function paymentSubscriptionEntityId()
    {
        return $this->_entityId;
    }

    public function paymentSubscriptionEntityType()
    {
        return 'post';
    }

    public function paymentSubscriptionHtml()
    {
        ob_start();
        echo '<div class="woocommerce">';
        \WCS_Template_Loader::get_subscription_details_template($this->_subscription);
        \WCS_Template_Loader::get_subscription_totals_template($this->_subscription);
        echo '</div>';

        return ob_get_clean();
    }
}