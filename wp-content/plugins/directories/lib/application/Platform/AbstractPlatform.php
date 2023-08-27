<?php
namespace SabaiApps\Directories\Platform;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\MainRoutingController;
use SabaiApps\Directories\Assets;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\Application\Url;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use SabaiApps\Framework\DB\AbstractDB;
use SabaiApps\Framework\User\AbstractIdentity;

abstract class AbstractPlatform
{
    private static $_application;
    protected $_name, $_db, $_defaultJsLoaded, $_defaultCssLoaded, $_renderCount = 0,
        $_head = [], $_js = [], $_jsIndex = 0, $_css = [],
        // For tracking assets
        $_trackedAssets = [],
        $_hasFontAwesomePro;

    protected function __construct($name)
    {
        $this->_name = $name;
    }

    final public function getName()
    {
        return $this->_name;
    }

    public function getCssRelSize()
    {
        return 'rem';
    }

    /**
     * @return AbstractDB
     */
    final public function getDB()
    {
        if (!isset($this->_db)) {
            $this->_db = $this->_getDB();
        }
        return $this->_db;
    }

    public function getMainUrl($lang = null)
    {
        return $this->getSiteUrl();
    }

    /**
     * @param bool $loadComponents
     * @param bool $reload
     * @return Application
     */
    public function getApplication($loadComponents = true, $reload = false)
    {
        if (!isset(self::$_application)) {
            self::$_application = $this->_createApplication();
        }
        if ($loadComponents) {
            if ($reload) {
                self::$_application->reloadComponents();
            } else {
                self::$_application->loadComponents();
            }
        }

        return self::$_application;
    }

