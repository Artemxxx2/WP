<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class NotificationsHelper
{
    private $_impls = [], $_adminRoles;
    
    /**
     * Returns all available notifications
     * @param Application $application
     */
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$notifications = $application->getPlatform()->getCache('wp_notifications'))
        ) {
            $notifications = [];
            foreach ($application->InstalledComponentsByInterface('WordPressContent\INotifications') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->wpGetNotificationNames() as $notification_name) {
                    if (!$notification = $application->getComponent($component_name)->wpGetNotification($notification_name)) {
                        continue;
                    }
                    $notifications[$notification_name] = $component_name;
                }
            }
            $application->getPlatform()->setCache($notifications, 'wp_notifications');
        }

        return $notifications;
    }

    /**
     * Gets an implementation of SabaiApps\Directories\Component\WordPressContent\Notification\INotification interface for a given notification type
     * @param Application $application
     * @param string $notification
     */
    public function impl(Application $application, $notification, $returnFalse = false, $useCache = true)
    {
        if (!isset($this->_impls[$notification])) {
            $notifications = $application->WordPressContent_Notifications($useCache);
            // Valid notification type?
            if (!isset($notifications[$notification])
                || (!$application->isComponentLoaded($notifications[$notification]))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid notification type: %s', $notification));
            }
            $this->_impls[$notification] = $application->getComponent($notifications[$notification])->wpGetNotification($notification);
        }

        return $this->_impls[$notification];
    }
    
    public function create(Application $application, $name)
    {
        if (!class_exists('\BNFW', false)
            || (!$notification = $this->impl($application, $name, true, false))
        ) return;
        
        foreach ($application->Entity_Bundles() as $bundle) {
            if (!empty($bundle->info['is_taxonomy'])
                || !$notification->wpNotificationSupports($bundle)
            ) continue;

            if ($notification->wpNotificationInfo('name')) {
                $name = $notification->wpNotificationInfo('name');
            } else {
                // Prepend "drts-" to the notification name if not one of the default notification types in BNFW
                $name = 'drts-' . $name;
            }
            $notification_name = $name . '-' . $bundle->name;
            $query = new \WP_Query(array(
                'post_type' => \BNFW_Notification::POST_TYPE,
                'meta_key' => \BNFW_Notification::META_KEY_PREFIX . 'notification',
                'meta_value' => $notification_name,
            ));
            if ($query->have_posts()) return;

            $post_id = wp_insert_post(array(
                'post_title' => get_post_type_object($bundle->name)->labels->singular_name . ' - ' . $notification->wpNotificationInfo('label'),
                'post_status' => 'publish',
                'post_type' => \BNFW_Notification::POST_TYPE,
            ), true);
            if (is_wp_error($post_id)) {
                $application->logError($post_id->get_error_message());
                return;
            }
        
            $meta = array(
                'notification' => $notification_name,
                'subject' => $notification->wpNotificationSubject($bundle),
                'message' => $notification->wpNotificationBody($bundle),
                'email-formatting' => get_option('bnfw_email_format', 'html'),
                'only-post-author' => ($author_only = (bool)$notification->wpNotificationInfo('author_only')) ? 'true' : 'false',
                'only-parent-post-author' => !empty($bundle->info['parent']) && ($parent_author_only = (bool)$notification->wpNotificationInfo('parent_author_only')) ? 'true' : 'false',
                'users' => $author_only || !empty($parent_author_only) ? [] : $this->_getAdminRoles($application),
                'disabled' => 'false',
            );
            foreach ($meta as $meta_key => $meta_value) {
                update_post_meta($post_id, \BNFW_Notification::META_KEY_PREFIX . $meta_key, $meta_value);
            }
        }
    }
    
    protected function _getAdminRoles(Application $application)
    {
        if (!isset($this->_adminRoles)) {
            $this->_adminRoles = [];
            foreach (array_keys($application->getPlatform()->getAdministratorRoles()) as $role_name) {
                $this->_adminRoles[] = 'role-' . $role_name;
            }
        }
        return $this->_adminRoles;
    }
    
    public function send(Application $application, $name, Entity\Type\IEntity $entity, Entity\Type\IEntity $parent = null)
    {
        if (!class_exists('\BNFW', false)
            || !$this->impl($application, $name, true)
        ) return;
        
        $notification_name = 'drts-' . $name . '-' . (isset($parent) ? $parent->getBundleName() : $entity->getBundleName());
        $bnfw = \BNFW::factory();
        foreach ($bnfw->notifier->get_notifications($notification_name) as $notification) {
            $bnfw->engine->send_notification($bnfw->notifier->read_settings($notification->ID), $entity->getId());
        }
    }
    
    public function shortcode(Application $application, $message, $postId, $engine)
    {
        $shortcodes = [];
        if ($application->isComponentLoaded('Dashboard')) {
            $shortcodes['drts_dashboard_url'] = 'drts_dashboard_url';
        }
        if (!empty($postId)) {
            if (!isset($this->_entity)
                || $this->_entity->getId() != $postId
            ) {
                $this->_entity = $application->Entity_Entity('post', $postId);
            }
            if ($this->_entity) {
                if ($application->Entity_BundleTypeInfo($this->_entity->getBundleType(), 'parent')) {
                    if (!$parent_entity = $application->Entity_ParentEntity($this->_entity)) return;

                    $this->_child_entity = $this->_entity;
                    $this->_entity = $parent_entity;
                    $shortcodes['drts_child_entity'] = 'drts_child_entity';
                    $shortcodes['drts_child_entity_admin_url'] = 'drts_child_entity_admin_url';
                }
                $message = $engine->post_shortcodes($message, $this->_entity->getId());
                if ($author_id = $this->_entity->getAuthorId()) {
                    $message = $engine->user_shortcodes($message, $author_id);
                }
                $shortcodes['drts_entity'] = 'drts_entity';
                $shortcodes['drts_entity_admin_url'] = 'drts_entity_admin_url';
            }
        }
        
        // Allow filtering message
        $message = $application->Filter('wordpress_notification_message', $message, array($this->_entity)); 
        
        // Add, process, and remove shortcodes
        $shortcodes = $application->Filter('wordpress_notification_shortcodes', $shortcodes, array($this->_entity)); 
        foreach (array_keys($shortcodes) as $shortcode_name) {
            add_shortcode($shortcode_name, is_string($shortcodes[$shortcode_name])
                ? array($application, 'WordPressContent_Notifications_doShortcode')
                : $shortcodes[$shortcode_name]);
        }
        $message = do_shortcode($message);
        foreach ($shortcodes as $shortcode) {
            remove_shortcode($shortcode);
        }
        
        return $message;
    }
    
    public function doShortcode(Application $application, $atts, $content, $tag)
    {
        switch ($tag) {
            case 'drts_entity':
            case 'drts_child_entity':
                $atts = shortcode_atts(array(
                    'field' => null,
                    'format' => PHP_EOL . '-- %label% --' . PHP_EOL . '%value%',
                    'separator' => null,
                    'key' => null
                ), $atts);
                $entity = $tag === 'drts_child_entity' ? $this->_child_entity : $this->_entity;
                // Render a specific field?
                if (!empty($atts['field'])) {
                    if ((!$field = $application->Entity_Field($entity, $atts['field']))
                        || (!$field_type = $application->Field_Type($field->getFieldType(), true))
                        || !$field_type instanceof Field\Type\IHumanReadable
                    ) return '';
                    
                    return $this->_formatEntityField($entity, $field, $field_type, $atts['format'], $atts['separator'], $atts['key']);
                }
                // Render all fields
                $ret = [];
                foreach ($application->Entity_Field($entity) as $field) {
                    if (($field_type = $application->Field_Type($field->getFieldType(), true))
                        && $field_type instanceof Field\Type\IHumanReadable
                        && ('' !== $formatted = $this->_formatEntityField($entity, $field, $field_type, $atts['format'], $atts['separator']))
                    ) {
                        $ret[] = $formatted;
                    }
                }
                return implode(PHP_EOL, $ret);
            case 'drts_entity_admin_url':
                return admin_url('edit.php?post_type=' . $this->_entity->getBundleName());
            case 'drts_child_entity_admin_url':
                return admin_url('edit.php?post_type=' . $this->_child_entity->getBundleName());
            case 'drts_dashboard_url':
                if (!$application->isComponentLoaded('Dashboard')) return;
                $atts = shortcode_atts(array('post_status' => null), $atts);
                $params = isset($atts['post_status']) ? array('status' => $atts['post_status']) : [];
                return $application->MainUrl('/' . $application->getComponent('Dashboard')->getSlug('dashboard'), $params);
        }
    }
    
    protected function _formatEntityField(Entity\Type\IEntity $entity, Entity\Model\Field $field, Field\Type\IHumanReadable $fieldType, $format, $separator, $key = null)
    {
        switch ($format) {
            case '%label%':
                return $field->getFieldLabel();
            case '%value%':
                return $fieldType->fieldHumanReadableText($field, $entity, $separator, $key);
            default:
                $value = $fieldType->fieldHumanReadableText($field, $entity, $separator, $key);
                return $value === '' ? '' : str_replace(
                    array('%label%', '%value%'),
                    array($field->getFieldLabel(), $value),
                    $format
                );
        }
    }
    
    public function options(Application $application, Entity\Model\Bundle $bundle)
    {
        $options = [];
        foreach (array_keys($this->help($application)) as $notification_name) {
            if ((!$notification = $this->impl($application, $notification_name, true))
                || !$notification->wpNotificationSupports($bundle)
                || $notification->wpNotificationInfo('name')
            ) continue;
            
            $options['drts-' . $notification_name . '-' . $bundle->name] = $notification->wpNotificationInfo('label');
        }
        return $options;
    }
}