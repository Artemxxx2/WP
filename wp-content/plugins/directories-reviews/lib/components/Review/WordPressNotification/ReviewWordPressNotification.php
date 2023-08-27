<?php
namespace SabaiApps\Directories\Component\Review\WordPressNotification;

use SabaiApps\Directories\Component\WordPressContent\Notification\AbstractNotification;
use SabaiApps\Directories\Component\Entity;

class ReviewWordPressNotification extends AbstractNotification
{
    protected function _wpNotificationInfo()
    {
        switch ($this->_name) {
            case 'review_published':
                return array(
                    'label' => __('Published', 'directories-reviews'),
                    'author_only' => false,
                    'parent_author_only' => true,
                    'name' => 'new',
                );
        }
    }
    
    public function wpNotificationSupports(Entity\Model\Bundle $bundle)
    {
        return $bundle->type === 'review_review';
    }
    
    public function wpNotificationSubject(Entity\Model\Bundle $bundle)
    {
        switch ($this->_name) {
            case 'review_published':
                return __('A new review has been submitted', 'directories-reviews');
        }
    }
    
    public function wpNotificationBody(Entity\Model\Bundle $bundle)
    {
        switch ($this->_name) {
            case 'review_published':
                if ($parent_bundle = $this->_application->Entity_Bundle($bundle->info['parent'])) {
                    $body = [
                        sprintf(__('Dear %s,', 'directories-reviews'), '[drts_entity field="post_author" format="%value%"]'),
                        sprintf(
                            __('A new review has been submitted for your %1$s.', 'directories-reviews'),
                            strtolower($singular = $parent_bundle->getLabel('singular')),
                            $singular
                        ),
                        '[post_title]' . PHP_EOL . '[permalink]',
                    ];
                }
                break;
            default:
                return;
        }
        
        return implode(PHP_EOL . PHP_EOL, array_filter(array_map('trim', $body)));
    }
}