    /**
     * @return Application
     */
    protected function _createApplication()
    {
        // Use Bootstrap library that comes with Directories if not exists
        define('DRTS_BS_PREFIX', $this->hasBootstrapCss() ? '' : 'drts-bs-');

        $app = new Application($this);

        // Set logger
        $logger = new Logger('drts');
        // Set an error_log logger so that errors can be written to wordpress debug.log
        if ($this->isDebugEnabled()) {
            $logger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Logger::ERROR));
        }
        $app->setLogger($logger);

        // Always add trailing slash to URL
        $app->addUrlTrailingSlash(true);

        return $app;
    }

    public function loadDefaultAssets($loadJs = true, $loadCss = true)
    {
        // Make sure not to track core files
        if ($track = $this->_trackedAssets) $this->_trackedAssets = [];

        $type = $this->isAdmin() ? 'admin' : 'main';

        if ($loadJs
            && !$this->_defaultJsLoaded
        ) {
            $this->_loadJqueryJs($type);
            $this->_loadCoreJs($type);
            $this->_defaultJsLoaded = true;
        }
        if ($loadCss
            && !$this->_defaultCssLoaded
        ) {
            $this->_loadCoreCss($type);
            $this->_defaultCssLoaded = true;
        }

        $this->_trackedAssets = $track;

        return $this;
    }

    protected function _hasFontAwesomePro()
    {
        if (!isset($this->_hasFontAwesomePro)) {
            $this->_hasFontAwesomePro = false;
            foreach ($this->getCustomAssetsDir() as $index => $dir) {
                if (file_exists($dir . '/fontawesome.min.css')) {
                    $this->_hasFontAwesomePro = $index;
                    break;
                }
            }
        }
        return $this->_hasFontAwesomePro;
    }

    protected function _loadCoreCss($type)
    {
        // Load FontAwesome CSS
        if (false === $dir_index = $this->_hasFontAwesomePro()) {
            $this->addCssFile('fontawesome.min.css', 'drts-fontawesome')
                ->addCssFile('system-fontawesome.min.css', 'drts-system-fontawesome', 'drts-fontawesome');
        } else {
            $this->addCssFile($this->getCustomAssetsDirUrl($dir_index) . '/fontawesome.min.css', 'drts-fontawesome', null, false)
                ->addCssFile('system-fontawesome-pro.min.css', 'drts-system-fontawesome', 'drts-fontawesome');
        }
        $this->addCssFile('fontawesome-brands.min.css', 'drts-fontawesome-brands');

        // Load Bootstrap CSS
        if ($type === 'admin') {
            $this->addCssFile('bootstrap-' . $type . '.min.css', 'drts-bootstrap')
                ->addCssFile($type . '.min.css', 'drts','drts-bootstrap');
        } else {
            $deps = [];
            if (!$bs_handle = $this->_getBootstrapHandle()) {
                $_suffix = $type;
                if (defined('DRTS_THEME')) {
                    $_suffix .= '-' . DRTS_THEME;
                }
                $this->addCssFile( 'bootstrap-' . $_suffix . '.min.css', 'drts-bootstrap');
                $deps[] = 'drts-bootstrap';
            } else {
                $deps[] = isset($bs_handle['css']) ? $bs_handle['css'] : 'bootstrap';
            }
            $_css_url = $type;
            if (defined('DRTS_THEME')) {
                $_css_url .= '-' . DRTS_THEME;
            }
            $this->addCssFile($_css_url . '.min.css', 'drts', $deps);
        }
        if ($this->isRtl()) {
            $this->addCssFile('bootstrap-rtl.min.css', 'drts-bootstrap-rtl', 'drts-bootstrap')
                ->addCssFile($type . '-rtl.min.css', 'drts-rtl', 'drts');
        }
        // Load plugin CSS
        $packages = $this->getPackages();
        $cache_id = 'core_css_files_' . implode('-', $packages);
        if (!$css_files = $this->getCache($cache_id)) {
            $css_files = ['main' => [], 'admin' => [], 'rtl' => ['main' => [], 'admin' => []]];
            $core_assets_dir = $this->getAssetsDir();
            foreach ($packages as $package) {
                $assets_dir = $this->getAssetsDir($package);
                if ($core_assets_dir === $assets_dir) continue;

                if ($this->_getBootstrapHandle()) {
                    // Use CSS without custom BS prefix if any
                    if (file_exists( $assets_dir . '/css/main-no-bs-prefix.min.css')) {
                        $css_files['main'][$package] = 'main-no-bs-prefix';
                    } else {
                        if (file_exists( $assets_dir . '/css/main.min.css')) {
                            $css_files['main'][$package] = 'main';
                        }
                    }
                    if (file_exists( $assets_dir . '/css/main-rtl-no-bs-prefix.min.css')) {
                        $css_files['rtl']['main'][$package] = 'main-rtl-no-bs-prefix';
                    } else {
                        if (file_exists( $assets_dir . '/css/main-rtl.min.css')) {
                            $css_files['rtl']['main'][$package] = 'main-rtl';
                        }
                    }
                } else {
                    if (file_exists( $assets_dir . '/css/main.min.css')) {
                        $css_files['main'][$package] = 'main';
                    }
                    if (file_exists($assets_dir . '/css/main-rtl.min.css')) {
                        $css_files['rtl']['main'][$package] = 'main-rtl';
                    }
                }
                if (file_exists($assets_dir . '/css/admin.min.css')) {
                    $css_files['admin'][$package] = 'admin';
                }
                if (file_exists($assets_dir . '/css/admin-rtl.min.css')) {
                    $css_files['rtl']['admin'][$package] = 'admin-rtl';
                }
            }
            $this->setCache($css_files, $cache_id);
        }
        foreach (array_keys($css_files[$type]) as $package) {
            $this->addCssFile($css_files[$type][$package] . '.min.css', $package, ['drts'], $package);
        }
        if (!empty($css_files['rtl'][$type])
            && $this->isRtl()
        ) {
            foreach (array_keys($css_files['rtl'][$type]) as $package) {
                $this->addCssFile($css_files['rtl'][$type][$package] . '.min.css', $package . '-rtl', [$package], $package);
            }
        }
        // Load custom CSS if any
        if ($type === 'main') {
            $deps = ['drts'];
            foreach ($this->getCustomAssetsDir() as $index => $custom_dir) {
                if (@file_exists($custom_dir . '/style.css')) {
                    $this->addCssFile($this->getCustomAssetsDirUrl($index) . '/style.css', $handle = 'drts-custom-' . $index, $deps, false);
                    $deps[] = $handle;
                }
            }
            if (@file_exists($this->getVarDir() . '/style.css')) {
                $this->addCssFile($this->getVarDirUrl() . '/style.css', $handle = 'drts-custom-var', $deps, false);
            }
        }
    }

    protected function _loadCoreJs($type)
    {
        if ($type === 'admin'
            || (!$bs_handle = $this->_getBootstrapHandle())
        ) {
            $bootstrap_handle = 'drts-bootstrap';
            $this->addJsFile('popper.min.js', 'drts-popper', null, null, false, true)
                ->addJsFile('bootstrap.min.js', 'drts-bootstrap', ['jquery', 'drts-popper'], null, true);
        } else {
            $bootstrap_handle = isset($bs_handle['js']) ? $bs_handle['js'] : 'bootstrap';
        }
        $this->addJsFile('core.min.js', 'drts', ['jquery', $bootstrap_handle], null, true);
        $init_js = sprintf(
            'if (typeof DRTS === "undefined") var DRTS = {url: "%s", isRTL: %s, domain: "%s", path: "%s", cookieHash: "%s", bsPrefix: "%s", hasFontAwesomePro: %s, params: {token: "%s", contentType: "%s", ajax: "%s"}, bsUseOriginal: %s, timeZone: "%s"};',
            rtrim($this->getSiteUrl(), '/'),
            $this->isRtl() ? 'true' : ' false',
            $this->getCookieDomain(),
            $this->getCookiePath(),
            $this->getCookieHash(),
            DRTS_BS_PREFIX,
            $this->_hasFontAwesomePro() !== false ? 'true' : ' false',
            Request::PARAM_TOKEN,
            Request::PARAM_CONTENT_TYPE,
            Request::PARAM_AJAX,
            $bootstrap_handle !== 'drts-bootstrap' ? 'true' : ' false',
            htmlspecialchars($this->getTimeZone(), ENT_QUOTES, 'UTF-8')
        );
        $this->addJsInline('drts', $init_js, false, 'before')
            ->addJsFile('sweetalert2.all.min.js', 'sweetalert2', null, null, true, true)
            ->addJsFile('autosize.min.js', 'autosize', 'jquery', null, true, true)
            ->addJsFile('jquery.coo_kie.min.js', 'jquery-cookie', 'jquery', null, true, true);
    }

    public function addHead($head, $handle, $index = 10)
    {
        $this->_head[$index][$handle] = $head;
        if ($this->_trackedAssets) {
            foreach (array_keys($this->_trackedAssets) as $track_name) {
                $this->_trackedAssets[$track_name]->addHead($handle, $head, $index);
            }
        }
        return $this;
    }

    public function getHeadHtml($clear = true)
    {
        $html = [];
        if (!empty($this->_head)) {
            ksort($this->_head);
            foreach (array_keys($this->_head) as $i) {
                foreach (array_keys($this->_head[$i]) as $j) {
                    $html[] = $this->_head[$i][$j];
                }
            }
        }
        if ($clear) $this->_head = [];
        return empty($html) ? '' : implode(PHP_EOL, $html);
    }

    public function addJsFile($file, $handle, $dependency = null, $package = null, $inFooter = true, $vendor = false)
    {
        if (empty($file)) {
            $this->_unloadJsFile($handle);
            if ($this->_trackedAssets) {
                foreach (array_keys($this->_trackedAssets) as $track_name) {
                    $this->_trackedAssets[$track_name]->addJsFile($handle, false);
                }
            }
        } else {
            $url = $package !== false ? $this->getAssetsUrl($package, $vendor) . '/js/' . $file : $file;
            $this->_loadJsFile($url, $handle, $dependency, $inFooter);
            if ($this->_trackedAssets) {
                foreach (array_keys($this->_trackedAssets) as $track_name) {
                    $this->_trackedAssets[$track_name]->addJsFile($handle, $file, $dependency, $package, $inFooter, $vendor);
                }
            }
        }
        return $this;
    }

    public function addJs($js, $onDomReady = true, $index = null)
    {
        $i = isset($index) ? $index : ++$this->_jsIndex;
        $this->_js[$onDomReady ? 1 : 0][$i][] = $js;
        if ($this->_trackedAssets) {
            foreach (array_keys($this->_trackedAssets) as $track_name) {
                $this->_trackedAssets[$track_name]->addJs($js, $onDomReady, $index);
            }
        }
        return $this;
    }

    public function addJsInline($dependency, $js, $addDomReady = false, $position = 'after')
    {
        if ($addDomReady) {
            if (Request::isXhr()) {
                $js = 'jQuery(function($) { ' . $js . '});';
            } else {
                $js = 'document.addEventListener("DOMContentLoaded", function(event) { var $ = jQuery; ' . $js . '});';
            }
        }
        $this->_loadJsInline($dependency, $js, $position);
        if ($this->_trackedAssets) {
            foreach (array_keys($this->_trackedAssets) as $track_name) {
                $this->_trackedAssets[$track_name]->addJsInline($dependency, $js);
            }
        }
        return $this;
    }

    public function addCssFile($file, $handle, $dependency = null, $package = null, $media = null, $vendor = false)
    {
        if (empty($file)) {
            $this->_unloadCssFile($handle);
            if ($this->_trackedAssets) {
                foreach (array_keys($this->_trackedAssets) as $track_name) {
                    $this->_trackedAssets[$track_name]->addCssFile($handle, false);
                }
            }
        } else {
            $url = $package !== false ? $this->getAssetsUrl($package, $vendor) . '/css/' . $file : $file;
            $this->_loadCssFile($url, $handle, $dependency, isset($media) ? $media : 'all');
            if ($this->_trackedAssets) {
                foreach (array_keys($this->_trackedAssets) as $track_name) {
                    $this->_trackedAssets[$track_name]->addCssFile($handle, $file, $dependency, $package, $media, $vendor);
                }
            }
        }
        return $this;
    }

    public function addCss($css, $targetHandle = null)
    {
        $this->_css[isset($targetHandle) ? $targetHandle : 'drts'][] = $css;
        if ($this->_trackedAssets) {
            foreach (array_keys($this->_trackedAssets) as $track_name) {
                $this->_trackedAssets[$track_name]->addCss($css, $targetHandle);
            }
        }
        return $this;
    }

    public function getCss($clear = true)
    {
        $css = $this->_css;
        if ($clear) {
            $this->_css = [];
            $this->_cssIndex = 0;
        }
        return $css;
    }

    public function getJsHtml($clear = true, $wrap = true)
    {
        $html = [];
        if (!empty($this->_js[0])) {
            ksort($this->_js[0]);
            foreach (array_keys($this->_js[0]) as $k) {
                foreach (array_keys($this->_js[0][$k]) as $i) {
                    $html[] = $this->_js[0][$k][$i];
                }
            }
        }
        if (!empty($this->_js[1])) {
            ksort($this->_js[1]);
            if (Request::isXhr()) {
                $html[] = 'jQuery(function($) {';
            } else {
                $html[] = 'document.addEventListener("DOMContentLoaded", function(event) { var $ = jQuery;';
            }
            foreach (array_keys($this->_js[1]) as $k) {
                foreach (array_keys($this->_js[1][$k]) as $i) {
                    $html[] = $this->_js[1][$k][$i];
                }
            }
            $html[] = '});';
        }
        if (!empty($html)) {
            $html = implode(PHP_EOL, $html);
            if ($wrap) $html = '<script type="text/javascript">' . $html . '</script>';
        } else {
            $html = '';
        }
        if ($clear) {
            $this->_js = [];
            $this->_jsIndex = 0;
        }
        return $html;
    }

    public function addFlash(array $flash)
    {
        return $this->addJs(sprintf('DRTS.flash(%s);', json_encode($flash)));
    }

    public function loadJqueryUiJs(array $components)
    {
        $this->_loadJqueryUiJs($components);
        if ($this->_trackedAssets) {
            foreach (array_keys($this->_trackedAssets) as $track_name) {
                $this->_trackedAssets[$track_name]->addJqueryUiJs($components);
            }
        }
        return $this;
    }

    public function loadImagesLoadedJs()
    {
        $this->_loadImagesLoadedJs();
        if ($this->_trackedAssets) {
            foreach (array_keys($this->_trackedAssets) as $track_name) {
                $this->_trackedAssets[$track_name]->addImagesLoadedJs();
            }
        }
        return $this;
    }

    protected function _getRenderCacheId($path, array $attributes, array $options)
    {
        $attr = serialize($attributes);
        // Append current entity ID if settings contain _current_
        if (isset($GLOBALS['drts_entity'])
            && strpos($attr, '_current_')
        ) {
            $attr .= $GLOBALS['drts_entity']->getId();
        }
        return 'core_platform_render_' . md5((string)$path . $attr . serialize($options));
    }

    public function trackAssets($bool = true, $name = 'core')
    {
        if ($bool) {
            $this->_trackedAssets[$name] = new Assets();
        } else {
            unset($this->_trackedAssets[$name]);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return Assets
     */
    public function getTrackedAssets($name = 'core')
    {
        return isset($this->_trackedAssets[$name]) ? $this->_trackedAssets[$name]->getAssets() : [];
    }

    public function loadAssets($assets)
    {
        if ($assets instanceof Assets) $assets = $assets->getAssets();
        Assets::load($this, $assets);
    }

    public function render($path, array $attributes = [], array $options = [])
    {
        $options += [
            'cache' => false,
            'container' => null,
            'render_assets' => true,
            'title' => null,
            'wrap' => true,
        ];

        // Init options
        if (!isset($options['container'])) {
            $options['container'] = 'drts-platform-render-' . uniqid() . '-' . ++$this->_renderCount;
        }

        $rendered = $this->_doRender($path, $attributes, $options);
        $content = $rendered['content'];

        // Load assets if needed
        if (!empty($rendered['assets'])) {
            Assets::load($this, $rendered['assets']);
        }
        if ($options['render_assets']) {
            $this->loadDefaultAssets();
            if ($js_html = $this->getJsHtml()) {
                $content .= PHP_EOL . $js_html;
            }
            if ($head_html = $this->getHeadHtml()) {
                $content = $head_html . PHP_EOL . $content;
            }
        }

        if (!strlen($content)) return;

        if (!$options['wrap']) return $content;

        $class = 'drts drts-main';
        if ($this->isRtl()) $class .= ' drts-rtl';
        if (isset($options['class'])) $class .= ' ' . $options['class'];
        return '<div id="' . $options['container'] . '" class="' . $class . '">' . $content . '</div>';
    }

    protected function _doRender($path, array $attributes, array $options)
    {
        // Render and cache
        if ((!$cacheable = !empty($options['cache']))
            || (!$cached = $this->getCache($cache_id = $this->_getRenderCacheId($path, $attributes, $options), 'content'))
        ) {
            $container = $options['container'];
            $this->trackAssets()->addJs('DRTS.init($("#' . $container . '"));', true, -99);
            if ($path instanceof Url) {
                if (!$path->route) {
                    throw new Exception\InvalidArgumentException('URL path may not be empty');
                }
                $params = $path->params;
                $path = $path->route;
            } elseif (is_array($path)) {
                $params = $path['params'];
                $path = $path['path'];
            } else {
                $params = null;
            }
            // Create context
            $context = (new Context())->setContainer('#' . $container)
                ->setRequest(new Request(true, true, $params))
                ->setAttributes($attributes);
            if (!empty($options['title'])) {
                $context->setTitle($options['title']);
            }
            if (!empty($options['content_type'])) {
                $context->setContentType($options['content_type']);
            }
            try {
                // Run Sabai
                $response = $this->getApplication()->setCurrentScriptName('main')->run(new MainRoutingController(), $context, $path);

                // Cacheable if response is view
                $cacheable = $context->isView();

                // Render output
                if ($context->getContentType() === 'html') {
                    ob_start();
                    $response->send($context);
                    $content = ob_get_clean();
                    if (false !== $options['title'] // title disabled explicitly if false
                        && ($title = $context->getTitle(false))
                    ) {
                        $content = '<h2>' . $title . '</h2>' . PHP_EOL . $content;
                    }
                } else {
                    $content = $response->send($context);
                }
            } catch (\Exception $e) {
                $cacheable = false;
                $this->getApplication()->logError($e);
                if ($this->isAdministrator()
                    || $this->isDebugEnabled()
                ) {
                    $content = sprintf(
                        '<p>%s</p><p><pre>%s</pre></p>',
                        htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8')
                    );
                } else {
                    $content = sprintf('<p>%s</p>', 'An error occurred while processing the request. Please contact the administrator of the website for further information.');
                }
            }
            $cached = [
                'content' => $content,
                'assets' => $this->getTrackedAssets(),
            ];
            $this->trackAssets(false);

            if (!empty($options['cache'])
                && $cacheable
                && !$this->isAdmin() // WordPress shortcodes may run on the admin side
            ) {
                if (!isset($cache_id)) $cache_id = $this->_getRenderCacheId($path, $attributes, $options);
                $this->setCache($cached, $cache_id, is_numeric($options['cache']) && $options['cache'] > 1 ? $options['cache'] : 86400, 'content');
            }

            // Assets already loaded by _render(), so no need to load them again
            unset($cached['assets']);
        }

        return $cached;
    }

    public function getRouteParam()
    {
        return 'q';
    }

    public function uninstall($removeData)
    {
        // Clear options and cache
        if ($removeData) {
            $this->clearOptions();
        }
        $this->clearCache();
    }

    public function numberFormat($number, $decimals = 0)
    {
        return number_format($number, $decimals);
    }

    public function getAdministrators()
    {
        $ret = [];
        foreach (array_keys($this->getAdministratorRoles()) as $role_name) {
            $ret += $this->getUsersByRole($role_name);
        }

        return $ret;
    }

    abstract protected function _loadJqueryJs($type);
    abstract protected function _loadJsFile($url, $handle, $dependency, $inFooter);
    abstract protected function _unloadJsFile($handle);
    abstract protected function _loadJsInline($dependency, $js, $position);
    abstract protected function _loadCssFile($url, $handle, $dependency, $media);
    abstract protected function _unloadCssFile($handle);
    abstract protected function _loadJqueryUiJs(array $components);
    abstract protected function _loadImagesLoadedJs();
    abstract protected function _getDB();
    abstract public function getPageParam();
    abstract public function hasBootstrapCss();
    /**
     * @return \SabaiApps\Framework\User\AbstractIdentityFetcher
     */
    abstract public function getUserIdentityFetcher();
    abstract public function getCurrentUser();
    abstract public function setCurrentUser($userId);
    abstract public function isAdministrator($userId = null);
    abstract public function getAdministratorRoles();
    abstract public function getUserRoles();
    abstract public function getUsersByRole($roleName);
    abstract public function getPermissions($userId);
    abstract public function hasPermission($userId, $permission);
    abstract public function guestHasPermission($permission);
    abstract public function getLogDir();
    abstract public function getVarDir();
    abstract public function getVarDirUrl();
    abstract public function getSitePath();
    abstract public function getPackagePath();
    abstract public function getPackageVersion($package);
    abstract public function getPackages();
    abstract public function getSiteName();
    abstract public function getSiteVersion();
    abstract public function getSiteEmail();
    abstract public function getSiteUrl();
    abstract public function getSiteAdminUrl();
    abstract public function getAssetsUrl($package = null, $vendor = false);
    abstract public function getAssetsDir($package = null, $vendor = false);
    abstract public function getLoginUrl($redirect = '');
    abstract public function getLogoutUrl();
    abstract public function getRegisterUrl($redirect = '');
    abstract public function isLoginFormRequired();
    abstract public function isRegisterFormRequired();
    abstract public function isUserRegisterable();
    abstract public function registerUser($username, $email, $password, array $values);
    abstract public function loginUser($username, $password, $remember, array $values);
    abstract public function logoutUser();
    abstract public function getResetPasswordKey(AbstractIdentity $identity);
    abstract public function checkResetPasswordKey($key, AbstractIdentity $identity);
    abstract public function resetPassword($password, $key, AbstractIdentity $identity);
    abstract public function isCurrentPassword($password, AbstractIdentity $identity);
    abstract public function changePassword($password, AbstractIdentity $identity);
    abstract public function deleteAccount(AbstractIdentity $identity);
    abstract public function getPrivacyPolicyLink();
    abstract public function mail($to, $subject, $body, array $options = []);
    abstract public function setSessionVar($name, $value);
    abstract public function getSessionVar($name);
    abstract public function deleteSessionVar($name);
    abstract public function setEntityMeta($entityType, $entityId, $name, $value);
    abstract public function getEntityMeta($entityType, $entityId, $name);
    abstract public function hasEntityMeta($entityType, $entityId, $name);
    abstract public function deleteEntityMeta($entityType, $entityId, $name);
    abstract public function getUsersByMeta($name, $value, $limit = 20, $offset = 0, $order = 'DESC', $numeric = true, $compare = null);
    abstract public function setCache($data, $id, $lifetime = null, $group = 'settings');
    abstract public function getCache($id, $group = 'settings');
    abstract public function deleteCache($id, $group = 'settings');
    abstract public function clearCache($group = null);
    abstract public function getLocale();
    abstract public function isRtl();
    abstract public function setOption($name, $value, $autoload = true);
    abstract public function getOption($name, $default = null);
    abstract public function deleteOption($name);
    abstract public function clearOptions();
    abstract public function getCustomAssetsDir($useCache = true);
    abstract public function getCustomAssetsDirUrl($index);
    abstract public function getUserProfileHtml($userId);
    abstract public function getSiteToSystemTime($timestamp);
    abstract public function getSystemToSiteTime($timestamp);
    abstract public function unzip($from, $to);
    abstract public function updateDatabase($schema, $previousSchema = null);
    abstract public function isAdmin();
    abstract public function getCookieDomain();
    abstract public function getCookiePath();
    abstract public function getCookieHash();
    abstract public function htmlize($text, $inlineTagsOnly = false, $forCaching = false);
    abstract public function getStartOfWeek();
    abstract public function getDateFormat();
    abstract public function getTimeFormat();
    abstract public function getDate($format, $timestamp);
    abstract public function getTimeZone();
    abstract public function registerString($str, $name, $domain = 'directories');
    abstract public function unregisterString($name, $domain = 'directories');
    abstract public function translateString($str, $name, $domain = 'directories', $lang = null);
    abstract public function getLanguages();
    abstract public function getDefaultLanguage();
    abstract public function getCurrentLanguage();
    abstract public function isTranslatable($entityType, $bundleName);
    abstract public function getTranslatedId($entityType, $bundleName, $id, $lang);
    abstract public function setTranslations($entityType, $bundleName, $sourceLang, $entityId, $transLang, $transEntityId);
    abstract public function getLanguageFor($entityType, $bundleName, $id);
    abstract public function isAdminAddTranslation(array $reqParams);
    abstract public function isDebugEnabled();
    abstract public function isAmpEnabled($bundleName);
    abstract public function isAmp();
    abstract public function hasSlug($component, $slug, $lang = null);
    abstract public function getSlug($component, $slug, $lang = null);
    abstract public function getTitle($component, $name, $lang = null);
    abstract public function remoteGet($url, array $args = []);
    abstract public function remotePost($url, array $params = [], array $args = []);
    abstract public function anonymizeEmail($email);
    abstract public function anonymizeUrl($url);
    abstract public function anonymizeIp($ip);
    abstract public function anonymizeText($text);
    abstract public function downloadUrl($url, $save = false, $title = null, $ext = null);
    abstract public function uploadFile($path, $name, $title);
    abstract public function encodeString($str, $schemaType, $columnName);
}
