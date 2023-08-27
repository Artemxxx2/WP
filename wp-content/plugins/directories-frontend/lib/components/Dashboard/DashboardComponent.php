<?php
namespace SabaiApps\Directories\Component\Dashboard;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\View;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;
use SabaiApps\Framework\User\AbstractIdentity;

class DashboardComponent extends AbstractComponent implements
    System\IMainRouter,
    System\ISlugs,
    View\IModes,
    Display\IButtons,
    IPanels
 {
    const VERSION = '1.3.108', PACKAGE = 'directories-frontend';

    public static function description()
    {
        return 'Adds a frontend dashboard to your site where users can manage their own content items.';
    }

    protected function _init()
    {
        // Compat with <v1.3.0
        if (!isset($this->_config['panels'])
            && isset($this->_config['panel'])
        ) {
            $this->_config += $this->_config['panel'];
            unset($this->_config['panel']);
        }
    }

    public function systemSlugs()
    {
        return array(
            'dashboard' => array(
                'admin_title' => __('Frontend Dashboard', 'directories-frontend'),
                'title' => __('Dashboard', 'directories-frontend'),
                'wp_shortcode' => 'drts-dashboard',
            ),
        );
    }

    protected function _sortPanels(array $panels)
    {
        if (!empty($this->_config['panels']['default'])) {
            $new_panels = [];
            foreach ($this->_config['panels']['default'] as $panel_name) {
                if (!isset($panels[$panel_name])) continue;

                $new_panels[$panel_name] = $panels[$panel_name];
            }
            $panels = $new_panels;
        }
        return $panels;
    }

    public function systemMainRoutes($lang = null)
    {
        $base = '/' . $this->getSlug('dashboard', $lang);
        $routes = [
            $base => [
                'controller' => 'Panel',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'dashboard',
                'priority' => 3,
            ],
            $base  . '/:user_name' => [
                'controller' => 'Panel',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'user',
                'priority' => 4,
            ],
            $base  . '/:user_name/:panel_name' => [
                'controller' => 'Panel',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'panel',
                'priority' => 4,
            ],
            $base . '/:user_name/:panel_name/posts' => [
                'access_callback' => true,
                'callback_path' => 'posts',
                'priority' => 3,
            ],
            $base . '/:user_name/:panel_name/posts/:entity_id' => [
                'format' => array(':entity_id' => '\d+'),
                'controller' => 'EditPost',
                'access_callback' => true,
                'callback_path' => 'edit_post',
                'priority' => 3,
            ],
            $base . '/:user_name/:panel_name/posts/:entity_id/delete' => [
                'controller' => 'DeletePost',
                'access_callback' => true,
                'callback_path' => 'delete_post',
                'priority' => 3,
            ],
            $base  . '/:user_name/:panel_name/posts/:entity_id/submit' => [
                'controller' => 'SubmitPost',
                'access_callback' => true,
                'callback_path' => 'submit_post',
                'priority' => 3,
            ],
            $base . '/:user_name/:panel_name/change_password' => [
                'controller' => 'ChangePassword',
                'callback_path' => 'change_password',
                'priority' => 3,
            ],
            $base . '/:user_name/:panel_name/delete_account' => [
                'controller' => 'DeleteAccount',
                'callback_path' => 'delete_account',
                'priority' => 3,
            ],
        ];

        return $routes;
    }

    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'dashboard':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if (!isset($context->is_dashboard)) $context->is_dashboard = true;
                    return true;
                }

                if ($this->_application->getUser()->isAnonymous()) {
                    $context->setUnauthorizedError($route['path']);
                    return false;
                }

                $panel = $link = null;
                foreach (array_keys($this->_sortPanels($this->_application->Dashboard_Panels())) as $panel_name) {
                    if (!$_panel = $this->_application->Dashboard_Panels_impl($panel_name, true)) continue;

                    // Get panel links
                    $panel_settings = isset($this->_config['panel_settings'][$panel_name]) ? $this->_config['panel_settings'][$panel_name] : [];
                    $panel_settings += (array)$_panel->dashboardPanelInfo('default_settings');
                    if (!$links = $_panel->dashboardPanelLinks($panel_settings)) continue;

                    $panel = $panel_name;
                    $link = current(array_keys($links));
                    break;
                }
                $context->dashboard_panel = $panel;
                $context->dashboard_panel_link = $link;
                return true;
            case 'user':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if ((!$user_name = $context->getRequest()->asStr('user_name'))
                        || (!$identity = $this->_application->getPlatform()->getUserIdentityFetcher()->fetchByUsername(urldecode($user_name)))
                        || $identity->isAnonymous()
                    ) {
                        $context->setNotFoundError();
                        return false;
                    }

                    if (empty($this->_config['enable_public'])
                        || !$this->_isPublicDashboardAllowed()
                    ) {
                        // Public dashboard not enabled or allowed, so return false if not viewing own profile
                        if ((int)$this->_application->getUser()->id !== (int)$identity->id) {
                            $context->setNotFoundError();
                            return false;
                        }
                    }

                    if ($this->_application->getUser()->isAnonymous()
                        || (int)$this->_application->getUser()->id !== (int)$identity->id
                    ) {
                        $context->dashboard_user = $identity;
                    }
                }
                $panel = $link = null;
                foreach (array_keys($this->_sortPanels($this->_application->Dashboard_Panels())) as $panel_name) {
                    if (!$_panel = $this->_application->Dashboard_Panels_impl($panel_name, true)) continue;

                    // Get panel links
                    $panel_settings = isset($this->_config['panel_settings'][$panel_name]) ? $this->_config['panel_settings'][$panel_name] : [];
                    $panel_settings += (array)$_panel->dashboardPanelInfo('default_settings');
                    if (!$links = $_panel->dashboardPanelLinks($panel_settings, isset($context->dashboard_user) ? $context->dashboard_user : null)) continue;

                    $panel = $panel_name;
                    $link = current(array_keys($links));
                    break;
                }
                $context->dashboard_panel = $panel;
                $context->dashboard_panel_link = $link;
                return true;
            case 'panel':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if ((!$panel_name = $context->getRequest()->asStr('panel_name'))
                        || (!$panel = $this->_application->Dashboard_Panels_impl($panel_name, true))
                    ) {
                        $context->setNotFoundError();
                        return false;
                    }

                    // Get panel links
                    $panel_settings = isset($this->_config['panel_settings'][$panel_name]) ? $this->_config['panel_settings'][$panel_name] : [];
                    $panel_settings += (array)$panel->dashboardPanelInfo('default_settings');
                    if (!$links = $panel->dashboardPanelLinks($panel_settings, isset($context->dashboard_user) ? $context->dashboard_user : null)) {
                        $context->setNotFoundError();
                        return false;
                    }

                    if ((!$link = $context->getRequest()->asStr('link'))
                        || !isset($links[$link])
                    ) {
                        $link = current(array_keys($links));
                    }
                    $context->dashboard_panel = $panel_name;
                    $context->dashboard_panel_link = $link;
                }

                if ($this->_application->getUser()->isAnonymous()
                    && !isset($context->dashboard_user)
                ) {
                    $context->setUnauthorizedError($route['path']);
                    return false;
                }
                return true;
            case 'posts':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    $panel = $this->_application->Dashboard_Panels_impl($context->dashboard_panel);
                    if ((!$panel instanceof Panel\PostsPanel)
                        || (!$bundle = $this->_application->Entity_Bundle($context->dashboard_panel_link))
                    ) {
                        $context->setNotFoundError();
                        return false;
                    }
                    $context->bundle = $bundle;
                }
                return true;
            case 'edit_post':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if ((!$entity_id = $context->getRequest()->asInt('entity_id'))
                        || (!$entity = $this->_application->Entity_Entity($context->bundle->entitytype_name, $entity_id))
                        || $context->bundle->name !== $entity->getBundleName()
                    ) {
                        $context->setNotFoundError();
                        return false;
                    }
                    $context->entity = $entity;
                    return true;
                }
                return $this->_application->Entity_IsRoutable($context->bundle, 'edit', $context->entity);
            case 'delete_post':
                return $this->_application->Entity_IsRoutable($context->bundle, 'delete', $context->entity);
            case 'submit_post':
                if ($accessType === Application::ROUTE_ACCESS_LINK) return true;

                $context->action = 'submit';
                return $this->_application->Entity_IsRoutable($context->bundle, $context->action, $context->entity);
        }
    }

    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'dashboard':
                return $this->getTitle('dashboard');
            case 'user':
            case 'panel':
                return isset($context->dashboard_user) ? $context->dashboard_user->name : $this->getTitle('dashboard');
        }
    }

    public function onCoreResponseSendView($context)
    {
        if (!$context->is_dashboard
            || $context->getRequest()->isAjax()
        ) return;

        // Wrap content with dashboard template
        $context->dashboard_templates = ($templates = $context->getTemplates()) ? array_reverse($templates) : [];
        $context->clearTemplates()->addTemplate($this->_application->getPlatform()->getAssetsDir('directories-frontend') . '/templates/dashboard_dashboard');
        $context->dashboard_id = 'drts-dashboard';
        $context->accordion = !empty($this->_config['accordion']);

        // Show logout button?
        if (!empty($this->_config['logout_btn'])
            && !$context->dashboard_user // make sure this is not another user's dashboard
            && !$this->_application->getUser()->isAnonymous()
        ) {
            $context->logout_btn = '<a href="' . $this->_application->getPlatform()->getLogoutUrl() . '" class="drts-dashboard-logout-button ' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-outline-secondary ' . DRTS_BS_PREFIX . 'btn-block">'
                . '<i class="fa-fw fas fa-sign-out-alt"></i> ' . $this->_application->H(__('Logout', 'directories-frontend')) . '</a>';
        }

        // Get panels
        if (!$panels = $this->getActivePanels(isset($context->dashboard_user) ? $context->dashboard_user : null)) {
            $context->panels = [];
            return;
        }

        // Add classes to current panel and link
        $current_panel_name = $context->dashboard_panel ? $context->dashboard_panel : current(array_keys($panels));
        if (isset($panels[$current_panel_name])) {
            $current_link_name = $context->dashboard_panel_link ? $context->dashboard_panel_link : current(array_keys($panels[$current_panel_name]['links']));
            $panels[$current_panel_name]['active'] = $panels[$current_panel_name]['links'][$current_link_name]['active'] = true;
            $panels[$current_panel_name]['links'][$current_link_name]['attr']['class'] .= ' ' . DRTS_BS_PREFIX . 'active';
        }

        // Remove URL path from first link
        $panel_names = array_keys($panels);
        $link_names = array_keys($panels[$panel_names[0]]['links']);
        $panels[$panel_names[0]]['links'][$link_names[0]]['url'] = '/' . $this->getSlug('dashboard');

        $context->panels = $panels;

        // Load all form field JS/CSS files for dahboard panels
        $this->_application->Form_Scripts();
    }

    public function viewGetModeNames()
    {
        return array('dashboard_dashboard');
    }

    public function viewGetMode($name)
    {
        return new ViewMode\DashboardViewMode($this->_application, $name);
    }

    public function displayGetButtonNames(Entity\Model\Bundle $bundle)
    {
        $ret = [];
        if (empty($bundle->info['is_taxonomy'])
            && empty($bundle->info['is_user'])
        ) {
            $ret[] = 'dashboard_posts_edit';
            $ret[] = 'dashboard_posts_delete';
            if (!empty($bundle->info['public'])) {
                $ret[] = 'dashboard_posts_submit';
            }
        }
        return $ret;
    }

    public function displayGetButton($name)
    {
        return new DisplayButton\PostDisplayButton($this->_application, $name);
    }

    public function submitFrontendAdminSettingsForm($form)
    {
        $this->_application->getComponent('System')->reloadRoutes($this);
    }

    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        // Compat with <v1.3.0
        if (isset($this->_config['panel'])) {
            unset($this->_config['panel']);
            $this->_application->System_Component_saveConfig($this->_name, $this->_config, false);
        }

        $form['tabs'][$this->_name] = [
            '#title' => __('Dashboard', 'directories-frontend'),
            '#weight' => 15,
        ];
        $panel_options = $original_panel_labels = $panel_label_disabled = $panel_settings_form = [];
        foreach ($this->_application->Dashboard_Panels(false) as $panel_name => $panel) {
            $ipanel = $this->_application->Dashboard_Panels_impl($panel_name);
            $original_panel_labels[$panel_name] = $panel_options[$panel_name] = $ipanel->dashboardPanelLabel();
            if (!$panel['labellable']) {
                $panel_label_disabled[] = $panel_name;
            } else {
                if (isset($this->_config['panels']['options'][$panel_name])) {
                    $panel_options[$panel_name] = $this->_config['panels']['options'][$panel_name];
                }
            }
            $panel_settings = isset($this->_config['panel_settings'][$panel_name]) ? $this->_config['panel_settings'][$panel_name] : [];
            if ($_panel_settings_form = $ipanel->dashboardPanelSettingsForm($panel_settings, [$this->_name, 'panel_settings', $panel_name])) {
                $panel_settings_form[$panel_name] = [
                    '#title' => sprintf(__('Dashboard Panel Settings (%s)', 'directories-frontend'), $original_panel_labels[$panel_name]),
                    '#class' => 'drts-form-label-lg',
                    '#states' => [
                        'visible' => [
                            '[name="' . $this->_name . '[panels][default][]"][data-option-value="' . $panel_name . '"]' => ['type' => 'empty', 'value' => false],
                        ],
                    ],
                ] + $_panel_settings_form;
            }
        }
        if (isset($this->_config['panels']['options'])) {
            // Re-order panels as saved previously
            $_panel_options = [];
            foreach (array_keys($this->_config['panels']['options']) as $panel_name) {
                if (!isset($panel_options[$panel_name])) continue;

                $_panel_options[$panel_name] = $panel_options[$panel_name];
                unset($panel_options[$panel_name]);
            }
            $panel_options = $_panel_options + $panel_options;
        }
        $form['fields'][$this->_name] = [
            '#component' => $this->_name,
            '#tab' => $this->_name,
            '#submit' => [
                9 => [ // weight
                    [$this, 'submitFrontendAdminSettingsForm'],
                ],
            ],
            '#title' => __('Dashboard Settings', 'directories-frontend'),
            'panels' => [
                '#title' => __('Dashboard panels', 'directories-frontend'),
                '#type' => 'options',
                '#horizontal' => true,
                '#disable_add' => true,
                '#disable_icon' => true,
                '#disable_add_csv' => true,
                '#multiple' => true,
                '#options_label_disabled' => $panel_label_disabled,
                '#options_value_disabled' => array_keys($original_panel_labels),
                '#default_value' => [
                    'options' => $panel_options,
                    'default' => isset($this->_config['panels']['default']) ? $this->_config['panels']['default'] : array_keys($panel_options),
                ],
                '#options_placeholder' => $original_panel_labels,
                '#weight' => 1,
            ],
            'accordion' => [
                '#type' => 'checkbox',
                '#title' => __('Enable accordion effect', 'directories-frontend'),
                '#horizontal' => true,
                '#default_value' => !empty($this->_config['accordion']),
                '#weight' => 5,
            ],
            'logout_btn' => [
                '#type' => 'checkbox',
                '#title' => __('Show logout button', 'directories-frontend'),
                '#horizontal' => true,
                '#default_value' => !empty($this->_config['logout_btn']),
                '#weight' => 10,
            ],
            'panel_settings' => [
                '#weight' => 20,
            ] + $panel_settings_form,
        ];
        if ($this->_isPublicDashboardAllowed()) {
            $form['fields'][$this->_name]['enable_public'] = [
                '#type' => 'checkbox',
                '#title' => __('Enable public dashboard', 'directories-frontend'),
                '#horizontal' => true,
                '#default_value' => !empty($this->_config['enable_public']),
                '#weight' => 15,
            ];
        } else {
            $form['fields'][$this->_name]['enable_public'] = [
                '#type' => 'hidden',
                '#default_value' => 0,
            ];
        }

        if ($this->_application->getPlatform()->getName() === 'WordPress') {
            // 3rd party plugin profile page integration
            foreach (['BuddyPress', 'UM', 'PeepSo'] as $plugin_class) {
                if (class_exists($plugin_class, false)) {
                    $profile_class = __NAMESPACE__ . '\\WordPress\\' . $plugin_class . 'Profile';
                    $settings = isset($this->_config['wordpress'][$plugin_class]) ? $this->_config['wordpress'][$plugin_class] : [];
                    $parents = [$this->_name, 'wordpress', $plugin_class];
                    $form['fields'][$this->_name]['wordpress'][$plugin_class] = $profile_class::init($this->_application, $settings)->getSettingsForm($parents);
                }
            }
            if (!empty($form['fields'][$this->_name]['wordpress'])) {
                $form['fields'][$this->_name]['wordpress']['#weight'] = 99;
            }
        }
    }

    public function onDirectoryAdminDirectoryAdded($directory, $values)
    {
        $config = $this->_config;
        $config['panels']['posts_' . $directory->name] = null;
        $this->_application->System_Component_saveConfig($this->_name, $config, false);
    }

    public function onViewEntitiesQuerySettingsFilter(&$query, $bundle, $context)
    {
        // Abort if not viewing dashboard or no specific status requested
        if ((string)$context->view !== 'dashboard_dashboard'
            || (!$status = $context->getRequest()->asStr('status'))
        ) return;

        switch ($status) {
            case 'publish':
            case 'pending':
            case 'draft':
            case 'private':
                $query['status'] = [$status];
                if (!empty($query['status_others'])) {
                    if (!in_array($status, $query['status_others'])) {
                        unset($query['status_others']);
                    } else {
                        $query['status_others'] = [$status];
                    }
                }
                break;
            case 'expired':
            case 'deactivated':
            case 'expiring':
                if (!$this->_application->isComponentLoaded('Payment')
                    || empty($bundle->info['payment_enable'])
                ) return;

                switch ($status) {
                    case 'expired':
                        $query['fields']['payment_plan'] = -1;
                        break;
                    case 'deactivated':
                        $query['fields']['payment_plan'] = -2;
                        break;
                    case 'expiring':
                        $query['fields']['payment_plan'] = -3;
                        break;
                }
                break;
            default:
                return;
        }
        $context->url_params['status'] = $status;
    }

    public function dashboardGetPanelNames()
    {
        $ret = ['account'];
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (!$bundle->group
                || !empty($bundle->info['is_taxonomy'])
                || !empty($bundle->info['parent'])
                || !empty($bundle->info['internal'])
                || !empty($bundle->info['is_user'])
            ) continue;

            $ret[] = 'posts_' . $bundle->group;
        }
        foreach ($this->_application->Filter('dashboard_panel_custom_names', []) as $custom_name) {
            $ret[] = 'custom_' . $custom_name;
        }
        return $ret;
    }

    public function dashboardGetPanel($name)
    {
        switch ($name) {
            case 'account':
                return new Panel\AccountPanel($this->_application, $name);
            default:
                if (strpos($name, 'posts_') === 0) {
                    return new Panel\PostsPanel($this->_application, $name);
                } elseif (strpos($name, 'custom_') === 0) {
                    return new Panel\CustomPanel($this->_application, $name);
                }
        }
    }

    public function getPanelUrl($panelName, $linkName = '', $path ='', array $params = [], $ajax = false, AbstractIdentity $identity = null)
    {
        $panel_path = '/' . $this->getSlug('dashboard')
            . '/' . urlencode(isset($identity) && !$identity->isAnonymous() ? $identity->username : $this->_application->getUser()->username)
            . '/' . $panelName;
        return $this->_application->Url(
            $this->_application->Filter('dashboard_panel_path', $panel_path, [$panelName, $ajax]) . $path,
            ['link' => $linkName] + $params,
            '',
            $ajax ? '&' : '&amp;'
        );
    }

    public function getPostsPanelUrl(Entity\Type\IEntity $entity, $path = '', array $params = [], $ajax = false)
    {
        if (!$bundle = $this->_application->Entity_Bundle($entity)) {
            throw new Exception\InvalidArgumentException('Invalid bundle');
        }

        return $this->getPanelUrl('posts_' . $bundle->group, $bundle->name, $path, $params, $ajax);
    }

    public function onWordPressDoShortcodeFilter(&$ret, $shortcode, $component)
    {
        if (strpos($shortcode, 'drts-dashboard') !== 0) return;

        $path = '/' . $this->getSlug('dashboard');
        $url = (string)$this->_application->Url($path);
        $url_requested = (string)Request::url(false);
        if (strpos($url_requested, $url) === 0
            && ($_path = substr($url_requested, strlen($url)))
            && ($_path = trim($_path, '/'))
        ) {
            $ret['path'] = [
                'path' => $path . '/' . $_path, // add dashboard panel path
                'params' => empty($_REQUEST) ? [] : $_REQUEST,
            ];
            // Set user name as title if viewing other user's profile
            if (($_paths = explode('/', $_path))
                && urldecode($_paths[0]) !== $this->_application->getUser()->username
                && ($identity = $this->_application->getPlatform()->getUserIdentityFetcher()->fetchByUsername($_paths[0]))
                && (!$identity->isAnonymous())
            ) {
                $ret['title'] = $identity->name;
            }
        }
    }

    public function getActivePanels(AbstractIdentity $identity = null, $language = null)
    {
        $panels = [];
        $panels_available = $this->_application->Dashboard_Panels();
        $panels_enabled = isset($this->_config['panels']['default']) ? $this->_config['panels']['default'] : array_keys($panels_available);
        if (!isset($language)) {
            $language = $this->_application->getPlatform()->getCurrentLanguage();
        }
        if (!empty($panels_enabled)) {
            foreach ($panels_enabled as $panel_name) {
                if (!isset($panels_available[$panel_name])
                    || (!$panel = $this->_application->Dashboard_Panels_impl($panel_name, true))
                ) continue;

                $panel_settings = isset($this->_config['panel_settings'][$panel_name]) ? $this->_config['panel_settings'][$panel_name] : [];
                $panel_settings += (array)$panel->dashboardPanelInfo('default_settings');
                if (!$links = $panel->panelHtmlLinks($panel_settings, false, true, $identity)) continue;

                $panel->dashboardPanelOnLoad($identity !== null);

                if (!empty($panels_available[$panel_name]['labellable'])
                    && isset($this->_config['panels']['options'][$panel_name])
                ) {
                    $panel_label = $this->_config['panels']['options'][$panel_name];
                    if (isset($language)) {
                        $panel_label = $this->_application->System_TranslateString($panel_label, $panel_name . '_panel_label', 'dashboard_panel', $language);
                    }
                } else {
                    $panel_label = $panel->dashboardPanelLabel();
                }
                $panels[$panel_name] = array(
                    'title' => $panel_label,
                    'links' => $links,
                    'wp_um_icon' => $panel->dashboardPanelInfo('wp_um_icon'), // WP Ultimate Member profile tab icon
                );
            }
        }
        return $panels;
    }

    public function onCorePlatformWordPressInit()
    {
        if ($this->_application->getPlatform()->getName() !== 'WordPress') return;

        // 3rd party plugin profile page integration
        if (!$this->_application->getPlatform()->isAdmin()) {
            foreach (['BuddyPress', 'PeepSo'] as $plugin_class) {
                $this->_initProfileClass($plugin_class);
            }
        }
        // Ultimate Member profile needs to be initialized in both backend/frontend
        $this->_initProfileClass('UM');
    }

    protected function _initProfileClass($pluginClass)
    {
        if (class_exists($pluginClass, false)
            && !empty($this->_config['wordpress'][$pluginClass]['account_show'])
        ) {
            $profile_class = __NAMESPACE__ . '\\WordPress\\' . $pluginClass . 'Profile';
            $settings = [
                '_own_profile_only' => empty($this->_config['enable_public']) || !$this->_isPublicDashboardAllowed()
            ] + $this->_config['wordpress'][$pluginClass];
            $profile_class::init($this->_application, $settings);
        }
    }

    public function onCoreAccessRouteFilter(&$result, Context $context, $route, $paths)
    {
        if (!$result
            || $context->isEmbed()
            || $this->_application->getPlatform()->getName() !== 'WordPress'
            || $this->_application->getPlatform()->isAdmin()
            || Request::isXhr()
            || Request::isPostMethod()
            || $paths[0] !== $this->getSlug('dashboard')
        ) return;

        // Redirect dashboard access to 3rd party plugin profile page?
        foreach (['BuddyPress', 'UM', 'PeepSo'] as $plugin_class) {
            if (class_exists($plugin_class, false)
                && !empty($this->_config['wordpress'][$plugin_class]['account_redirect'])
                && !empty($this->_config['wordpress'][$plugin_class]['account_show'])
            ) {
                $profile_class = __NAMESPACE__ . '\\WordPress\\' . $plugin_class . 'Profile';
                $settings = [
                    '_own_profile_only' => empty($this->_config['enable_public']) || !$this->_isPublicDashboardAllowed()
                ] + $this->_config['wordpress'][$plugin_class];
                $profile_class::init($this->_application, $settings)->redirectDashboardAccess($context, $paths);
                if ($context->isRedirect()) {
                    $result = false;
                    return;
                }
            }
        }
    }

    protected function _isPublicDashboardAllowed()
    {
        return $this->_application->isComponentLoaded('User')
            && $this->_application->Entity_Bundle('users_usr_usr') ? false : true;
    }

    public function onCoreUserLinkAttrFilter(&$attr, $identity)
    {
        if (false !== $attr['href'] // link is disabled if false
            && !empty($this->_config['enable_public'])
            && !$identity->isAnonymous()
            && $this->_isPublicDashboardAllowed()
        ) {
            $attr['href'] = $this->_application->Url('/' . $this->getSlug('dashboard') . '/' . urlencode($identity->username));
            $attr['target'] = $attr['rel'] = '';
        }
    }

    public function onEntityCreatePostEntitySuccess($bundle, $entity, $values, $extraArgs)
    {
        $this->_maybeClearPostsPanelPostCounts($bundle, $entity);
    }

    public function onEntityUpdatePostEntitySuccess($bundle, $entity, $oldEntity, $values, $extraArgs)
    {
        $this->_maybeClearPostsPanelPostCounts($bundle, $entity, $oldEntity);
    }

    protected function _maybeClearPostsPanelPostCounts($bundle, $entity, $oldEntity = null)
    {
        if (!$entity->getAuthorId()
            || (isset($oldEntity) && $entity->getStatus() === $oldEntity->getStatus())
        ) return;

        // Clear dashboard posts panel post counts
        $this->_application->getPlatform()->deleteCache('dashboard_post_counts_' . $bundle->group . '_' . $entity->getAuthorId() . '_', 'content');
    }

    public function onEntityUpdateEntity($bundle, $entity, &$values, $extraArgs)
    {
        if (!empty($extraArgs['dashboard_edit_post'])
            && !empty($values)
            && $entity->isPublished()
            && !$this->_application->HasPermission('entity_publish_' . $bundle->name)
            && $this->_application->Filter('dashboard_edit_post_pending', false, [$bundle, $entity, $values, $extraArgs])
        ) {
            $values['status'] = $this->_application->Entity_Status($entity->getType(), 'pending');
        }
    }
}
