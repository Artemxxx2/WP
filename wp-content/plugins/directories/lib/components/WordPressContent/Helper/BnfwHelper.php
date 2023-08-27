<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;

class BnfwHelper
{
    public function help(Application $application)
    {
        add_filter('bnfw_notification_name', [$application, 'WordPressContent_Bnfw_notificationName'], 10, 2);
        add_filter('bnfw_shortcodes', [$application, 'WordPressContent_Bnfw_shortcodes'], 10, 4);

        // Send to parent post author
        if ($application->getPlatform()->isAdmin()) {
            add_action('bnfw_after_disable_current_user', [$application, 'WordPressContent_Bnfw_afterDisableCurrentUserAction']);
            add_filter('bnfw_notification_setting', [$application, 'WordPressContent_Bnfw_notificationSettingFilter']);
            add_action('bnfw_notification_table_column', [$application, 'WordPressContent_Bnfw_notificationTableColumnAction'], 10, 2);
            add_action('bnfw_after_enqueue_scripts', [$application, 'WordPressContent_Bnfw_afterEnqueueScriptsAction']);
        }
        add_filter('bnfw_notification_setting_fields', [$application, 'WordPressContent_Bnfw_notificationSettingFieldsFilter']);
        add_filter('bnfw_to_emails', [$application, 'WordPressContent_Bnfw_toEmailsFilter'], 10, 3);
        add_filter('bnfw_trigger_insert_post', [$application, 'WordPressContent_Bnfw_triggerInsertPostFilter'], 10, 3);
    }

    public function triggerInsertPostFilter(Application $application, $bool, $postId, $update = null)
    {
        if (!$bool
            && empty($update)
            && ($post_type = get_post_type($postId))
            && $application->getComponent('WordPressContent')->hasPostType($post_type)
            && !wp_is_post_revision($postId) // Do not invoke on revisions
        ) {
            $bool = true;
        }
        return $bool;
    }

    public function afterDisableCurrentUserAction(Application $application, $setting)
    {
        printf(
            '<tr valign="top" id="parent-post-author">
				<th>
					&nbsp;
					<div class="bnfw-help-tip"><p>%s</p></div>
				</th>

				<td>
					<label>
						<input type="checkbox" id="only-parent-post-author" name="only-parent-post-author" value="true"%s>
						%s
					</label>
				</td>
			</tr>',
            $application->H(__('E.g. If you want a new notification to go to the author of the parent post, tick this box.', 'directories')),
            isset($setting['only-parent-post-author']) && $setting['only-parent-post-author'] === 'true' ? ' checked="checked"' : '',
            $application->H(__('Send this notification to the Author of parent post', 'directories'))
        );
    }

    public function notificationSettingFilter(Application $application, $setting)
    {
        $setting['only-parent-post-author'] = isset($_POST['only-parent-post-author']) ? sanitize_text_field($_POST['only-parent-post-author']) : 'false';
        return $setting;
    }

    public function notificationSettingFieldsFilter(Application $application, $default)
    {
        $default['only-parent-post-author'] = 'false';
        return $default;
    }

    public function toEmailsFilter(Application $application, $toEmails, $setting, $id)
    {
        if ('true' === $setting['only-parent-post-author']) {
            if (bnfw_is_comment_notification($setting['notification'])) {
                $comment = get_comment($id);
                $post_id = $comment->comment_post_ID;
            } else {
                $post_id = $id;
            }

            if (($parent_post_id = get_post_field('post_parent', $post_id))
                && ($parent_post_author = get_post_field('post_author', $parent_post_id))
                && ('true' !== $setting['disable-current-user'] || $parent_post_author != $application->getUser()->id)
                && ($author = get_user_by('id', $parent_post_author))
                && !in_array($author->user_email, $toEmails)
            ) {
                $toEmails[] = $author->user_email;
            }
        }
        return $toEmails;
    }

    public function notificationTableColumnAction(Application $application, $column, $postId)
    {
        if ($column === 'users') {
            $setting = \BNFW::factory()->notifier->read_settings($postId);
            if ('true' === $setting['only-parent-post-author']) {
                echo $application->H(__(', Parent Post Author', 'directories'));
            }
        }
    }

    public function notificationName(Application $application, $name, $slug)
    {
        if (strpos($slug, 'drts-') === 0
            && ($parts = explode('-', $slug))
            && isset($parts[2])
            && $application->getComponent('WordPressContent')->hasPostType($parts[2])
            && ($post_type = get_post_type_object($parts[2]))
            && ($notification = $application->WordPressContent_Notifications_impl($parts[1], true))
        ) {
            $name = $post_type->labels->singular_name . ' ' . $notification->wpNotificationInfo('label');
        }
        return $name;
    }

    public function shortcodes(Application $application, $message, $notification, $postId, $engine)
    {
        if (strpos($notification, 'drts-') === 0
            || (!empty($postId)
                && ($post_type = get_post_type($postId))
                && $application->getComponent('WordPressContent')->hasPostType($post_type)
            )
        ) {
            $message = $application->WordPressContent_Notifications_shortcode($message, $postId, $engine);
        }
        return $message;
    }

    public function afterNotificationOptions(Application $application, $postType, $label, $setting)
    {
        if ($application->getComponent('WordPressContent')->hasPostType($postType)
            && ($bundle = $application->Entity_Bundle($postType))
            && ($options = $application->WordPressContent_Notifications_options($bundle))
        ) {
            foreach (array_keys($options) as $k) {
                echo '<option value="' . $k . '" ' . selected($k, $setting['notification']) . '>'
                    . $application->H("'" . $label . "' " . $options[$k])
                    . '</option>';
            }
        }
    }

    public function afterEnqueueScriptsAction(Application $application)
    {
        $child_bundles = [];
        foreach ($application->Entity_Bundles() as $bundle) {
            if (!empty($bundle->info['parent'])) {
                $child_bundles[] = $bundle->name;
            }
        }
        if (empty($child_bundles)) return;

        $application->getPlatform()
            ->addJsFile('wordpress-admin-bnfw.min.js', 'drts-wordpress-admin-bnfw', null, 'directories')
            ->addJsInline(
                'drts-wordpress-admin-bnfw',
                sprintf(
                    'jQuery(document).ready(function($) {
    DRTS_WordPress_bnfwAdmin(%s);
});',
                    $application->JsonEncode($child_bundles)
                )
            );

        wp_dequeue_script('bnfw');
        wp_enqueue_script('bnfw', plugins_url('bnfw/assets/js/bnfw.js', dirname( __FILE__ ) ), ['select2', 'drts-wordpress-admin-bnfw'], '0.1', true);
    }
}