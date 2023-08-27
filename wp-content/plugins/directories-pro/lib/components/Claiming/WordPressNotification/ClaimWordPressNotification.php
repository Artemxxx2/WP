<?php
namespace SabaiApps\Directories\Component\Claiming\WordPressNotification;

use SabaiApps\Directories\Component\WordPressContent\Notification\AbstractNotification;
use SabaiApps\Directories\Component\Entity;

class ClaimWordPressNotification extends AbstractNotification
{
    protected function _wpNotificationInfo()
    {
        switch ($this->_name) {
            case 'claiming_pending':
                return array(
                    'label' => __('Claim pending review', 'directories-pro'),
                    'author_only' => false,
                );
            case 'claiming_approved':
                return array(
                    'label' => __('Claim approved', 'directories-pro'),
                    'author_only' => true,
                );
            case 'claiming_rejected':
                return array(
                    'label' => __('Claim rejected', 'directories-pro'),
                    'author_only' => true,
                );
        }
    }
    
    public function wpNotificationSupports(Entity\Model\Bundle $bundle)
    {
        return !empty($bundle->info['claiming_enable']);
    }
    
    public function wpNotificationSubject(Entity\Model\Bundle $bundle)
    {
        switch ($this->_name) {
            case 'claiming_pending':
                return __('A new claim has been submitted', 'directories-pro');
            case 'claiming_approved':
                return __('Your claim has been approved', 'directories-pro');
            case 'claiming_rejected':
                return __('Your claim was rejected', 'directories-pro');
        }
    }
    
    public function wpNotificationBody(Entity\Model\Bundle $bundle)
    {
        switch ($this->_name) {
            case 'claiming_pending':
                $body = array(
                    __('Dear Administrator,', 'directories-pro'),
                    sprintf(
                        __('A new claim has been submitted for the following %1$s.', 'directories-pro'),
                        strtolower($singular = $bundle->getLabel('singular')),
                        $singular
                    ),
                    '[post_title]' . PHP_EOL . '[permalink]',
                    __('You can approve or reject the claim from the following page.', 'directories-pro') . PHP_EOL . '[drts_child_entity_admin_url]',
                );
                break;
            case 'claiming_approved':
                $body = array(
                    sprintf(__('Dear %s,', 'directories-pro'), '[drts_child_entity field="post_author" format="%value%"]'),
                    sprintf(
                        __('Your claim submitted for the following %1$s has been approved.', 'directories-pro'),
                        strtolower($singular = $bundle->getLabel('singular')),
                        $singular
                    ),
                    '[post_title]' . PHP_EOL . '[permalink]',
                );
                break;
            case 'claiming_rejected':
                $body = array(
                    sprintf(__('Dear %s,', 'directories-pro'), '[drts_child_entity field="post_author" format="%value%"]'),
                    sprintf(
                        __('Your claim submitted for the following %1$s was rejected.', 'directories-pro'),
                        strtolower($singular = $bundle->getLabel('singular')),
                        $singular
                    ),
                    '[post_title]' . PHP_EOL . '[permalink]',
                );
                break;
            default:
                return;
        }
        
        return implode(PHP_EOL . PHP_EOL, array_filter(array_map('trim', $body)));
    }
}