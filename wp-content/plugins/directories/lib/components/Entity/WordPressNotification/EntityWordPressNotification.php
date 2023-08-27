<?php
namespace SabaiApps\Directories\Component\Entity\WordPressNotification;

use SabaiApps\Directories\Component\WordPressContent\Notification\AbstractNotification;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class EntityWordPressNotification extends AbstractNotification
{
    protected function _wpNotificationInfo()
    {
        switch ($this->_name) {
            case 'pending':
                return array(
                    'label' => __('Pending review', 'directories'),
                    'author_only' => false,
                    'name' => 'pending',
                );
        }
    }
    
    public function wpNotificationSupports(Bundle $bundle)
    {
        return empty($bundle->info['is_taxonomy']) && !empty($bundle->info['public']);
    }
    
    public function wpNotificationSubject(Bundle $bundle)
    {
        switch ($this->_name) {
            case 'pending':
                return sprintf(
                    __('A new %1$s has been submitted', 'directories'),
                    strtolower($singular = $bundle->getLabel('singular')),
                    $singular
                );
        }
    }
    
    public function wpNotificationBody(Bundle $bundle)
    {
        switch ($this->_name) {
            case 'pending':
                $body = array(
                    __('Dear Administrator,', 'directories'),
                    sprintf(
                        __('A new %1$s has been submitted.', 'directories'),
                        strtolower($singular = $bundle->getLabel('singular')),
                        $singular
                    ),
                    '[post_title]' . PHP_EOL . '[permalink]',
                    __('You can view the item from the following page.', 'directories') . PHP_EOL . '[drts_entity_admin_url]',
                );
                break;
            default:
                return;
        }
        
        return implode(PHP_EOL . PHP_EOL, array_filter(array_map('trim', $body)));
    }
}