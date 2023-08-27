<?php
namespace SabaiApps\Directories\Component\Payment\WordPressNotification;

use SabaiApps\Directories\Component\WordPressContent\Notification\AbstractNotification;
use SabaiApps\Directories\Component\Entity;

class PaymentWordPressNotification extends AbstractNotification
{
    protected function _wpNotificationInfo()
    {
        switch ($this->_name) {
            case 'payment_expiring':
                return array(
                    'label' => __('Expiring', 'directories-payments'),
                    'author_only' => true,
                );
            case 'payment_expired':
                return array(
                    'label' => __('Expired', 'directories-payments'),
                    'author_only' => true,
                );
            case 'payment_deactivated':
                return array(
                    'label' => __('Deactivated', 'directories-payments'),
                    'author_only' => true,
                );
        }
    }
    
    public function wpNotificationSupports(Entity\Model\Bundle $bundle)
    {
        return !empty($bundle->info['payment_enable']);
    }
    
    public function wpNotificationSubject(Entity\Model\Bundle $bundle)
    {
        switch ($this->_name) {
            case 'payment_expiring':
                return sprintf(
                    __('Your %s is about to expire', 'directories-payments'),
                    strtolower($singular = $bundle->getLabel('singular')),
                    $singular
                );
            case 'payment_expired':
                return sprintf(
                    __('Your %s has expired', 'directories-payments'),
                    strtolower($singular = $bundle->getLabel('singular')),
                    $singular
                );
            case 'payment_deactivated':
                return sprintf(
                    __('Your %s has been deactivated', 'directories-payments'),
                    strtolower($singular = $bundle->getLabel('singular')),
                    $singular
                );
        }
    }
    
    public function wpNotificationBody(Entity\Model\Bundle $bundle)
    {
        switch ($this->_name) {
            case 'payment_expiring':
                $singular = $bundle->getLabel('singular');
                $body = array(
                    sprintf(__('Dear %s,', 'directories-payments'), '[post_author]'),
                    sprintf(
                        __('The following %1$s posted on our site will expire in %2$s day(s).', 'directories-payments'),
                        strtolower($singular),
                        '[drts_payment_expire_days]',
                        $singular
                    ),
                    '[post_title]' . PHP_EOL . '[permalink]',
                    sprintf(__('You can renew the %1$s from the dashboard page on our site.', 'directories-payments'), strtolower($singular), $singular)
                        . PHP_EOL . '[drts_dashboard_url post_status="expiring"]',
                );
                break;
            case 'payment_expired':
                $singular = $bundle->getLabel('singular');
                $body = array(
                    sprintf(__('Dear %s,', 'directories-payments'), '[post_author]'),
                    sprintf(
                        __('The following %1$s posted on our site has expired on %2$s.', 'directories-payments'),
                        strtolower($singular),
                        '[drts_payment_expire_on]',
                        $singular
                    ),
                    '[post_title]' . PHP_EOL . '[permalink]',
                    sprintf(__('You can renew the %1$s from the dashboard page on our site.', 'directories-payments'), strtolower($singular), $singular)
                        . PHP_EOL . '[drts_dashboard_url post_status="expired"]',
                    sprintf(
                        __('If you do not renew your %1$s in %2$s days, it will be deactavated and hidden from public view.', 'directories-payments'),
                        strtolower($singular),
                        '[drts_payment_renew_grace_period_days]',
                        $singular
                    ),
                );
                break;
            case 'payment_deactivated':
                $singular = $bundle->getLabel('singular');
                $body = array(
                    sprintf(__('Dear %s,', 'directories-payments'), '[post_author]'),
                    sprintf(
                        __('The following %1$s posted on our site has been deactivated.', 'directories-payments'),
                        strtolower($singular),
                        $singular
                    ),
                    '[post_title]' . PHP_EOL . '[permalink]',
                    sprintf(__('You can reactivate the %1$s from the dashboard page on our site.', 'directories-payments'), strtolower($singular), $singular)
                        . PHP_EOL . '[drts_dashboard_url post_status="deactivated"]',
                );
                break;
            default:
                return;
        }
        
        return implode(PHP_EOL . PHP_EOL, array_filter(array_map('trim', $body)));
    }
}