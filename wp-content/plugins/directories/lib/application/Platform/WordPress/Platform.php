<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\MainRoutingController;
use SabaiApps\Directories\AdminRoutingController;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Platform\AbstractPlatform;
use SabaiApps\Directories\Platform\Installer;
use SabaiApps\Framework\User\AbstractIdentity;
use SabaiApps\Framework\User\RegisteredIdentity;
use SabaiApps\Framework\User\User;

class Platform extends AbstractPlatform
{
    const VERSION = '1.3.108';
    private $_mainContent, $_singlePageId, $_singlePageContent, $_singlePageContentFiltered, $_singleForcePage, $_singleActionForceSingle,
        $_isSingleEntityPage = false,
        $_jqueryUiCoreLoaded, $_jqueryUiCssLoaded,
        $_userToBeDeleted, $_moLoaded, $_i18n, $_flash = [], $_bsHandle, $_flushRewriteRules, $_pluginsUrl,
        $_cssRelSize;
    private static $_instance;

    protected function __construct()
    {
        parent::__construct('WordPress');
        if (!defined('DRTS_WORDPRESS_SESSION_TRANSIENT')) {
            define('DRTS_WORDPRESS_SESSION_TRANSIENT', true);
        }
        if (DRTS_WORDPRESS_SESSION_TRANSIENT && !defined('DRTS_WORDPRESS_SESSION_TRANSIENT_LIFETIME')) {
            define('DRTS_WORDPRESS_SESSION_TRANSIENT_LIFETIME', 10800);
        }
        if (!defined('DRTS_WORDPRESS_ADMIN_CAPABILITY')) {
            define('DRTS_WORDPRESS_ADMIN_CAPABILITY', 'delete_users');
        }
        if (!defined('DRTS_WORDPRESS_WP_ACTION_PRIORITY')) {
            define('DRTS_WORDPRESS_WP_ACTION_PRIORITY', 11);
        }
        if (defined('WPML_PLUGIN_BASENAME')) {
            $this->_i18n = 'wpml';
        } elseif (function_exists('PLL')) { // don't use constants since they may be defined during deactivation causing fatal error
            $this->_i18n = 'polylang';
        }
    }

