<?php
namespace SabaiApps\Directories\Component\Payment\DashboardPanel;

use SabaiApps\Directories\Component\Dashboard;
use SabaiApps\Framework\User\AbstractIdentity;

class PaymentsDashboardPanel extends Dashboard\Panel\AbstractPanel
{    
    protected function _dashboardPanelInfo()
    {   
        return [
            'label' => __('Payments', 'directories-payments'),
            'weight' => 10,
            'wp_um_icon' => 'um-faicon-money',
        ];
    }
    
    protected function _dashboardPanelLinks(array $settings, AbstractIdentity $identity = null)
    {
        if (isset($identity)) return; // Do not show if public dashboard

        if (!$payment_component = $this->_application->getComponent('Payment')->getPaymentComponent()) return;
        
        $ret = [
            'orders' => [
                'title' => __('Orders', 'directories-payments'),
                'icon' => 'fas fa-shopping-basket',
                'weight' => 5,
            ],
        ];
        if ($payment_component->paymentIsSubscriptionEnabled()) {
            $ret['subscriptions'] = [
                'title' => __('Subscriptions', 'directories-payments'),
                'icon' => 'fas fa-sync-alt',
                'weight' => 10,
            ];
        }

        return $ret;
    }
    
    public function dashboardPanelContent($link, array $settings, array $params, AbstractIdentity $identity = null)
    {
        if (isset($identity)) return;

        switch ($link) {
            case 'subscriptions':
                $path = '/payment_subscriptions';
                break;
            default:
                $path = '/payment_orders';
        }
        return $this->_application->getPlatform()->render(
            $this->_application->getComponent('Dashboard')->getPanelUrl('payment_payments', $link, $path, [], true),
            ['is_dashboard' => false] // prevent rendering duplicate panel sections on reload panel
        );
    }
}