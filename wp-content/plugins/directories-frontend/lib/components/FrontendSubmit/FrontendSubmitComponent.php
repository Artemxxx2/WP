<?php
namespace SabaiApps\Directories\Component\FrontendSubmit;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\CSV;
use SabaiApps\Framework\User\AbstractIdentity;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\Exception;

class FrontendSubmitComponent extends AbstractComponent implements
    System\IMainRouter,
    System\IAdminRouter,
    Field\ITypes,
    Field\IWidgets,
    System\ISlugs,
    Display\IButtons,
    CSV\IExporters,
    CSV\IImporters,
    IRestrictors
{
    const VERSION = '1.3.108', PACKAGE = 'directories-frontend';

    const REGISTER_FORM_FIELD_WEIGHT_USER_NAME = 1, REGISTER_FORM_FIELD_WEIGHT_EMAIL = 5,
        REGISTER_FORM_FIELD_WEIGHT_PASSWORD = 10, REGISTER_FORM_FIELD_WEIGHT_PASSWORD_CONFIRM = 12;
    
    public static function description()
    {
        return 'Enables registered and non-registered users to submit content from the frontend.';
    }
    
    public function systemMainRoutes($lang = null)
    {
        $routes = [];
        if ($this->hasSlug('login', $lang)) {
            $login_slug = $this->getSlug('login', $lang);
            $routes['/' . $login_slug] = [
                'controller' => 'LoginOrRegister',
                'access_callback' => true,
                'callback_path' => 'login',
                'title_callback' => true,
            ];
            $routes['/' . $login_slug . '/lost_password'] = [
                'controller' => 'LostPassword',
                'callback_path' => 'lost_password',
                'title_callback' => true,
            ];
            $routes['/' . $login_slug . '/reset_password'] = [
                'controller' => 'ResetPassword',
                'access_callback' => true,
                'callback_path' => 'reset_password',
                'title_callback' => true,
            ];
            $routes['/' . $login_slug . '/verify_account'] = [
                'controller' => 'VerifyAccount',
                'access_callback' => true,
                'callback_path' => 'verify_account',
            ];
            $routes['/' . $login_slug . '/resend_verify_account_key'] = [
                'controller' => 'ResendVerifyAccountKey',
                'access_callback' => true,
                'callback_path' => 'resend_verify_account_key',
            ];
        }
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (!$this->_application->isComponentLoaded($bundle->component)
                || !empty($bundle->info['is_taxonomy'])
                || !empty($bundle->info['is_user'])
                || !empty($bundle->info['internal'])
            ) continue;

            if (empty($bundle->info['parent'])) {
                $routes['/' . $this->_application->FrontendSubmit_AddEntitySlug($bundle, $lang)] = array(
                    'controller' => 'AddEntity',
                    'access_callback' => true,
                    'title_callback' => true,
                    'callback_path' => 'add_entity',
                    'data' => array(
                        'bundle_type' => $bundle->type,
                    ),
                );
            } else {
                $routes['/' . $this->_application->FrontendSubmit_AddEntitySlug($bundle, $lang)] = array(
                    'controller' => 'AddChildEntity',
                    'access_callback' => true,
                    'title_callback' => true,
                    'callback_path' => 'add_child_entity',
                    'data' => array(
                        'bundle_type' => $bundle->type,
                        'component' => $bundle->component,
                        'group' => $bundle->group,
                    ),
                );
            }
        }
        
        return $routes;
    }
    
    protected function _getBundle(array $route)
    {
        return $this->_application->Entity_Bundle($route['data']['bundle_type'], $route['data']['component'], $route['data']['group']);
    }

    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'add_child_entity':
                if (!isset($context->child_bundle)) {
                    if (!$bundle = $this->_getBundle($route)) return false;

                    $context->child_bundle = $bundle;
                }
            case 'add_entity':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    $context->bundle_type = $route['data']['bundle_type'];
                }
                return true;
            case 'login':
                if ($accessType === Application::ROUTE_ACCESS_LINK
                    || $this->_application->getUser()->isAnonymous()
                ) return true;

                // Redirect to frontend dashboard if enabled
                if ($this->_application->isComponentLoaded('Dashboard')
                    && ($dashboard_slug = $this->_application->getComponent('Dashboard')->getSlug('dashboard'))
                ) {
                    $context->setRedirect('/' . $dashboard_slug);
                }
                return false;
            case 'verify_account':
            case 'resend_verify_account_key':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if (!$this->getConfig('register', 'verify_email')
                        || !$this->_application->getUser()->isAnonymous()
                        || (!$id = $context->getRequest()->asInt('id'))
                        || (!$identity = $this->_application->getPlatform()->getUserIdentityFetcher()->fetchById($id))
                        || !$this->_application->FrontendSubmit_VerifyAccount_isRequired($identity->id)
                    ) return false;

                    if ($path === 'verify_account') {
                        if (!$key = $context->getRequest()->asStr('key')) {
                            return false;
                        }
                        $context->key = $key;
                    }
                    $context->identity = $identity;
                }
                return true;
            case 'reset_password':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if (!$this->_application->getUser()->isAnonymous()) {
                        if ($this->_application->isComponentLoaded('Dashboard')
                            && ($dashboard_slug = $this->_application->getComponent('Dashboard')->getSlug('dashboard'))
                        ) {
                            $context->setRedirect('/' . $dashboard_slug);
                        }
                        return false;
                    }

                    if (!$cookie = $this->_application->System_Cookie('drts-frontendsubmit-reset-password')) {
                        // No cookie, so set a cookie and redirect if valid id/key

                        if ((!$key = $context->getRequest()->asStr('key'))
                            || (!$id = $context->getRequest()->asInt('id'))
                            || (!$identity = $this->_application->getPlatform()->getUserIdentityFetcher()->fetchById($id))
                        ) return false;

                        try {
                            if (!$this->_application->getPlatform()->checkResetPasswordKey($key, $identity)) return false;
                        } catch (\Exception $e) {
                            $this->_application->logError($e);
                            return false;
                        }

                        if (!headers_sent()) {
                            $this->_application->System_Cookie('drts-frontendsubmit-reset-password', sprintf('%d:%s', $id, $key));
                            $context->setRedirect($route['path']);
                            return false;
                        }
                    }
                    if (!isset($key)
                        || !isset($identity)
                    ) {
                        if ((!$parts = explode(':', $cookie))
                            || count($parts) !== 2
                            || (!$id = intval($parts[0]))
                            || (!$key = $parts[1])
                            || (!$identity = $this->_application->getPlatform()->getUserIdentityFetcher()->fetchById($id))
                        ) return false;
                    }

                    try {
                        if (!$this->_application->getPlatform()->checkResetPasswordKey($key, $identity)) return false;
                    } catch (\Exception $e) {
                        $this->_application->logError($e);
                        return false;
                    }
                    $context->identity = $identity;
                }
                return true;
        }
    }

    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'login':
                return $this->getTitle('login');
            case 'lost_password':
                return __('Lost your password?', 'directories-frontend');
            case 'reset_password':
                return __('Reset Password', 'directories-frontend');
            case 'add_child_entity_non_public':
            case 'add_child_entity':
                return $context->child_bundle->getLabel('add');
            case 'add_entity':
                return $this->_application->FrontendSubmit_AddEntitySlug_title($context->bundle_type);
        }
    }

    public function systemAdminRoutes()
    {
        return [
            '/_drts/frontendsubmit/verify_account' => [
                'controller' => 'VerifyAccount',
                'access_callback' => true,
                'callback_path' => 'verify_account',
            ],
            '/_drts/frontendsubmit/unverify_account' => [
                'controller' => 'UnverifyAccount',
                'access_callback' => true,
                'callback_path' => 'unverify_account',
            ],
            '/_drts/frontendsubmit/resend_verify_account_key' => [
                'controller' => 'ResendVerifyAccountKey',
                'access_callback' => true,
                'callback_path' => 'resend_verify_account_key',
            ],
        ];
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'verify_account':
            case 'unverify_account':
            case 'resend_verify_account_key':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if (!$this->getConfig('register', 'verify_email')
                        || (!$id = $context->getRequest()->asInt('id'))
                        || (!$identity = $this->_application->getPlatform()->getUserIdentityFetcher()->fetchById($id))
                    ) return false;

                    $context->identity = $identity;
                }
                return true;
        }
    }

    public function systemAdminRouteTitle(Context $context, $path, $title, array $route) {}

    public function fieldGetTypeNames()
    {
        return ['frontendsubmit_guest'];
    }
    
    public function fieldGetType($name)
    {
        switch ($name) {
            case 'frontendsubmit_guest':
                return new FieldType\GuestFieldType($this->_application, $name);
        }
    }
    
    public function fieldGetWidgetNames()
    {
        return ['frontendsubmit_guest'];
    }
    
    public function fieldGetWidget($name)
    {
        switch ($name) {
            case 'frontendsubmit_guest':
                return new FieldWidget\GuestFieldWidget($this->_application, $name);
        }
    }
    
    public function systemSlugs()
    {
        return array(
            'login' => array(
                'admin_title' => __('Login/Registration', 'directories-frontend'),
                'title' => __('Login or Register', 'directories-frontend'),
                'wp_shortcode' => 'drts-frontend-login',
                'required' => false,
            ),
        );
    }

    public function isLoginEnabled()
    {
        return (($slug = $this->hasSlug('login'))
            && !$this->_application->Filter('frontendsubmit_login_disable', false)
            && (!defined('DRTS_FRONTENDSUBMIT_LOGIN_DISABLE') || !DRTS_FRONTENDSUBMIT_LOGIN_DISABLE)
        ) ? $slug : false;
    }
        
    public function onCoreLoginUrlFilter(&$url, $redirect)
    {
        if ($this->isLoginEnabled()) {
            $url = (string)$this->_application->Url('/' . $this->getSlug('login'), array('redirect_to' => $redirect));
        }
    }
    
    public function onSystemSlugsFilter(&$slugs)
    {
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (!$this->_application->isComponentLoaded($bundle->component)
                || !empty($bundle->info['is_taxonomy'])
                || !empty($bundle->info['is_user'])
                || !empty($bundle->info['parent'])
                || !empty($bundle->info['internal'])
            ) continue;
            
            $add_slug = $this->_application->FrontendSubmit_AddEntitySlug_name($bundle->type);
            if (isset($slugs[$this->_name][$add_slug])) continue;
            
            $info = $this->_application->Entity_BundleTypeInfo($bundle);
            $slugs[$this->_name][$add_slug] = array(
                'slug' => $add_slug,
                'admin_title' => $info['label_add'],
                'title' => $info['label_add'],
                'component' => $this->_name,
                'wp_shortcode' => 'drts-' . $add_slug . '-form',
             );
        }
    }
    
    public function onEntityIsAuthorFilter(&$false, Entity\Type\IEntity $entity)
    {
        if (!$entity->getAuthorId() // must be a guest post
            && ($guid = $entity->getSingleFieldValue('frontendsubmit_guest', 'guid'))
            && ($cookie_guids = $this->_application->FrontendSubmit_GuestAuthorCookie_guids())
            && in_array($guid, $cookie_guids)
        ) {
            $false = true;
        }
    }
    
    public function onEntityAuthorFilter(AbstractIdentity $author, Entity\Type\IEntity $entity)
    {
        if ($author->isAnonymous()
            && $author->name === null
            && ($guest = $entity->getSingleFieldValue('frontendsubmit_guest'))
        ) {
            foreach (['name', 'email', 'url'] as $key) {
                if (strlen($guest[$key])) $author->$key = $guest[$key];
            }
        }
    }
    
    protected function _onEntityCreateBundlesSuccess(array $bundles, $update = false)
    {
        foreach ($bundles as $bundle) {
            if (empty($bundle->info['is_taxonomy'])
                && $this->_application->Entity_BundleTypeInfo($bundle, 'frontendsubmit_guest')
            ) {
                $this->_application->getComponent('Entity')->createEntityField(
                    $bundle,
                    'frontendsubmit_guest',
                    array(
                        'type' => 'frontendsubmit_guest',
                        'max_num_items' => 1,
                    )
                );
            }
        }
    }
    
    public function onEntityCreateBundlesSuccess(array $bundles)
    {
        $this->_onEntityCreateBundlesSuccess($bundles);
    }
    
    public function onEntityUpdateBundlesSuccess(array $bundles)
    {
        $this->_onEntityCreateBundlesSuccess($bundles, true);
    }
    
    public function onEntityPermissionsFilter(&$permissions, Entity\Model\Bundle $bundle)
    {
        // Enable some guest permissions
        if (empty($bundle->info['is_taxonomy'])) {
            if (!empty($bundle->info['public'])
                && empty($bundle->info['is_user'])
                && empty($bundle->info['internal'])
            ) {
                $permissions['entity_create']['guest_allowed'] = true;
                $permissions['entity_publish']['guest_allowed'] = true;
                //$permissions['entity_edit']['guest_allowed'] = true;
                //$permissions['entity_edit_published']['guest_allowed'] = true;
                //$permissions['entity_delete']['guest_allowed'] = true;
                //$permissions['entity_delete_published']['guest_allowed'] = true;
            }
        } else {
            $permissions['entity_assign']['guest_allowed'] = true;
        }
    }
    
    public function displayGetButtonNames(Entity\Model\Bundle $bundle)
    {
        $ret = [];
        if (empty($bundle->info['is_taxonomy'])
            && empty($bundle->info['is_user'])
        ) {
            if (empty($bundle->info['parent'])) {
                foreach ($this->_application->Entity_BundleTypes_children($bundle->type) as $bundle_type) {
                    if (!$this->_application->Entity_Bundle($bundle_type, $bundle->component, $bundle->group)) continue;
                    
                    $ret[] = 'frontendsubmit_add_' . $bundle_type;
                }
            }
        }
        return $ret;
    }
    
    public function displayGetButton($name)
    {
        return new DisplayButton\AddEntityDisplayButton($this->_application, $name);
    }
    
    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        $form['tabs'][$this->_name] = [
            '#title' => __('Frontend Submit', 'directories-frontend'),
            '#weight' => 16,
        ];
        $form['fields'][$this->_name] = [
            '#component' => $this->_name,
            '#tab' => $this->_name,
        ] + $this->_application->FrontendSubmit_SettingsForm($this->_config, [$this->_name]);
    }

    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {
        if (!isset($info['frontendsubmit_enable'])
            || !$info['frontendsubmit_enable']
            || !empty($info['parent'])
            || !empty($info['is_taxonomy'])
        ) return;

        $form['frontendsubmit_enable'] = array(
            '#type' => 'checkbox',
            '#title' => __('Enable frontend submit', 'directories-frontend'),
            '#default_value' => !empty($settings['frontendsubmit_enable'])
                || !isset($settings['frontendsubmit_enable']) // for compat with < 1.1.2
                || is_null($settings),
            '#horizontal' => true,
            '#weight' => 30,
        );
    }

    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {
        if (!isset($info['frontendsubmit_enable'])) return;

        if (!$info['frontendsubmit_enable']
            || !empty($info['is_taxonomy'])
        ) {
            unset($info['frontendsubmit_enable']);
        }

        if (!empty($info['parent'])) {
            $info['frontendsubmit_enable'] = true;
        } else {
            if (isset($settings['frontendsubmit_enable']) && !$settings['frontendsubmit_enable']) {
                $info['frontendsubmit_enable'] = false;
            }
        }
    }

    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'frontendsubmit_enable';
        $keys[] = 'frontendsubmit_restrict';
    }
    
    public function csvGetImporterNames()
    {
        return ['frontendsubmit_guest'];
    }
    
    public function csvGetImporter($name)
    {
        return new CSVImporter\GuestCSVImporter($this->_application, $name);
    }
    
    public function csvGetExporterNames()
    {
        return ['frontendsubmit_guest'];
    }
    
    public function csvGetExporter($name)
    {
        return new CSVExporter\GuestCSVExporter($this->_application, $name);
    }

    public function frontendsubmitGetRestrictorNames()
    {
        return ['default'];
    }

    public function frontendsubmitGetRestrictor($name)
    {
        return new Restrictor\DefaultRestrictor($this->_application, $name);
    }

    public function isLoginFormEnabled()
    {
        if (!$slug = $this->isLoginEnabled()) return false;

        if ($this->_application->getPlatform()->isLoginFormRequired()) return $slug;

        return (!empty($this->_config['login']['form'])
            || (!isset($this->_config['login']['form']) && !empty($this->_config['login']['login_form'])) // for compat with <=1.2.42
        ) ? $slug : false;
    }

    public function showLostPasswordLink()
    {
        return !isset($this->_config['login']['lost_pass_link']) || $this->_config['login']['lost_pass_link'];
    }

    public function isRegisterFormEnabled()
    {
        if ($this->_application->getPlatform()->isRegisterFormRequired()) return true;

        return !empty($this->_config['register']['form'])
            || (!isset($this->_config['register']['form']) && !empty($this->_config['login']['register_form'])); // for compat with <=1.2.42
    }

    public function isCollectGuestInfo()
    {
        $config = empty($this->_config['guest']) ? [] : $this->_config['guest'];
        $ret = !empty($config['collect_name'])
            || !isset($config['collect_name']) // compat with <1.2.58
            || !empty($config['collect_email'])
            || !empty($config['collect_url'])
            || !empty($config['collect_privacy']);
        return $this->_application->Filter('frontendsubmit_collect_guest_info', $ret);
    }

    public function onWordPressDoShortcodeFilter(&$ret, $shortcode, $component)
    {
        switch ($shortcode) {
            case 'drts-frontend-login':
                if (!$this->isLoginFormEnabled()) return;

                $path = '/' . $this->getSlug('login');
                $url = (string)$this->_application->Url($path);
                $url_requested = (string)Request::url(false);
                if (strpos($url_requested, $url) === 0
                    && ($_path = substr($url_requested, strlen($url)))
                    && ($_path = trim($_path, '/'))
                ) {
                    $ret['path'] = [
                        'path' => $path . '/' . $_path, // additional path
                        'params' => empty($_REQUEST) ? [] : $_REQUEST,
                    ];
                }
                break;
            case 'drts-frontend-add-entity-link':
                if (empty($ret['atts']['directory'])) {
                    throw new Exception\RuntimeException('Shortcode [' . $shortcode . ']: Directory is not specified.');
                }
                foreach ($this->_application->Entity_Bundles(null, 'Directory', $ret['atts']['directory']) as $_bundle) {
                    if (!empty($_bundle->info['is_primary'])) {
                        $bundle = $_bundle;
                        break;
                    }
                }
                if (!isset($bundle)) {
                    throw new Exception\RuntimeException('Shortcode [' . $shortcode . ']: content type not found for directory (' . $ret['atts']['directory'] . '). Make sure directory exists.');
                }
                $options = [];
                if (isset($ret['atts']['label'])) $options['label'] = $ret['atts']['label'];
                if (!empty($ret['atts']['icon'])) $options['icon'] = $ret['atts']['icon'];
                if ($show_button = !empty($ret['atts']['button'])) {
                    $options['button'] = [];
                    foreach (['size', 'color'] as $key) {
                        if (!empty($ret['atts']['button_' . $key])) {
                            $options['button'][$key] = $ret['atts']['button_' . $key];
                        }
                    }
                }
                try {
                    $content = $this->_application->FrontendSubmit_Submission_addEntityLink($bundle, $options);
                    if ($show_button) $content = '<span class="drts">' . $content . '</span>';
                } catch (\Exception $e) {
                    $content = $e->getMessage();
                }
                $ret['content'] = $content;
                break;
        }
    }

    public function onEntityIsRoutableFilter(&$isRoutable, $bundle, $action, $entity = null)
    {
        if ($isRoutable === false
            || $action !== 'add'
            || !$this->_application->Entity_BundleTypeInfo($bundle, 'frontendsubmit_enable')
        ) return;

        if (empty($bundle->info['frontendsubmit_enable'])) {
            $isRoutable = false;
        } else {
            // Check if submission is restricted
            if (!empty($bundle->info['parent'])) {
                if (!isset($entity) // this should not happen
                    || !$this->_application->FrontendSubmit_Restrictors_isAllowed($bundle, $this->_application->getUser()->getIdentity(), $entity->getId())
                ) {
                    $isRoutable = false;
                }
            } else {
                if (!$this->_application->FrontendSubmit_Restrictors_isAllowed($bundle, $this->_application->getUser()->getIdentity())) {
                    $isRoutable = false;
                }
            }
        }
    }

    public function onCorePlatformWordPressAdminInit()
    {
        if (isset($GLOBALS['pagenow'])
            && $GLOBALS['pagenow'] === 'users.php'
            && $this->getConfig('register', 'verify_email')
            && current_user_can('edit_users')
        ) {
            $this->_application->FrontendSubmit_VerifyAccount_wordPressUsersColumn();
        }
    }

    public function onSystemCron(&$logs, &$lastRun, $force)
    {
        if (!$force) {
            if (!$this->_application->callHelper('System_Cron_canRunTask', [$this->_name, &$logs, &$lastRun])) return;
        }
        if (!$this->getConfig('register', 'verify_email')
            || !$this->getConfig('register', 'verify_email_settings', 'delete')
            || (!$days = $this->getConfig('register', 'verify_email_settings', 'delete_after'))
        ) return;

        $this->_application->callHelper('FrontendSubmit_VerifyAccount_deleteUnverified', [$days, &$logs]);
    }

    public function onWordpressShortcodesFilter(&$shortcodes)
    {
        $shortcodes['drts-frontend-add-entity-link'] = [
            'component' => $this->_name,
        ];
    }
}