    /**
     * @return Platform
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) self::$_instance = new self();

        return self::$_instance;
    }

    public function getI18n()
    {
        return $this->_i18n;
    }

    public function hasBootstrapCss()
    {
        return !$this->isAdmin() && $this->_getBootstrapHandle();
    }

    protected function _getBootstrapHandle()
    {
        if (!isset($this->_bsHandle)) {
            $this->_bsHandle = apply_filters('drts_bootstrap_handle', false);
        }
        return $this->_bsHandle;
    }

    public function getCssRelSize()
    {
        if (!isset($this->_cssRelSize)) {
            $this->_cssRelSize = apply_filters('drts_css_rel_size', defined('DRTS_CSS_REL_SIZE') ? DRTS_CSS_REL_SIZE : parent::getCssRelSize());
        }

        return $this->_cssRelSize;
    }

    public function getPageParam()
    {
        return defined('DRTS_WORDPRESS_PAGE_PARAM') ? DRTS_WORDPRESS_PAGE_PARAM : '_page';
    }

    public function getUserIdentityFetcher()
    {
        return UserIdentityFetcher::getInstance();
    }

    public function getCurrentUser()
    {
        $wp_user = wp_get_current_user();
        if ($wp_user->ID == 0) return false;

        $identity = $this->_getIdentity($wp_user);

        return new User($identity);
    }

    public function isAdministrator($userId = null)
    {
        if (!isset($userId)) $userId = get_current_user_id();

        return is_super_admin($userId)
            || user_can($userId, DRTS_WORDPRESS_ADMIN_CAPABILITY)
            || user_can($userId, 'manage_directories');
    }

    public function getAdministratorRoles()
    {
        $wp_roles = wp_roles();
        $ret = [];
        foreach($wp_roles->role_objects as $role_name => $role) {
            if (!$role->has_cap(DRTS_WORDPRESS_ADMIN_CAPABILITY)
                && !$role->has_cap('manage_directories')
            ) continue;

            $ret[$role_name] = $wp_roles->roles[$role_name]['name'];
        }

        // Remove bbPress roles which are not real WP user roles
        return $this->_removeBbpRoles($ret);;
    }

    public function getUserRoles()
    {
        $wp_roles = wp_roles();
        $ret = [];
        foreach(array_keys($wp_roles->roles) as $role_name) {
            $ret[$role_name] = $wp_roles->roles[$role_name]['name'];
        }

        // Remove bbPress roles which are not real WP user roles
        return $this->_removeBbpRoles($ret);
    }

    protected function _removeBbpRoles(array $roles)
    {
        foreach (array_keys($roles) as $role) {
            if (strpos($role,'bbp_') === 0) {
                unset($roles[$role]);
            }
        }
        return $roles;
    }

    public function getUsersByRole($roleName)
    {
        $ret = [];
        foreach (get_users(array('role' => $roleName)) as $user) {
            if (!isset($ret[$user->ID])) {
                $ret[$user->ID] = $this->_getIdentity($user);
            }
        }

        return $ret;
    }


    public function getPermissions($userId)
    {
        $perms = [];
        if ($data = get_userdata($userId)) {
            $prefix_len = strlen('drts_');
            foreach (array_keys($data->allcaps) as $cap) {
                if (strpos($cap, 'drts_') === 0) {
                    $perms[] = substr($cap, $prefix_len);
                }
            }
        }
        return $perms;
    }

    public function hasPermission($userId, $permission)
    {
        return user_can($userId, 'drts_' . $permission);
    }

    public function guestHasPermission($permission)
    {
        return ($guest_perms = $this->getOption('guest_permissions')) ? !empty($guest_perms['drts_' . $permission]) : false;
    }

    public function getLogDir()
    {
        // Return empty value to use logs directory of System component
    }

    public function getTmpDir()
    {
        // Return empty value to use tmp directory of System component
    }

    public function getVarDir()
    {
        $ret = wp_upload_dir()['basedir'] . '/drts';
        if (is_multisite() && $GLOBALS['blog_id'] != 1) {
            $ret .= '/sites/' . $GLOBALS['blog_id'];
            if (!is_dir($ret)) {
                if (!@mkdir($ret, 0755, true)) {
                    // $this->logError('Failed creating directory ' . $ret);
                }
            }
        }
        return $ret;
    }

    public function getVarDirUrl()
    {
        $ret = wp_upload_dir()['baseurl'] . '/drts';
        if (is_multisite() && $GLOBALS['blog_id'] != 1) {
            $ret .= '/sites/' . $GLOBALS['blog_id'];
        }
        return $ret;
    }

    public function getSitePath()
    {
        return rtrim(ABSPATH, '/');
    }

    public function getSiteName()
    {
        return get_option('blogname');
    }

    public function getSiteVersion()
    {
        return get_bloginfo('version');
    }

    public function getSiteEmail()
    {
        return get_option('admin_email');
    }

    public function getSiteUrl()
    {
        return home_url();
    }

    public function getSiteAdminUrl()
    {
        return rtrim(admin_url(), '/');
    }

    public function getPackagePath()
    {
        return Loader::pluginsDir();
    }

    public function getPackages()
    {
        $plugins = $this->getSabaiPlugins(true);
        return array_keys($plugins);
    }

    public function getPackageVersion($package)
    {
        return $this->getPluginData($package, 'Version', '0.0.0');
    }

    public function getAssetsUrl($package = null, $vendor = false)
    {
        if (!isset($this->_pluginsUrl)) $this->_pluginsUrl = plugins_url();
        $url = $this->_pluginsUrl . '/' . (isset($package) ? $package : Loader::plugin()) . '/assets';
        if ($vendor) $url .= '/vendor';
        return $url;
    }

    public function getAssetsDir($package = null, $vendor = false)
    {
        $dir = $this->getPackagePath() . '/' . (isset($package) ? $package : Loader::plugin()) . '/assets';
        if ($vendor) $dir .= '/vendor';
        return $dir;
    }

    public function getLoginUrl($redirect = '')
    {
        return wp_login_url($redirect);
    }

    public function getLogoutUrl()
    {
        return wp_logout_url();
    }

    public function getRegisterUrl($redirect = '')
    {
        $url = rtrim(wp_registration_url(), '&');
        if ($redirect !== '') {
            $url .= strpos($url, '?') ? '&' : '?';
            $url .= esc_url_raw($redirect);
        }
        return $url;
    }

    public function isLoginFormRequired()
    {
        return false;
    }

    public function isRegisterFormRequired()
    {
        return false;
    }

    public function isUserRegisterable()
    {
        return get_option('users_can_register');
    }

    public function registerUser($username, $email, $password, array $values)
    {
        $email = apply_filters('user_registration_email', $email);
        // Let other plugins add errors
        $errors = apply_filters('registration_errors', new \WP_Error(), sanitize_user($username), $email);
        if ($errors->has_errors()) {
            throw new Exception\RuntimeException($errors->get_error_message());
        }

        // Create user
        $data = [
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
        ];
        $user_id = wp_insert_user($data);
        if (is_wp_error($user_id)) {
            throw new Exception\RuntimeException($user_id->get_error_message());
        }

        // Set default_password_nag to false to disable password change notice
        update_user_option($user_id, 'default_password_nag', false, true);

        if (class_exists('\BNFW', false)) {
            $bnfw = \BNFW::factory();
            $notify_admin = $bnfw->notifier->notification_exists('admin-user', true);
            $notify_user = $bnfw->notifier->notification_exists('new-user', false);
            if ($notify_admin) {
                $type = $notify_user ? 'both' : 'admin';
            } else {
                $type = $notify_user ? 'user' : null;
            }
            if ($type) {
                wp_new_user_notification($user_id, null, $type);
            }
        }

        return $user_id;
    }

    public function loginUser($username, $password, $remember, array $values)
    {
        $user = wp_signon(array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => !empty($remember)
        ));
        if (is_wp_error($user)) {
            throw new Exception\RuntimeException($user->get_error_message());
        }
        return $user->ID;
    }

    public function logoutUser()
    {
        wp_logout();
        return $this;
    }

    public function getResetPasswordKey(AbstractIdentity $identity)
    {
        if (!$user = get_user_by('ID', $identity->id)) {
            throw new Exception\RuntimeException('Invalid user');
        }
        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            throw new Exception\RuntimeException($key->get_error_message());
        }
        return $key;
    }

    public function checkResetPasswordKey($key, AbstractIdentity $identity)
    {
        $result = check_password_reset_key($key, $identity->username);
        if (is_wp_error($result)) {
            throw new Exception\RuntimeException($result->get_error_message());
        }
        return true;
    }

    public function resetPassword($password, $key, AbstractIdentity $identity)
    {
        if (!$user = get_user_by('ID', $identity->id)) {
            throw new Exception\RuntimeException('Invalid user');
        }

        reset_password($user, $password);
        wp_password_change_notification($user);
    }

    public function isCurrentPassword($password, AbstractIdentity $identity)
    {
        if (!$user = get_user_by('ID', $identity->id)) {
            throw new Exception\RuntimeException('Invalid user');
        }
        return wp_check_password($password, $user->user_pass, $user->ID);
    }

    public function changePassword($password, AbstractIdentity $identity)
    {
        $result = wp_update_user([
            'ID' => $identity->id,
            'user_pass' => $password,
        ]);

        if (is_wp_error($result)) {
            throw new Exception\RuntimeException($result->get_error_message());
        }
    }

    public function deleteAccount(AbstractIdentity $identity)
    {
        if (!function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        return wp_delete_user($identity->id);
    }

    public function getPrivacyPolicyLink()
    {
        return function_exists('get_the_privacy_policy_link') ? get_the_privacy_policy_link() : null;
    }

    public function setCurrentUser($userId)
    {
        if (!$user = get_user_by('id', $userId)) return false;

        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID);
        do_action('wp_login', $user->user_login, $user);
        return true;
    }

    public function getMainUrl($lang = null)
    {
        switch ($this->_i18n) {
            case 'wpml':
                $ret = apply_filters('wpml_home_url', get_option('home'));
                break;
            case 'polylang':
                // For some reason pll_home_url() returns URL with lang dir even when "The language is set from content" is selected in URL setting, so use home_url()
                $ret = home_url();
                if (PLL()->options['force_lang']) {
                    if (!isset($lang)) $lang = $this->getCurrentLanguage();
                    if ($lang !== $this->getDefaultLanguage()) {
                        $ret = rtrim($ret, '/') . '/' . $lang;
                    }
                }
                break;
            default:
                $ret = home_url();
        }
        if (($permalink_structure = get_option('permalink_structure'))
            && strpos($permalink_structure, 'index.php') !== false
            && strpos($ret, 'index.php') === false
        ) {
            if ($params_pos = strpos($ret, '?')) { // WPML may add params to home URL
                $ret = rtrim(substr($ret, 0, $params_pos), '/') . '/index.php' . substr($ret, $params_pos);
            } else {
                $ret = rtrim($ret, '/') . '/index.php';
            }
        }

        return $ret;
    }

    protected function _getDB()
    {
        return new DB($GLOBALS['wpdb']);
    }

    public function mail($to, $subject, $body, array $options = [])
    {
        $options += array(
            'from' => $this->getSiteName(),
            'from_email' => $this->getSiteEmail(),
            'attachments' => [],
            'headers' => [],
        );

        $options['headers'][] = sprintf('From: %s <%s>', $options['from'], $options['from_email']);

        // Attachments?
        if (!empty($options['attachments'])) {
            foreach (array_keys($options['attachments']) as $i) {
                // wp_mail() accepts file path only
                $options['attachments'][$i] = $options['attachments'][$i]['path'];
            }
        }

        if (!empty($options['is_html'])) {
            add_filter('wp_mail_content_type', array($this, 'onWpMailContentType'));
        }

        $result = wp_mail($to, $subject, $body, $options['headers'], $options['attachments']);

        if (!empty($options['is_html'])) {
            remove_filter('wp_mail_content_type', array($this, 'onWpMailContentType'));
        }

        return $result;
    }

    public function onWpMailContentType()
    {
        return 'text/html';
    }

    protected function _getGuestId()
    {
        return md5(Request::ip() . Request::userAgent());
    }

    public function addCss($css, $targetHandle = null)
    {
        wp_add_inline_style(isset($targetHandle) ? $targetHandle : 'drts', $css);
        if ($this->_trackedAssets) {
            foreach (array_keys($this->_trackedAssets) as $track_name) {
                $this->_trackedAssets[$track_name]->addCss($css, $targetHandle);
            }
        }
        return $this;
    }

    public function setSessionVar($name, $value)
    {
        $name = $GLOBALS['wpdb']->prefix . $name;
        if (DRTS_WORDPRESS_SESSION_TRANSIENT) {
            if (!$user_id = get_current_user_id()) {
                $user_id = $this->_getGuestId();
            }
            $this->setCache($value, 'session_' . $name . ':' . $user_id, DRTS_WORDPRESS_SESSION_TRANSIENT_LIFETIME);
        } else {
            $_SESSION['drts'][$name] = $value;
        }
        return $this;
    }

    public function getSessionVar($name)
    {
        $name = $GLOBALS['wpdb']->prefix . $name;
        if (DRTS_WORDPRESS_SESSION_TRANSIENT) {
            if (!$user_id = get_current_user_id()) {
                $user_id = $this->_getGuestId();
            }
            $ret = $this->getCache('session_' . $name . ':' . $user_id);
            return $ret === false ? null : $ret;
        }
        return isset($_SESSION['drts'][$name])
            ? $_SESSION['drts'][$name]
            : null;
    }

    public function deleteSessionVar($name)
    {
        $name = $GLOBALS['wpdb']->prefix . $name;
        if (DRTS_WORDPRESS_SESSION_TRANSIENT) {
            if (!$user_id = get_current_user_id()) {
                $user_id = $this->_getGuestId();
            }
            $this->deleteCache('session_' . $name . ':' . $user_id);
        } else {
            if (isset($_SESSION['drts'][$name])) {
                unset($_SESSION['drts'][$name]);
            }
        }

        return $this;
    }

    public function setEntityMeta($entityType, $entityId, $name, $value)
    {
        switch ($entityType) {
            case 'post':
                update_post_meta($entityId, 'drts_' . $name, $value);
                break;
            case 'term':
                update_term_meta($entityId, 'drts_' . $name, $value);
                break;
            case 'user':
                update_user_meta($entityId, $GLOBALS['wpdb']->prefix . 'drts_' . $name, $value);
                break;
            default:
        }
        return $this;
    }

    public function getEntityMeta($entityType, $entityId, $name)
    {
        switch ($entityType) {
            case 'post':
                return get_post_meta($entityId, 'drts_' . $name, true);
            case 'term':
                return get_term_meta($entityId, 'drts_' . $name, true);
            case 'user':
                return get_user_meta($entityId, $GLOBALS['wpdb']->prefix . 'drts_' . $name, true);
        }
    }

    public function hasEntityMeta($entityType, $entityId, $name)
    {
        return in_array($entityType, ['post', 'term', 'user']) ? metadata_exists($entityType, $entityId, $name) : false;
    }

    public function deleteEntityMeta($entityType, $entityId, $name)
    {
        switch ($entityType) {
            case 'post':
                delete_post_meta($entityId, 'drts_' . $name);
                break;
            case 'term':
                delete_term_meta($entityId, 'drts_' . $name);
                break;
            case 'user':
                delete_user_meta($entityId, $GLOBALS['wpdb']->prefix . 'drts_' . $name);
                break;
            default:
        }
        return $this;
    }

    public function getUsersByMeta($name, $value, $limit = 20, $offset = 0, $order = 'DESC', $numeric = true, $compare = null)
    {
        $meta_query = [
            'key' => $meta_key = $GLOBALS['wpdb']->prefix . 'drts_' . $name,
            'value' => $value,
        ];
        if ($numeric) {
            $meta_query['type'] = 'NUMERIC';
            if (isset($compare)) $meta_query['compare'] = $compare;
        }
        $query = new \WP_User_Query([
            'meta_query' => [$meta_query],
            'orderby' => $numeric ? 'meta_value_num' : 'meta_value',
            'order' => $order,
            'number' => $limit,
            'offset' => $offset,
        ]);
        $ret = [];
        if (!empty($query->results)) {
            foreach ($query->results as $user) {
                $ret[$user->ID] = $this->_getIdentity($user, [$name => $user->get($meta_key)]);
            }
        }
        return $ret;
    }

    protected function _getIdentity($wpUser, array $data = [])
    {
        return new RegisteredIdentity([
            'id' => $wpUser->ID,
            'username' => $wpUser->user_login,
            'name' => $wpUser->display_name,
            'email' => $wpUser->user_email,
            'url' => $wpUser->user_url,
            'created' => strtotime($wpUser->user_registered),
        ] + $data);
    }

    public function getCache($id, $group = 'settings')
    {
        return get_transient($this->_getCacheId($id, $group));
    }

    public function setCache($data, $id, $lifetime = null, $group = 'settings')
    {
        // Always set expiration to prevent this cache data from being autoloaded on every request by WP.
        // Lifetime can be set to 0 to never expire but the value will be autoloaded on every request.
        if (!isset($lifetime)) $lifetime = 604800;

        set_transient($this->_getCacheId($id, $group), $data, $lifetime);

        return $this;
    }

    public function deleteCache($id, $group = 'settings')
    {
        delete_transient($this->_getCacheId($id, $group));

        return $this;
    }

    protected function _getCacheId($id, $group)
    {
        return 'drts_' . (strlen($group) ? '_' . $group . '__' : '') . $id;
    }

    public function clearCache($group = null)
    {
        global $wpdb;
        $prefix = '';
        if (isset($group)
            && strlen($group)
        ) {
            $prefix .= '_' . $group . '__';
        }
        $wpdb->query('DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE \'_transient_drts_' . $prefix . '%\'');
        $wpdb->query('DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE \'_transient_timeout_drts_' . $prefix . '%\'');

        // Clear object cache
        if (function_exists('wp_cache_flush')) wp_cache_flush();

        return $this;
    }

    public function getLocale()
    {
        return get_locale();
    }

    public function isRtl()
    {
        return is_rtl();
    }

    public function htmlize($text, $inlineTagsOnly = false, $forCaching = false)
    {
        if (!strlen($text)) return '';

        if ($inlineTagsOnly) {
            $tags = [
                'a' => ['title' => true, 'href' => true, 'target' => true],
                'abbr' => ['title' => true],
                'acronym' => ['title' => true],
                'b' => [],
                'cite' => [],
                'code' => [],
                'del' => ['datetime' => true],
                'em' => [],
                'i' => [],
                'q' => ['cite' => true],
                's' => [],
                'strike' => [],
                'strong' => [],
                'small' => [],
                'br' => [],
            ];
            $text = wp_kses($text, $tags);
        } else {
            if ($inlineTagsOnly === false) {
                $text = wp_kses_post($text);
            }
        }
        $text = balanceTags($text, true);
        if (!isset($tags)) {
            if (!class_exists('\WP_Embed', false)) {
                include ABSPATH . WPINC . '/class-wp-embed.php';
            }
            if (isset($GLOBALS['wp_embed'])) {
                $text = $GLOBALS['wp_embed']->autoembed($text);
            }
            $text = make_clickable($text);
        } elseif (isset($tags['a'])) {
            $text = make_clickable($text);
        }
        $text = wptexturize($text);
        $text = convert_smilies($text);
        $text = convert_chars($text);
        if (!isset($tags)
            || (isset($tags['p']) && isset($tags['br']))
        ) {
            $text = wpautop($text);
            $text = shortcode_unautop($text);
        }
        // Process shortcodes if not caching
        if (!$forCaching) {
            $text = $this->doShortcode($text);
        }
        return $text;
    }

    public function doShortcode($text)
    {
        // Need to manually convert [embed] shortcode
        if (strpos($text, '[/embed]') !== false) {
            if (!class_exists('\WP_Embed', false)) {
                include ABSPATH . WPINC . '/class-wp-embed.php';
            }
            $text = $GLOBALS['wp_embed']->run_shortcode($text);
        }
        return do_shortcode($text);
    }

    public function getCookieDomain()
    {
        return COOKIE_DOMAIN;
    }

    public function getCookiePath()
    {
        return COOKIEPATH;
    }

    public function getCookieHash()
    {
        return COOKIEHASH;
    }

    public function setOption($name, $value, $autoload = true)
    {
        update_option('drts_' . strtolower($name), $value, $autoload);
        return $this;
    }

    public function getOption($name, $default = null)
    {
        return get_option('drts_' . strtolower($name), $default);
    }

    public function deleteOption($name)
    {
        delete_option('drts_' . strtolower($name));
        return $this;
    }

    public function clearOptions()
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->options. ' WHERE option_name LIKE %s', 'drts_%'));
        return $this;
    }

    public function getDateFormat()
    {
        return get_option('date_format');
    }

    public function getTimeFormat()
    {
        return get_option('time_format');
    }

    public function getDate($format, $timestamp)
    {
        return date_i18n($format, $timestamp + (get_option('gmt_offset') * 3600));
    }

    public function getStartOfWeek()
    {
        return ($ret = (int)get_option('start_of_week')) ? $ret : 7;
    }

    public function getTimeZone()
    {
        if (!$ret = get_option('timezone_string')) {
            if (!$gmt_offset = get_option('gmt_offset')) {
                $gmt_offset = 0;
            }
            $ret = timezone_name_from_abbr('', (int)$gmt_offset * 3600, 0);
        }
        return $ret ?: null;
    }

    public function getCustomAssetsDir($useCache = true)
    {
        if (!$useCache
            || false === ($ret = $this->getCache('wordpress_assets_dir'))
        ){
            $ret = [];
            foreach (array(TEMPLATEPATH  . '/drts', WP_CONTENT_DIR . '/drts/assets', STYLESHEETPATH . '/drts') as $dir) {
                if (is_dir($dir) && !in_array($dir, $ret)) {
                    $ret[] = $dir;
                }
            }
            $this->setCache($ret = apply_filters('drts_assets_dir', $ret), 'wordpress_assets_dir', 0);
        }
        return $ret;
    }

    public function getCustomAssetsDirUrl($index = null)
    {
        if (false === $ret = $this->getCache('wordpress_assets_dir_url')) {
            $ret = [];
            foreach ($this->getCustomAssetsDir() as $dir) {
                if ($dir === TEMPLATEPATH  . '/drts') {
                    $ret[] = get_template_directory_uri() . '/drts';
                } elseif ($dir === STYLESHEETPATH  . '/drts') {
                    $ret[] = get_stylesheet_directory_uri() . '/drts';
                } elseif ($dir === WP_CONTENT_DIR . '/drts/assets') {
                    $ret[] = WP_CONTENT_URL . '/drts/assets';
                }
            }
            $this->setCache($ret = apply_filters('drts_assets_dir_url', $ret), 'wordpress_assets_dir_url', 0);
        }
        return isset($index) ? $ret[$index] : $ret;
    }

    public function getUserProfileHtml($userId)
    {
        return nl2br(get_the_author_meta('description', $userId));
    }

    public function loadDefaultAssets($loadJs = true, $loadCss = true)
    {
        if ($loadJs
            && !$this->_defaultJsLoaded
        ) {
            $action = is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';
            add_action($action, array($this, 'onWpEnqueueScripts'), 1);
            add_action($action, array($this, 'onWpEnqueueScriptsLast'), 99999);
        }
        if ($loadCss
            && !$this->_defaultCssLoaded
        ) {
            $action = is_admin() ? 'admin_print_styles' : 'wp_print_styles';
            add_action($action, array($this, 'onWpPrintStyles'), 99999);
        }

        return parent::loadDefaultAssets($loadJs, $loadCss);
    }

    public function run()
    {
        if (!DRTS_WORDPRESS_SESSION_TRANSIENT) {
            Application::startSession(defined('DRTS_WORDPRESS_SESSION_PATH') ? DRTS_WORDPRESS_SESSION_PATH : null);
        }

        add_action('init', array($this, 'onInitAction'), 3); // earlier than most plugins
        add_action('admin_init', array($this, 'onAdminInitAction'));
        add_action('widgets_init', array($this, 'onWidgetsInitAction'));
        add_action('wp_login', array($this, 'onWpLoginAction'));
        add_action('wp_logout', array($this, 'onWpLogoutAction'));
        add_action('delete_user', array($this, 'onDeleteUserAction'));
        add_action('deleted_user', array($this, 'onDeletedUserAction'));

        if (is_admin()) {
            // Do not include WP admin header automatically if sabai admin page
            if (isset($_REQUEST['page']) && is_string($_REQUEST['page']) && 0 === strpos($_REQUEST['page'], 'drts')) {
                $_GET['noheader'] = 1;
            }

            add_action('admin_menu', array($this, 'onAdminMenuAction'));
            add_action('admin_notices', array($this, 'onAdminNoticesAction'));
            add_action('post_updated', array($this, 'onPostUpdatedAction'), 10, 3);
            add_action('activated_plugin', array($this, 'onActivatedPluginAction'));
            add_action('deactivated_plugin', array($this, 'onDeactivatedPluginAction'));
            add_action('upgrader_process_complete', array($this, 'onUpgraderProcessCompleteAction'), 10, 2);
            add_action('after_switch_theme', array($this, 'onAfterSwitchThemeAction'));
            add_filter('extra_plugin_headers', array($this, 'onExtraPluginHeadersFilter'));
            add_filter('network_admin_plugin_action_links', array($this, 'onNetworkAdminPluginActionLinks'), 10, 4);
            add_action('admin_head-widgets.php', array($this, 'onAdminHeadWidgetsPhpAction'));
            add_action('admin_print_styles', [$this, 'onAdminPrintStylesAction']);
        } else {
            add_filter('query_vars', array($this, 'onQueryVarsFilter'));

            // Add action method to run Sabai
            add_action('wp', array($this, 'onWpAction'), DRTS_WORDPRESS_WP_ACTION_PRIORITY);
        }
    }

    public function uninstall($removeData)
    {
        parent::uninstall($removeData);

        if (!$removeData) return;

        global $wpdb;
        // Make sure tables from other plugins are deleted
        $plugin_tables = [
            'payment_feature',
            'payment_featuregroup',
        ];
        foreach ($plugin_tables as $table) {
            $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'drts_' . $table . ';');
        }
    }

    public function onQueryVarsFilter($vars)
    {
        $vars[] = 'drts_route';
        $vars[] = 'drts_action';
        $vars[] = 'drts_pagename';
        $vars[] = 'drts_parent_pagename';
        $vars[] = 'drts_lang';
        $vars[] = 'drts_redirect';
        $vars[] = 'drts_is_user';
        return $vars;
    }

    public function getPageSlugs($lang = null)
    {
        if (!empty($lang)
            || ($lang !== false && ($lang = $this->getCurrentLanguage()))
        ) {
            if (false !== $slugs = $this->getOption('page_slugs_' . $lang, false)) {
                return $slugs;
            }
        }
        return $this->getOption('page_slugs', []);
    }

    public function setPageSlugs($slugs, $lang = null, $flush = true)
    {
        if (!empty($lang)
            || ($lang !== false && ($lang = $this->getCurrentLanguage()))
        ) {
            $this->setOption('page_slugs_' . $lang, $slugs);
            $this->deleteCache('wordpress_rewrite_rules_' . $lang);

            if ($lang !== $this->getDefaultLanguage()) return $this;
        }
        $this->setOption('page_slugs', $slugs);
        $this->flushRewriteRules($flush);

        return $this;
    }

    public function flushRewriteRules($flag = true)
    {
        $this->_flushRewriteRules = $flag;
    }

    protected function _flushRewriteRules()
    {
        foreach ($this->getAllRewriteRules(true) as $regex => $redirect) {
            add_rewrite_rule($regex, $redirect, 'top');
        }
        flush_rewrite_rules();
    }

    public function hasSlug($component, $slug, $lang = null)
    {
        // Check if a valid page is assigned
        if (($page_slugs = $this->getPageSlugs($lang))
            && isset($page_slugs[1][$component][$slug])
            && !empty($page_slugs[2][$page_slugs[1][$component][$slug]])
        ) {
            return $page_slugs[1][$component][$slug];
        }
        return false;
    }

    public function getSlug($component, $slug, $lang = null)
    {
        return ($_slug = $this->hasSlug($component, $slug, $lang)) ? $_slug : $slug;
    }

    public function getPermalinkConfig($lang = null)
    {
        $page_slugs = $this->getPageSlugs($lang);
        return empty($page_slugs[4]) ? [] : $page_slugs[4];
    }

    public function getTitle($component, $name, $lang = null)
    {
        if (($page_slugs = $this->getPageSlugs($lang))
            && ($slug = @$page_slugs[1][$component][$name])
            && ($page_id = @$page_slugs[2][$slug])
            && ($post = get_post($page_id))
        ) {
            return $post->post_title;
        }
    }

    private function _isSabaiPageId($id)
    {
        $page_slugs = $this->getPageSlugs();
        return !empty($page_slugs[2]) && ($slug = array_search($id, $page_slugs[2])) ? $slug : false;
    }

    protected function _isSabaiPage()
    {
        if (is_page()) {
            if (!Request::isAjax()
                && !(Request::isPostMethod() && empty($_POST['_drts_form_build_id']))
                && $this->_isPageUsingShortcode($GLOBALS['post'])
                && !get_query_var('drts_is_user') // make sure not on single user page
                && !get_query_var('drts_action') // make sure not on single post action page
            ) return false;

            if (!$pagename = $this->_isSabaiPageId($GLOBALS['post']->ID)) return false;

            if (!$route = get_query_var('drts_route')) return $pagename;

            $route_to_check = strtolower(urlencode($route)); // $route is not URL encoded
            if (strpos($route_to_check, $pagename) !== 0
                && strpos($route_to_check, '_drts') !== 0 // allow _drts/* route since it may have a page when permalink structure is plain
            ) return false;

            if ($action = get_query_var('drts_action')) $route .= '/' . $action;

            return $route;
        }

        if (is_single() || is_tax()) {
            if (!$route = get_query_var('drts_route')) {
                // Using Plain permalink type, so get route from current object

                $object = get_queried_object();
                if (is_single()) {
                    if (!$this->getApplication()->getComponent('WordPressContent')->hasPostType($object->post_type)) {
                        return false;  // Not our post type
                    }

                    $entity_type = 'post';
                } else {
                    if (!$this->getApplication()->getComponent('WordPressContent')->hasTaxonomy($object->taxonomy)) {
                        return false;  // Not our taxonomy
                    }

                    $entity_type = 'term';
                }

                if ((!$entity = $this->getApplication()->Entity_Entity($entity_type, get_queried_object_id()))
                    || (!$bundle = $this->getApplication()->Entity_Bundle($entity))
                    || (!$bundle_permalink_path = $bundle->getPath(true))
                ) return false;

                if (!empty($bundle->info['parent'])) { // child entity bundles do not have custom permalinks
                    if (!$parent = $this->getApplication()->Entity_ParentEntity($entity, false)) return false;

                    $path = str_replace(':slug', $parent->getSlug(), $bundle_permalink_path) . '/' . $entity->getId();
                } else {
                    if ($entity->isDraft()
                        || $entity->isPending()
                    ) {
                        // No slug if draft or pending
                        $path = $bundle_permalink_path . '/' . $entity->getId();
                    } else {
                        $path = $bundle_permalink_path . '/' . $entity->getSlug();
                    }
                }
                $route = trim($path, '/');

                if (is_tax()
                    && !get_query_var('drts_pagename')
                    && ($route_parts = explode('/', $route))
                ) {
                    // page name is required to correctly render taxonomy term page
                    set_query_var('drts_pagename', $route_parts[0]);
                }
            }

            if (is_tax()) {
                if (($current_lang = $this->getCurrentLanguage()) // multi-lingual enabled?
                    && ($requested_lang = get_query_var('drts_lang'))
                    &&  $requested_lang !== $current_lang // language switch requested
                ) {
                    // Need to manually redirect to the requested language page since WPML does not.
                    // Todo: Check what other plugins (Polylang, ect.) do.
                    $term = get_queried_object();
                    if ($this->getTranslatedId('term', $term->taxonomy, $term->term_id, $requested_lang) // has translation?
                        && ($term_url = get_term_link($term))
                    ) {
                        wp_redirect($term_url, 301);
                        exit;
                    }
                }
            }

            if ($action = get_query_var('drts_action')) $route .= '/' . $action;

            return $route;
        }

        // For /_drts* routes
        return ($route = get_query_var('drts_route')) && strpos($route, '_drts') === 0 ? $route : false;
    }

    protected function _loadMo()
    {
        if (!$this->_moLoaded) {
            foreach ($this->getSabaiPlugins() as $plugin_name => $plugin) {
                if ($plugin['mo']) {
                    load_plugin_textdomain($plugin_name, false, $plugin_name . '/languages/');
                }
            }
            $this->_moLoaded = true;
        }

        return $this;
    }

    public function onWpAction()
    {
        if (defined('DOING_AJAX')
            || is_feed()
            || post_password_required() // Password protected page
            || !empty($_GET['elementor-preview']) // Elementor editor page
            || !empty($_GET['uxb_iframe']) // UX Builder editor page
            || !apply_filters('drts_wordpress_pre_wp_action', true)
        ) return;

        $this->_mainContent = $this->_singlePageId = $this->_singlePageContent = null;
        $this->_singlePageContentFiltered = false;

        if (!$route = $this->_isSabaiPage()) {
            if (is_page()
                && $this->_isPageUsingShortcode($GLOBALS['post'])
            ) {
                set_query_var($this->getPageParam(), ''); // prevents wordpress from redirecting to paged path, e.g. */2, */3.
            }
            return;
        }

        if (get_query_var('drts_redirect')) {
            // Came by rewrite URL, redirect so that URL in browser changes
            wp_redirect(rtrim($this->getMainUrl(), '/') . '/' . trim($route, '/') . '/', 301);
            exit;
        }

        do_action('drts_wordpress_wp_action', $route);

        $run_main = true;
        if (!Request::isAjax()) {
            if (is_single() || is_tax()) {
                $run_main = false;
                $page = $this->_handleSingleEntityPage($route);
                $current_theme = strtolower(wp_get_theme(get_template())->get('Name'));
                if (is_single()) {
                    if (get_query_var('drts_action')) {
                        $this->_singleActionForceSingle = in_array($current_theme, ['the next mag']);
                        $this->_singleActionForceSingle = apply_filters('drts_wordpress_single_action_force_single', $this->_singleActionForceSingle);
                    } else {
                        $this->_singleForcePage = in_array($current_theme, ['Ave']) || strpos($current_theme, 'themify ') === 0;
                        $this->_singleForcePage = apply_filters('drts_wordpress_single_force_page', $this->_singleForcePage);
                    }
                } elseif (is_tax()) {
                    $GLOBALS['wp_query']->post_count = 1;
                    $GLOBALS['wp_query']->posts = [$page];
                    $GLOBALS['wp_query']->max_num_pages = 1;
                    $GLOBALS['wp_query']->rewind_posts();
                    $archive_force_singular = in_array($current_theme, ['x', 'listify', 'publisher', 'jupiterx', 'hello elementor', 'astra']);
                    if (apply_filters('drts_wordpress_archive_force_singular', $archive_force_singular)) {
                        $GLOBALS['wp_query']->is_singular = true;
                        add_filter('feed_links_show_comments_feed', function ($show) { if (is_archive()) { $show = false; } return $show; });
                    }
                    $archive_force_queried_object_id = in_array($current_theme, ['astra']);
                    if (apply_filters('drts_wordpress_archive_force_queried_object_id', $archive_force_queried_object_id)) {
                        $GLOBALS['wp_query']->queried_object_id = $page->ID;
                    }
                    $archive_force_is_page = in_array($current_theme, ['thegem', 'onfleek']);
                    if (apply_filters('drts_wordpress_archive_force_is_page', $archive_force_is_page)) {
                        $GLOBALS['wp_query']->is_page = true;
                    }
                    $archive_force_is_not_arvhive = in_array($current_theme, ['life']);
                    if (apply_filters('drts_wordpress_archive_force_is_not_archive', $archive_force_is_not_arvhive)) {
                        $GLOBALS['wp_query']->is_archive = false;
                    }
                    if ($current_theme === 'astra') {
                        add_filter('drts_wordpress_the_content_filter_once', '__return_false');
                    }

                    // For page title
                    $archive_force_title_in_loop = true;
                    if (apply_filters('drts_wordpress_archive_force_title_in_loop', $archive_force_title_in_loop)) {
                        add_filter('the_title', function ($title) {
                            return in_the_loop() ? single_term_title('', false) : $title;
                        }, PHP_INT_MAX - 2);
                    }
                    // The7 theme https://themeforest.net/item/the7-responsive-multipurpose-wordpress-theme/5556590
                    add_filter('presscore_page_title_strings', function ($titles) {
                        $titles['archives'] = single_term_title('', false);
                        return $titles;
                    }, 99999);
                    // Customizr theme
                    add_filter('czr_is_list_of_posts', '__return_false', 99999);

                    add_filter('get_the_archive_description', [$this, 'onGetTheArchiveDescFilter']);
                }

                add_filter('template_include', [$this, 'onTemplateIncludeFilter']);
            } elseif (get_query_var('drts_is_user')) {
                $run_main = false;
                $this->_handleSingleEntityPage($route);
            }
        }
        if ($run_main) {
            if (false === $this->_mainContent = $this->_runMain($route)) return;

            if (strpos($route, '/')) remove_all_filters('the_content'); // make sure parent page content is not processed
        }

        // Remove unwanted filters added by default
        $this->_addTheContentFilters(false);

        add_filter('the_content', [$this, 'onTheContentFilter'], 12);

        if (isset($this->_singlePageId)) {
            add_filter('body_class', function($classes) {
                $classes[] = 'page-template';
                if ($template_slug = get_page_template_slug($this->_singlePageId)) {
                    $template_parts = explode('/', $template_slug);
                    foreach ($template_parts as $part) {
                        $classes[] = 'page-template-' . sanitize_html_class(str_replace(['.', '/'], '-', basename($part, '.php')));
                    }
                    $classes[] = 'page-template-' . sanitize_html_class(str_replace('.', '-', $template_slug));
                }
                return $classes;
            });
        }
    }

    protected function _handleSingleEntityPage($route)
    {
        // Using custom page template to display single entity page?
        if ($page = $this->_hasSinglePage()) {
            // Has custom page template
            if (get_query_var('drts_action')) {
                // Do not use custom page template

                if (apply_filters('drts_wordpress_single_action_use_parent_page', true)) {
                    // Use page template of parent page. Below should not fail.
                    if (!$this->_useParentPage()) return;
                }

                if (false === $this->_mainContent = $this->_runMain($route)) return;
            } else {
                // Use custom page template
                $this->_singlePageId = $page->ID;
                $single_page_content = $this->_getSinglePageContent($page);
                if (strpos($single_page_content, '[drts-entity]') !== false) {
                    if (false === $this->_mainContent = $this->_runMain($route)) return;

                    $this->_singlePageContent = $single_page_content;
                } else {
                    if (strpos($page->post_content, '[drts-entity ') !== false) {
                        // No need to render main content here since it should be rendered through page builder

                        $this->_singlePageContent = $single_page_content;
                    } else {
                        if (false === $this->_mainContent = $this->_runMain($route)) return;
                    }
                }
            }
        } else {
            $page = true;
            if (is_tax()) {
                // Use page template of parent page. Below should not fail.
                if (!$page = $this->_useParentPage()) return;
            } elseif (is_single()) {
                if (get_query_var('drts_action')) {
                    if (apply_filters('drts_wordpress_single_action_use_parent_page', true)) {
                        // Use page template of parent page. Below should not fail.
                        if (!$page = $this->_useParentPage()) return;
                    }
                }
            }

            if (false === $this->_mainContent = $this->_runMain($route)) return;
        }

        $this->_isSingleEntityPage = true;
        return $page;
    }

    public function isSingleEntityPage()
    {
        return $this->_isSingleEntityPage;
    }

    public function getMainContent($unset = true)
    {
        $ret = $this->_mainContent;
        if ($unset) $this->_mainContent = null;
        return $ret;
    }

    protected function _isPageUsingShortcode($page)
    {
        return strpos($GLOBALS['post']->post_content, '[drts-') !== false
            || $this->_isPageBuiltWithPageBuilder($page->ID);
    }

    protected function _isPageBuiltWithPageBuilder($pageId)
    {
        if (class_exists('\Elementor\Plugin', false)
            && \Elementor\Plugin::$instance->documents->get($pageId)->is_built_with_elementor()
        ) {
            return 'elementor';
        } elseif (class_exists('\FLBuilder', false)
            && \FLBuilderModel::is_builder_enabled($pageId)
        ) {
            return 'beaver';
        }
        return false;
    }

    protected function _getSinglePageContent($page)
    {
        switch ($this->_isPageBuiltWithPageBuilder($page->ID)) {
            case 'elementor': // Elementor
                // Prevent the content from being filtered twice.
                $this->_singlePageContentFiltered = true;
                // Page content edited by Elementor is saved separately, so we need to fetch it manually.
                return \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($page->ID);
            case 'beaver': // Beaver Builder
                // Prevent the content from being filtered twice.
                $this->_singlePageContentFiltered = true;
                // Page content edited by BB is saved separately, so we need to fetch it manually.
                ob_start();
                \FLBuilder::render_content_by_id($page->ID);
                return ob_get_clean();
            default:
                return $page->post_content;
        }
    }

    protected function _hasSinglePage()
    {
        if ((!$page_name = get_query_var('drts_pagename'))
            || (!$page = get_page_by_path($page_name))
            || $page->post_status !== 'publish'
        ) return;

        return $page;
    }

    protected function _useParentPage()
    {
        if ((!$page_name = get_query_var('drts_parent_pagename'))
            || (!$page = get_page_by_path($page_name))
            || $page->post_status !== 'publish'
        ) return;

        $this->_singlePageId = $page->ID;
        $page->post_content = ''; // make sure parent page content is not processed
        return $page;
    }

    public function onTheContentFilter($content)
    {
        if ((in_the_loop() || apply_filters('drts_wordpress_skip_content_in_the_loop_check', false))
            || (function_exists('is_amp_endpoint') && is_amp_endpoint()) // in_the_loop always returns false (AMP 0.5.1) for AMP single pages
        ) {
            if (isset($this->_singlePageContent)) {
                // Process page content

                $content = $this->_singlePageContent;
                // Remove current filter to prevent loop
                remove_filter('the_content', [$this, 'onTheContentFilter'], 12);
                if (!$this->_singlePageContentFiltered) {
                    // Add back default filters that were removed
                    $this->_addTheContentFilters();
                } else {
                    // Add back shortcode filter only to process [drts-entity] shortcode
                    //add_filter('the_content', 'do_shortcode', 11);
                }

                if (!isset($GLOBALS['drts_entity'])) {
                    // Need to manually setup $GLOBALS['drts_entity'] required by [drts-entity] shortcode with field or display_element parameter.
                    if ($obj = get_queried_object()) {
                        if ($obj instanceof \WP_Post) {
                            if ($this->getApplication()->getComponent('WordPressContent')->hasPostType($obj->post_type)) {
                                $GLOBALS['drts_entity'] = new \SabaiApps\Directories\Component\WordPressContent\EntityType\PostEntity($obj);
                            }
                        } elseif ($obj instanceof \WP_Term) {
                            if ($this->getApplication()->getComponent('WordPressContent')->hasTaxonomy($obj->taxonomy)) {
                                $GLOBALS['drts_entity'] = new \SabaiApps\Directories\Component\WordPressContent\EntityType\TermEntity($obj);
                            }
                        }
                    }
                }
                // Apply the_content filter to page content
                if (isset($GLOBALS['drts_entity'])) {
                    $content = strtr($content, ['%id%' => $GLOBALS['drts_entity']->getId()]);
                }
                $content = apply_filters('the_content', $content);
                // Insert main content
                if (strlen($this->_mainContent)) {
                    // Insert plugin content to where shortcode was if there was any
                    if (strpos($content, '[drts-entity]') !== false) {
                        $content = strtr($content, ['[drts-entity]' => $this->_mainContent]);
                    } else {
                        // Placeholder does not exist for some reason, overwrite with content generated
                        $content = $this->_mainContent;
                    }
                }
                // Add back filter if required to be called multiple times.
                if (!apply_filters('drts_wordpress_the_content_filter_once', true)) {
                    add_filter('the_content', [$this, 'onTheContentFilter'], 12);
                }
            } else {
                if (strlen($this->_mainContent)) {
                    $content = $this->_mainContent;
                }
                $this->_addTheContentFilters();
                // Remove filter if not required to be called multiple times.
                if (apply_filters('drts_wordpress_the_content_filter_once', true)) {
                    remove_filter('the_content', [$this, 'onTheContentFilter'], 12);
                }
            }
        }
        return $content;
    }

    protected function _addTheContentFilters($add = true)
    {
        $func = $add ? 'add_filter' : 'remove_filter';
        $func('the_content', 'wptexturize');
        $func('the_content', 'wpautop');
        $func('the_content', 'convert_smilies');
        $func('the_content', 'convert_chars');
        $func('the_content', 'shortcode_unautop');
        $func('the_content', 'prepend_attachment');
        //$func('the_content', 'do_shortcode', 11);
    }

    public function onGetTheArchiveDescFilter($desc)
    {
        if (!strlen($desc)
            && isset($GLOBALS['drts_entity'])
            && $GLOBALS['drts_entity']->isTaxonomyTerm()
        ) {
            $desc = $GLOBALS['drts_entity']->getContent();
            if (!strlen($desc)) $desc = ' '; // some themes such astra require non-empty string to show the single page content
        }
        return $desc;
    }

    public function onTemplateIncludeFilter($template)
    {
        $templates = [];
        $use_single = $single_page_template = false;
        // Check for custom page template
        if (isset($this->_singlePageId)) {
            $single_page_template = get_page_template_slug($this->_singlePageId);
        }
        // Mimic WordPress template hierarchy for custom post type and taxonomy pages
        if (is_single()) {
            if ($post = get_queried_object()) {
                if (get_query_var('drts_action')) {
                    if ($this->_singleActionForceSingle) $use_single = true;
                } else {
                    if (!$this->_singleForcePage) $use_single = true;
                    $templates[] = 'single-' . $post->post_type . '-' . $post->post_name . '.php';
                    if ($single_page_template) $templates[] = $single_page_template;
                    $templates[] = 'single-' . $post->post_type . '.php';
                }
            }
            $templates[] = 'drts-post.php';
        } elseif (is_tax()) {
            if ($term = get_queried_object()) {
                $templates[] = 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
                if ($single_page_template) $templates[] = $single_page_template;
                $templates[] = 'taxonomy-' . $term->taxonomy . '.php';
            }
            $templates[] = 'drts-term.php';
        }

        $templates[] = $use_single ? 'single.php' : 'page.php';
        $templates[] = 'singular.php';
        $templates[] = 'index.php';

        return get_query_template('page', $templates);
    }

    private function _runMain($route)
    {
        try {
            // Create context
            $request = new Request(true, true); // force stripslashes since WP adds them vis wp_magic_quotes() if magic_quotes_gpc is off
            $context = (new Context())->setRequest($request);

            // Run
            $response = $this->getApplication()
                ->setCurrentScriptName('main')
                ->run(new MainRoutingController(), $context, $route);
            if (!$context->isView()) {
                if ($context->isError()
                    && $context->getErrorType() === 404
                ) {
                    $GLOBALS['wp_query']->set_404();
                    return false;
                }
                $response->send($context);
                exit;
            } else {
                if ($context->getRequest()->isAjax()
                    || $context->getContentType() !== 'html'
                ) {
                    if ($context->getRequest()->isAjax() === '#drts-content') {
                        $response->setInlineLayoutHtmlTemplate(__DIR__ . '/layout/main_inline.html.php');
                    }
                    $response->send($context);
                    exit;
                } else {
                    ob_start();
                    $response->setInlineLayoutHtmlTemplate(__DIR__ . '/layout/main_inline.html.php')
                        ->setLayoutHtmlTemplate(__DIR__ . '/layout/main.html.php')
                        ->send($context);
                    return ob_get_clean();
                }
            }
        } catch (\Exception $e) {
            $this->getApplication()->logError($e);
            if ($this->isAdministrator()
                || $this->isDebugEnabled()
            ) {
                return sprintf('<p>%s</p><p><pre>%s</pre></p>', esc_html($e->getMessage()), esc_html($e->getTraceAsString()));
            }
            return sprintf('<p>%s</p>', 'An error occurred while processing the request. Please contact the administrator of the website for further information.');
        }
    }

    public function runAdmin()
    {
        $page = substr($_GET['page'], strlen('drts/'));
        if (!$route = isset($_GET[$this->getRouteParam()]) ? trim($_GET[$this->getRouteParam()], '/') : null) {
            $route = $page;
        }
        $page_slug = current(explode('/', $route));
        if ($page_slug === '_drts') {
            $page_slug = $page;
        }

        // De-queue scripts to prevent conflicts
        add_action('admin_enqueue_scripts', function() {
            foreach (array_keys(wp_scripts()->registered) as $handle) {
                if (in_array($handle, [
                    'bootstrap.min', 'qodef-ui-admin', 'qodef-ui-repeater', // Bridge theme
                    'tribe-select2', // The Events Calendar plugin
                    'events-manager-pro',
                ])) {
                    wp_deregister_script($handle);
                } elseif (wp_scripts()->registered[$handle]->src
                    && (strpos(wp_scripts()->registered[$handle]->src, 'cs-plugins')
                        || strpos(wp_scripts()->registered[$handle]->src, 'cs-framework')
                    )
                ) {
                    wp_dequeue_script($handle);
                }
            }

        }, PHP_INT_MAX);

        $this->_runAdmin($page_slug, $route);
    }

    protected function _runAdmin($page, $route = null)
    {
        // Create context
        $request = new AdminRequest(true, true);
        $context = (new Context())->setRequest($request);

        try {
            // Run application
            $response = $this->getApplication()
                ->setCurrentScriptName('admin')
                ->run(new AdminRoutingController(), $context, $route);
            // Flush rewrite rules if required
            if ($this->_flushRewriteRules) {
                $this->_flushRewriteRules();
            }

            if (!$context->isView()) {
                $response->send($context);
            } else {
                if ($request->isAjax()
                    || $context->getContentType() !== 'html'
                ) {
                    if ($request->isAjax() === '#drts-content') {
                        $response->setInlineLayoutHtmlTemplate(__DIR__ . '/layout/admin_inline.html.php');
                    }
                    $response->send($context);
                } else {
                    $response->setInlineLayoutHtmlTemplate(__DIR__ . '/layout/admin_inline.html.php')
                        ->setLayoutHtmlTemplate(__DIR__ . '/layout/admin.html.php')
                        ->send($context);
                }
            }
        } catch (\Exception $e) {
            // Display error message
            require_once ABSPATH . 'wp-admin/admin-header.php';
            printf('<p>%s</p><p><pre>%s</pre></p>', esc_html($e->getMessage()), esc_html($e->getTraceAsString()));
            require_once ABSPATH . 'wp-admin/admin-footer.php';
        }
        exit;
    }

    public function onWpEnqueueScripts()
    {
        if (!is_admin()) {
            if (defined('DRTS_WORDPRESS_JQUERY_CDN') && DRTS_WORDPRESS_JQUERY_CDN) {
                wp_deregister_script('jquery');
                wp_register_script('jquery', is_string(DRTS_WORDPRESS_JQUERY_CDN) ? DRTS_WORDPRESS_JQUERY_CDN : '//ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js');
            }

            if (defined('DRTS_WORDPRESS_JQUERY_FOOTER') && DRTS_WORDPRESS_JQUERY_FOOTER) {
                // Load jquery in the footer
                wp_enqueue_script('jquery', '', [], false, true);
            }
        }
    }

    public function onWpEnqueueScriptsLast()
    {
        if (apply_filters('drts_bootstrap_dequeue', true)) {
            wp_dequeue_script('bootstrap');
        }
    }

    public function onWpPrintStyles()
    {
        if (apply_filters('drts_fontawesome_dequeue', true)) {
            wp_dequeue_style('fontawesome');
            wp_dequeue_style('storefront-icons');
        }
    }

    public function onExtraPluginHeadersFilter($headers)
    {
        $headers[] = 'SabaiApps License Package';
        return $headers;
    }

    public function getSabaiPlugins($activeOnly = true, $force = false, $addonsOnly = false)
    {
        $id = 'wordpress_plugins_' . (int)$activeOnly . (int)$addonsOnly;
        if ($force
            || false === $plugin_names = $this->getCache($id)
        ) {
            $plugin_names = [];
            if ($plugin_dirs = glob($this->getPackagePath() . '/*', GLOB_ONLYDIR | GLOB_NOSORT)) {
                if (!function_exists('is_plugin_active')) {
                    require ABSPATH . 'wp-admin/includes/plugin.php';
                }
                foreach ($plugin_dirs as $plugin_dir) {
                    $plugin_name = basename($plugin_dir);
                    if (!$activeOnly
                        || is_plugin_active($plugin_name . '/' . $plugin_name . '.php')
                    ) {
                        if ((!$plugin_data = $this->getPluginData($plugin_name))
                            || empty($plugin_data['Author'])
                            || $plugin_data['Author'] !== 'SabaiApps'
                            || ($addonsOnly && strpos($plugin_name, '-') === false)
                        ) continue;

                        $plugin_names[$plugin_name] = $plugin_data + array(
                            'mo' => file_exists($plugin_dir . '/languages/' . $plugin_name . '.pot'),
                        );
                    }
                }
                ksort($plugin_names);
            }
            $this->setCache($plugin_names, $id);
        }
        return $plugin_names;
    }

    public function onAdminMenuAction()
    {
        $default_cap = current_user_can(DRTS_WORDPRESS_ADMIN_CAPABILITY) ? DRTS_WORDPRESS_ADMIN_CAPABILITY : 'manage_directories';
        $endpoints = $this->_getAdminEndpoints();
        foreach ($endpoints as $path => $endpoint) {
            add_menu_page(
                $endpoint['label'],
                isset($endpoint['label_menu']) ? $endpoint['label_menu'] : $endpoint['label'],
                $capability = isset($endpoint['capability']) ? $endpoint['capability'] : $default_cap,
                'drts' . $path,
                array($this, 'runAdmin'),
                isset($endpoint['icon']) ? $endpoint['icon'] : '',
                $endpoint['order']
            );
            if (!empty($endpoint['children'])) {
                foreach ($endpoint['children'] as $_path => $_endpoint) {
                    add_submenu_page(
                        'drts' . $path,
                        $_endpoint['label'],
                        isset($_endpoint['label_menu']) ? $_endpoint['label_menu'] : $_endpoint['label'],
                        isset($_endpoint['capability']) ? $_endpoint['capability'] : $capability,
                        'drts' . $_path,
                        array($this, 'runAdmin')
                    );
                }
            }
        }
    }

    protected function _getAdminEndpoints()
    {
        if (!$endpoints = $this->getCache('wordpress_admin_endpoints')) {
            $endpoints = $this->getApplication()->Filter('wordpress_admin_endpoints', []);
            foreach (array_keys($endpoints) as $path) {
                if (!empty($endpoints[$path]['children'])) {
                    uasort($endpoints[$path]['children'], function ($a, $b) { return $a['order'] <= $b['order'] ? -1 : 1; });
                }
            }
            $this->setCache($endpoints, 'wordpress_admin_endpoints');
        }
        return $endpoints;
    }

    public function onAdminNoticesAction()
    {
        // Show errors on our application admin pages only
        $is_sabai_page = strpos(get_current_screen()->parent_base, 'drts/') === 0;

        foreach ($this->_flash as $flash) {
            switch ($flash['level']) {
                case 'danger':
                case 'error':
                    $class = 'error';
                    break;
                case 'success':
                case 'warning':
                    if (!$is_sabai_page) continue 2;

                    $class = $flash['level'];
                    break;
                default:
                    if (!$is_sabai_page) continue 2;

                    $class = 'info';
            }
            echo '<div class="notice notice-' . $class . ' is-dismissible"><p>[directories] ' . esc_html($flash['msg']) . '</p></div>';
        }
    }

    public function onPostUpdatedAction($postId, $postAfter, $postBefore)
    {
        // Has slug been changed?
        if ($postAfter->post_name === $postBefore->post_name) return;

        // Is it a SabaiApps application page?
        if (!$slug = $this->_isSabaiPageId($postId)) return;

        // Update SabaiApps application page slug data
        $new_slug = $postAfter->post_name;
        $page_slugs = $this->getPageSlugs();
        $page_slugs[0][$new_slug] = $new_slug;
        $page_slugs[2][$new_slug] = $postId;
        if (isset($page_slugs[5][$slug])) {
            $page_slugs[5][$new_slug] = $page_slugs[5][$slug];
        }
        unset($page_slugs[0][$slug], $page_slugs[2][$slug], $page_slugs[5][$slug]);
        foreach (array_keys($page_slugs[1]) as $component_name) {
            if ($slug_key = array_search($slug, $page_slugs[1][$component_name])) {
                $page_slugs[1][$component_name][$slug_key] = $new_slug;
                break;
            }
        }
        $this->setPageSlugs($page_slugs, null, false);

        // Reload all main routes
        $this->getApplication()->getComponent('System')->reloadAllRoutes(true);

        // Upgrade all ISlug components since slugs have been updated
        $this->getApplication()->System_Component_upgradeAll(array_keys($this->getApplication()->System_Slugs()));

        // Need to manually flush rules since this is not coming from our plugin admin page
        $this->_flushRewriteRules();
    }

    public function isSabaiAppsPlugin($plugin)
    {
        return $plugin === 'drts'
            || in_array($plugin, array_keys(apply_filters('drts_core_component_paths', [])));
    }

    public function onActivatedPluginAction($plugin)
    {
        $component_paths = apply_filters('drts_core_component_paths', []);
        $plugin = basename($plugin, '.php');
        if (isset($component_paths[$plugin])) {
            Installer::getInstance($this)->installPackage($plugin, $component_paths[$plugin][0]);
        }
    }

    public function onDeactivatedPluginAction($plugin)
    {
        $plugin = basename($plugin, '.php');
        if (in_array($plugin, array_keys(apply_filters('drts_core_component_paths', [])))) {
            // Force reload all components
            $this->getApplication()->System_Component_upgradeAll(null, true);
        }
    }

    public function onUpgraderProcessCompleteAction($upgrader, $options)
    {
        if ($options['action'] === 'update'
            && $options['type'] === 'plugin'
            && isset($options['plugins'])
        ) {
            foreach($options['plugins'] as $plugin) {
                if ($plugin === 'directories/directories.php') {
                    // Delete cache to re-check un-updated components
                    $this->deleteCache('system_component_updates');
                }
            }
        }
    }

    public function onAfterSwitchThemeAction()
    {
        $this->deleteCache('wordpress_assets_dir');
    }

    public function onNetworkAdminPluginActionLinks($links, $pluginFile)
    {
        if (strpos($pluginFile, 'directories') === 0) {
            unset($links['activate'], $links['deactivate']);
        }
        return $links;
    }

    public function onDeleteSiteTransientUpdatePluginsAction()
    {
        // Delete component update info
        $this->deleteCache('wordpress_component_updates');

        // Clear old version info currently saved
        $this->getUpdater()->clearOldVersionInfo();
    }

    /**
     * @param bool $loadComponents
     * @param bool $reload
     * @param bool $throwError
     * @return Application
     */
    public function getApplication($loadComponents = true, $reload = false, $throwError = false)
    {
        $this->_loadMo();
        try {
            return parent::getApplication($loadComponents, $reload);
        } catch (Exception\NotInstalledException $e) {
            if ($throwError) throw $e;

            if (!function_exists('deactivate_plugins')) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }
            deactivate_plugins(plugin_basename(Loader::plugin(true)));
            wp_redirect(is_admin() ? admin_url('plugins.php') : home_url());
            exit;
        }
    }

    protected function _createApplication()
    {
        // Init
        $app = parent::_createApplication()
            ->isSsl(is_ssl())
            ->setScriptUrl($main_url = $this->getMainUrl(), 'main')
            ->setScriptUrl(admin_url('admin.php?page=drts/directories'), 'admin');
        // Set mod rewrite format
        $mod_rewrite_format = '%1$s';
        if ($params_pos = strpos($main_url, '?')) { // WP plugins such as WPML adds params to home URL
            $mod_rewrite_format .= substr($main_url, $params_pos);
            $main_url = substr($main_url, 0, $params_pos);
        }
        $app->setModRewriteFormat(rtrim($main_url, '/') . $mod_rewrite_format, 'main');

        // Set custom helpers
        $app->setHelper('GravatarUrl', array($this, 'gravatarUrlHelper'))
            ->setHelper('Slugify', array($this, 'slugifyHelper'))
            ->setHelper('Summarize', array($this, 'summarizeHelper'))
            ->setHelper('Action', array(new ActionHelper(), 'help'))
            ->setHelper('Filter', array(new FilterHelper(), 'help'))
            ->setHelper('Form_Token_create', array($this, 'formTokenCreateHelper'))
            ->setHelper('Form_Token_validate', array($this, 'formTokenValidateHelper'));
        // Custom URL helper if permalink method is Plain
        if (!$this->isAdmin()
            && !get_option('permalink_structure')
        ) {
            $page_slugs = $this->getPageSlugs();
            if (!empty($page_slugs[2])) {
                $app->setHelper('Url', array(new UrlHelper($page_slugs[2]), 'help'));
            }
        }

        return $app;
    }

    public function onInitAction()
    {
        $app = $this->getApplication();

        // Invoke components
        $app->Action('core_platform_wordpress_init');

        // Redirect wp-login.php to custom login page?
        if ($GLOBALS['pagenow'] === 'wp-login.php'
            && (!isset($_REQUEST['action']) || in_array($_REQUEST['action'], array('register', 'login')))
            && $app->getUser()->isAnonymous()
            && $app->isComponentLoaded('FrontendSubmit')
            && ($login_slug = $app->getComponent('FrontendSubmit')->isLoginEnabled())
            && $app->getComponent('FrontendSubmit')->getConfig(isset($_REQUEST['action']) && $_REQUEST['action'] === 'register' ? 'register' : 'login', 'form')
            && ($page = get_page_by_path($login_slug))
            && $this->_isSabaiPageId($page->ID)
        ) {
            wp_redirect($app->Url(
                '/' . $login_slug,
                isset($_REQUEST['redirect_to']) ? array('redirect_to' => $_REQUEST['redirect_to']) : []
            ));
            exit;
        }

        // Add rewrite rules
        foreach ($this->getAllRewriteRules() as $regex => $redirect) {
            add_rewrite_rule($regex, $redirect, 'top');
        }
    }

    public function getAllRewriteRules($force = false)
    {
        $rewrite_rules = [];
        if ($languages = $this->getLanguages()) {
            foreach ($languages as $lang) {
                $rewrite_rules += $this->_getRewriteRules($lang, $force);
            }
        } else {
            $rewrite_rules += $this->_getRewriteRules(null, $force);
        }

        if (($permalink_structure = get_option('permalink_structure'))
            && strpos($permalink_structure, 'index.php') !== false
        ) {
            foreach (array_keys($rewrite_rules) as $key) {
                $rewrite_rules['index.php/' . $key] = $rewrite_rules[$key];
                unset($rewrite_rules[$key]);
            }
        }

        return $rewrite_rules;
    }

    protected function _getRewriteRules($lang, $force = false)
    {
        $cache_id = empty($lang) ? 'wordpress_rewrite_rules' : 'wordpress_rewrite_rules_' . $lang;
        if ($force
            || false === ($ret = $this->getCache($cache_id))
        ) {
            $this->setCache($ret = Util::getRewriteRules($this, $lang), $cache_id, 0);
        }

        return $ret;
    }

    public function getUpdater()
    {
        return Updater::getInstance($this);
    }

    public function onAdminInitAction()
    {
        // Run autoupdater
        if ($this->isAdministrator(get_current_user_id())) {
            // Enable update notification if any license key is set
            $license_keys = $this->getOption('license_keys', []);
            if (!empty($license_keys)) {
                $plugin_names = $this->getSabaiPlugins(false);
                foreach ($license_keys as $plugin_name => $license_key) {
                    if (!isset($plugin_names[$plugin_name])
                        || !strlen((string)@$license_key['value'])
                    ) continue;

                    $this->getUpdater()->addPlugin($plugin_name, $license_key['type'], $license_key['value']);
                    unset($plugin_names[$plugin_name]);
                }
                if (!empty($plugin_names)) {
                    $active_plugin_names = $this->getSabaiPlugins(true);
                    foreach (array_keys($plugin_names) as $plugin_name) {
                        if (isset($active_plugin_names[$plugin_name])) {
                            $this->addFlash([[
                                'level' => 'danger',
                                'msg' => sprintf(__('Please enter a license key for %s in Settings -> Licenses.', 'directories'), $plugin_name),
                            ]]);
                        }
                    }
                }
            }

            // Add a hook to clear cache of upgradable components when plugins are installed/updated/uninstalled
            add_action('delete_site_transient_update_plugins', array($this, 'onDeleteSiteTransientUpdatePluginsAction'));
        }

        // Invoke components
        $this->getApplication()->Action('core_platform_wordpress_admin_init');

        // Register polylang strings
        if ($this->_i18n === 'polylang'
            && ($polylang_strings = $this->getOption('_polylang_strings', []))
        ) {
            foreach (array_keys($polylang_strings) as $group) {
                foreach (array_keys($polylang_strings[$group]) as $name) {
                    pll_register_string($name, $polylang_strings[$group][$name], $group, true);
                }
            }
        }
    }

    public function onWidgetsInitAction()
    {
        $widgets = $this->getApplication()->System_Widgets();

        // Fetch all sabai widgets and then convert each to a wp widget
        foreach ($widgets as $widget_name => $widget) {
            $class = sprintf('SabaiApps_Directories_WordPress_Widget_%s', $widget_name);
            if (class_exists('\\' . $class, false)) continue;

            eval(sprintf('
class %s extends \SabaiApps\Directories\Platform\WordPress\Widget {
    public function __construct() {
        parent::__construct("%s", "%s", "%s");
    }
}
                ', $class, $widget_name, esc_html($widget['title']), esc_html($widget['summary'])));
            register_widget($class);
        }
    }

    public function onWpLoginAction()
    {
        if (!DRTS_WORDPRESS_SESSION_TRANSIENT) {
            Application::startSession(defined('DRTS_WORDPRESS_SESSION_PATH') ? DRTS_WORDPRESS_SESSION_PATH : null);
            session_regenerate_id(true); // to prevent session fixation attack
        }
    }

    public function onWpLogoutAction()
    {
        if (!DRTS_WORDPRESS_SESSION_TRANSIENT && session_id()) {
            $_SESSION = [];
            session_destroy();
        }
    }

    public function onDeleteUserAction($userId)
    {
        // Cache user data here so that we can reference it after the user actually being deleted
        $identity = $this->getApplication()->UserIdentity($userId);
        if (!$identity->isAnonymous()) $this->_userToBeDeleted[$userId] = $identity;
    }

    public function onDeletedUserAction($userId)
    {
        if (!isset($this->_userToBeDeleted[$userId])) return;

        // Notify that a user account has been deleted
        $this->getApplication()->Action('core_platform_user_deleted', array($this->_userToBeDeleted[$userId]));

        unset($this->_userToBeDeleted[$userId]);
    }

    public function onAdminHeadWidgetsPhpAction()
    {
        echo '<style type="text/css">
.drts-form-field {margin:1em 0;}
.drts-form-field > label {display:inline; margin-bottom:2px;}
.drts-form-field input[type=checkbox] {margin-top:0;}
.drts-form-field select,.drts-form-field input[type=text] {width:100%;}
.widget[id*="drts"] .widget-title h3 {overflow: auto; text-overflow: initial;}
</style>';
    }

    public function onAdminPrintStylesAction()
    {
        if (get_current_screen()->id === 'plugins') {
            // Disable update link for all active directories plugins
            $selectors = [];
            foreach ($this->getPackages() as $package) {
                $selectors[] = '#' . $package . '-update.plugin-update-tr.active a.update-link';
            }
            printf(
                '<style type="text/css">
%s {
    cursor: default;
    color: #32373c;
    pointer-events: none;
    text-decoration: none;
}
</style>',
                implode(',', $selectors)
            );
        }
    }

    protected function _loadJqueryJs($type)
    {
        wp_enqueue_script('hoverIntent');
    }

    protected function _loadJsFile($url, $handle, $dependency, $inFooter)
    {
        wp_enqueue_script($handle, $url, (array)$dependency, Application::VERSION, $inFooter);
    }

    protected function _unloadJsFile($handle)
    {
        wp_deregister_script($handle);
        wp_dequeue_script($handle);
    }

    protected function _loadJsInline($dependency, $js, $position)
    {
        wp_add_inline_script($dependency, $js, $position);
    }

    protected function _loadCssFile($url, $handle, $dependency, $media)
    {
        wp_enqueue_style($handle, $url, (array)$dependency, Application::VERSION, $media);
    }

    protected function _unloadCssFile($handle)
    {
        wp_deregister_style($handle);
        wp_dequeue_style($handle);
    }

    protected function _loadJqueryUiJs(array $components)
    {
        if (!isset($this->_jqueryUiCoreLoaded)) {
            wp_enqueue_script('jquery-ui-core');
            $this->_jqueryUiCoreLoaded = [];
        }
        if (!$this->_jqueryUiCssLoaded) {
            $theme_url = apply_filters(
                'drts_jquery_ui_theme_url',
                '//ajax.googleapis.com/ajax/libs/jqueryui/' . $GLOBALS['wp_scripts']->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.min.css'
            );
            if ($theme_url) {
                wp_enqueue_style('drts-jquery-ui', $theme_url);
            }
            $this->_jqueryUiCssLoaded = true;
        }
        foreach ($components as $component) {
            wp_enqueue_script(strpos($component, 'effects') === 0 ? 'jquery-' . $component : 'jquery-ui-' . $component);
        }
    }

    protected function _loadImagesLoadedJs()
    {
        wp_enqueue_script('imagesloaded');
    }

    public function formTokenCreateHelper(Application $application, $tokenId, $tokenLifetime = 1800, $reobtainable = false)
    {
        return wp_create_nonce('drts_' . $tokenId);
    }

    public function formTokenValidateHelper(Application $application, $tokenValue, $tokenId, $reuseable)
    {
        $result = wp_verify_nonce($tokenValue, 'drts_' . $tokenId);
        // 1 indicates that the nonce has been generated in the past 12 hours or less.
        // 2 indicates that the nonce was generated between 12 and 24 hours ago.
        // Use 1 for enhanced security
        return $result === 1;
    }

    public function gravatarUrlHelper(Application $application, $email, $size = 96, $default = 'mm', $rating = null, $secure = false)
    {
        if (preg_match('/src=("|\')(.*?)("|\')/i', get_avatar($email, $size, $default), $matches)) {
            return str_replace('&amp;', '&', $matches[2]);
        }
    }

    public function getSiteToSystemTime($timestamp)
    {
        // mktime should return UTC in WP
        return intval($timestamp - get_option('gmt_offset') * 3600);
    }

    public function getSystemToSiteTime($timestamp)
    {
        return intval($timestamp + get_option('gmt_offset') * 3600);
    }

    public function slugifyHelper(Application $application, $string, $maxLength = 200)
    {
        $slug = rawurldecode(sanitize_title($string));
        return empty($maxLength) ? $slug : substr($slug, 0, $maxLength);
    }

    public function summarizeHelper(Application $application, $text, $length = 0, $trimmarker = '...')
    {
        if (!strlen($text)) return '';

        $text = strip_shortcodes(strip_tags(strtr($text, array("\r" => '', "\n" => ' '))));

        return empty($length) ? $text : $application->System_MB_strimwidth($text, 0, $length, $trimmarker);
    }

    public function activate()
    {
        Installer::getInstance($this)
            ->install(['WordPress' => []])
            ->installPackage('directories');
    }

    public function createPage($slug, $title, $lang = false)
    {
        return Util::createPage($this, $slug, $title, $lang);
    }

    public function getPluginData($pluginName, $key = null, $default = false)
    {
        $plugin_file = $this->getPackagePath() . '/' . $pluginName . '/' . $pluginName . '.php';
        if (!file_exists($plugin_file)) return $default;

        // Fetch plugin data for version comparison
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data($plugin_file, false, false);

        return isset($key) ? (isset($plugin_data[$key]) ? $plugin_data[$key] : $default) : $plugin_data;
    }

    public function unzip($from, $to)
    {
        global $wp_filesystem;
        if (!isset($wp_filesystem)) WP_Filesystem();

        if (true !== $result = unzip_file($from, $to)) {
            throw new Exception\RuntimeException($result->get_error_message());
        }

        return $this;
    }

    public function updateDatabase($schema, $previousSchema = null)
    {
        Util::updateDatabase($this, $schema, $previousSchema);
    }

    public function isAdmin()
    {
        return is_admin();
    }

    public function registerString($str, $name, $group = null)
    {
        switch ($this->_i18n) {
            case 'wpml':
                do_action('wpml_register_single_string', isset($group) ? 'drts-strings-' . $group : 'drts-strings', $name, $str);
                break;
            case 'polylang':
                $strings = $this->getOption('_polylang_strings', []);
                $strings[isset($group) ? 'drts-strings-' . $group : 'drts-strings'][$name] = $str;
                $this->setOption('_polylang_strings', $strings);
                break;
            default:
        }
        return $this;
    }

    public function unregisterString($name, $group = null)
    {
        switch ($this->_i18n) {
            case 'wpml':
                if (function_exists('icl_unregister_string')) {
                    icl_unregister_string(isset($group) ? 'drts-strings-' . $group : 'drts-strings', $name);
                }
                break;
            case 'polylang':
                $strings = $this->getOption('_polylang_strings', []);
                unset($strings[isset($group) ? 'drts-strings-' . $group : 'drts-strings'][$name]);
                $this->setOption('_polylang_strings', $strings);
                break;
            default:
        }
        return $this;
    }

    public function translateString($str, $name, $group = null, $lang = null)
    {
        switch ($this->_i18n) {
            case 'wpml':
                return apply_filters('wpml_translate_single_string', $str, isset($group) ? 'drts-strings-' . $group : 'drts-strings', $name, $lang);
            case 'polylang':
                return isset($lang) ? pll_translate_string($str, $lang) : pll__($str);
            default:
                return $str;
        }
    }

    public function getLanguages()
    {
        switch ($this->_i18n) {
            case 'wpml':
                return array_keys(apply_filters('wpml_active_languages', []));
            case 'polylang':
                return pll_languages_list();
            default:
                return [];
        }
    }

    public function getDefaultLanguage()
    {
        switch ($this->_i18n) {
            case 'wpml':
                return ($lang = apply_filters('wpml_default_language', null)) ? $lang : null;
            case 'polylang':
                return ($lang = pll_default_language()) ? $lang : null;
            default:
        }
    }

    public function getCurrentLanguage()
    {
        switch ($this->_i18n) {
            case 'wpml':
                return ($lang = apply_filters('wpml_current_language', null)) ? $lang : null;
            case 'polylang':
                // Current language may be different when editing translation, so return language of current translation
                if (is_admin() && !empty($_REQUEST['post_lang_choice'])) return $_REQUEST['post_lang_choice'];

                if ($lang = pll_current_language()) return $lang;
                // Lang may be null when called before wp action, so get from cookie
                $cookie_name = defined('PLL_COOKIE') ? PLL_COOKIE : 'pll_language';
                return isset($_COOKIE[$cookie_name]) ? sanitize_key($_COOKIE[$cookie_name]) : null;
            default:
        }
    }

    public function isTranslatable($entityType, $bundleName)
    {
        switch ($this->_i18n) {
            case 'wpml':
                return $entityType === 'term' ? is_taxonomy_translated($bundleName) : is_post_type_translated($bundleName);
            case 'polylang':
                return $entityType === 'term' ? pll_is_translated_taxonomy($bundleName) : pll_is_translated_post_type($bundleName);
            default:
        }
    }

    public function getTranslatedId($entityType, $bundleName, $id, $lang)
    {
        switch ($this->_i18n) {
            case 'wpml':
                return apply_filters('wpml_object_id', $this->_getWpmlElementId($bundleName, $id), $bundleName, false, $lang);
            case 'polylang':
                $_id = $entityType === 'term' ? pll_get_term($id, $lang) : pll_get_post($id, $lang);
                // false is returned when post is not translatable
                return $_id === false ? $id : $_id;
            default:
        }
    }

    public function setTranslations($entityType, $bundleName, $sourceLang, $entityId, $transLang, $transEntityId)
    {
        switch ($this->_i18n) {
            case 'wpml':
                if ((!$ele_id = $this->_getWpmlElementId($bundleName, $entityId))
                    || (!$trans_ele_id = $this->_getWpmlElementId($bundleName, $transEntityId))
                ) return;

                // Get the language info of the original post
                $original_lang_info = apply_filters('wpml_element_language_details', null, [
                    'element_id' => $ele_id,
                    'element_type' => $bundleName
                ]);
                // Associate translation
                do_action('wpml_set_element_language_details', [
                    'element_id'    => $trans_ele_id,
                    'element_type'  => apply_filters('wpml_element_type', $bundleName),
                    'trid'   => $original_lang_info->trid,
                    'language_code'   => $transLang,
                    'source_language_code' => $original_lang_info->language_code
                ]);
                break;
            case 'polylang':
                switch ($entityType) {
                    case 'term':
                        pll_set_term_language($transEntityId, $transLang);
                        pll_save_term_translations([$sourceLang => $entityId, $transLang => $transEntityId]);
                        break;
                    case 'post':
                        pll_set_post_language($transEntityId, $transLang);
                        pll_save_post_translations([$sourceLang => $entityId, $transLang => $transEntityId]);
                        break;
                    default:
                }
                break;
            default:
        }
        return $this;
    }

    public function getLanguageFor($entityType, $bundleName, $id)
    {
        switch ($this->_i18n) {
            case 'wpml':
                return apply_filters('wpml_element_language_code', null, ['element_id' => $this->_getWpmlElementId($bundleName, $id), 'element_type' => $bundleName]);
            case 'polylang':
                return $entityType === 'term' ? pll_get_term_language($id) : pll_get_post_language($id);
            default:
        }
    }

    public function isAdminAddTranslation(array $reqParams)
    {
        switch ($this->_i18n) {
            case 'wpml':
                return !empty($reqParams['trid']) && class_exists('\SitePress', false) && \SitePress::get_original_element_id_by_trid($reqParams['trid']);
            case 'polylang':
                return !empty($reqParams['new_lang']) && !empty($reqParams['from_post']);
            default:
                return false;
        }
    }

    protected function _getWpmlElementId($bundleName, $id)
    {
        if (post_type_exists($bundleName)) return $id;
        if (taxonomy_exists($bundleName)
            && ($term = get_term($id, $bundleName))
            && $term instanceof \WP_Term
        ) return $term->term_taxonomy_id; // WPML uses term_taxonomy_id
    }

    public function isDebugEnabled()
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    public function isAmpEnabled($bundleName)
    {
        return false;
        return function_exists('is_amp_endpoint') && post_type_supports($bundleName, AMP_QUERY_VAR);
    }

    public function isAmp()
    {
        return is_amp_endpoint();
    }

    public function addFlash(array $flash)
    {
        if (!$this->isAdmin()) return parent::addFlash($flash);

        foreach ($flash as $_flash) $this->_flash[] = $_flash;

        return $this;
    }

    public function getTemplate()
    {
        return Template::getInstance($this);
    }

    public function remoteGet($url, array $args = [])
    {
        return $this->_sendRequest($url, $args);
    }

    public function remotePost($url, array $params = [], array $args = [])
    {
        return $this->_sendRequest($url, ['body' => $params] + $args, 'post');
    }

    protected function _sendRequest($url, array $args = [], $method = 'get')
    {
        $func = strtolower($method) === 'post' ? 'wp_remote_post' : 'wp_remote_get';
        $response = $func($url, $args);
        if (is_wp_error($response)) {
            throw new Exception\RuntimeException($response->get_error_message());
        }
        if (200 != ($code = wp_remote_retrieve_response_code($response))) {
            throw new Exception\RuntimeException(
                'The server did not return a valid response. Request sent to: ' . $url . '; Response code: ' . $code
                . '; Response message: ' . wp_remote_retrieve_response_message($response)
            );
        }
        return $response['body'];
    }

    public function anonymizeEmail($email)
    {
        return wp_privacy_anonymize_data('email', $email);
    }

    public function anonymizeUrl($url)
    {
        return wp_privacy_anonymize_data('url', $url);
    }

    public function anonymizeIp($ip)
    {
        return wp_privacy_anonymize_data('ip', $ip);
    }

    public function anonymizeText($text)
    {
        return wp_privacy_anonymize_data('text', $text);
    }

    public function downloadUrl($url, $save = false, $title = null, $ext = null)
    {
        // Check if already saved
        if ($save) {
            if (!isset($title)
                || !is_string($title)
                || !strlen($title = trim($title))
            ) {
                throw new Exception\RuntimeException('Invalid download file title.');
            }

            $slug = 'drts-download-url-' . sanitize_title($title);
            if ($attachment = get_page_by_path($slug, OBJECT, 'attachment')) {
                if ($url = wp_get_attachment_url($attachment->ID)) return $url;

                wp_delete_attachment($attachment->ID, /*force*/ true);
            }
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Download
        $file_path = download_url($url);
        if (is_wp_error($file_path)) {
            throw new Exception\RuntimeException($url . ': ' . $file_path->get_error_message());
        }

        // Save file?
        if (!$save) return $file_path;
        // $save can be a custom function to determine whether or not to save the file
        if ($save instanceof \Closure) {
            if (!$save($file_path)) return $file_path;
        }

        // Save
        $id = media_handle_sideload(
            [
                'name' => sanitize_file_name($title . (string)$ext),
                'tmp_name' => $file_path,
            ],
            0, // post ID
            null, // desc
            [
                'post_name' => $slug,
                'post_title' => $title,
            ]
        );
        if (is_wp_error($id)) {
            @unlink($file_path);
            throw new Exception\RuntimeException($url . ': ' . $id->get_error_message());
        }

        // Get URL
        if (!$url = wp_get_attachment_url($id)) {
            throw new Exception\RuntimeException('Failed retrieving URL for attachment ID:' . $id);
        }

        return $url;
    }

    public function uploadFile($path, $name, $title)
    {
        // Save
        $attachment_id = media_handle_sideload(
            [
                'name' => $name,
                'tmp_name' => $path,
            ],
            0, // post ID
            null, // desc
            [
                'post_name' => 'drts-' . sanitize_title($name),
                'post_title' => $title,
            ]
        );
        if (is_wp_error($attachment_id)) {
            throw new Exception\RuntimeException($attachment_id->get_error_message());
        }

        return $attachment_id;
    }

    public function numberFormat($number, $decimals = 0)
    {
        return number_format_i18n($number, $decimals);
    }

    public function encodeString($str, $schemaType, $columnName)
    {
        global $wpdb;
        if ('utf8' === $wpdb->get_col_charset($wpdb->prefix . 'drts_entity_field_' . $schemaType, $columnName)) {
            $str = wp_encode_emoji($str);
        }
        return $str;
    }
}