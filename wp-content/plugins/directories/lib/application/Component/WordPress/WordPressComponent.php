<?php
namespace SabaiApps\Directories\Component\WordPress;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\System;

class WordPressComponent extends AbstractComponent implements
    Form\IFields,
    System\IMainRouter
{
    const VERSION = '1.3.108', PACKAGE = 'directories';

    public static function description()
    {
        return 'Provides integration between WordPress and SabaiApps applications.';
    }

    public function onSystemAdminSystemInfoFilter(&$info)
    {
        $platform = $this->_application->getPlatform();
        $info['wordpress'] = [
            'label' => '<i class="fab fa-fw fa-wordpress"></i> ' . $this->_application->H(__('WordPress environment', 'directories')),
            'label_no_escape' => true,
            'weight' => 2,
            'info' => [
                'version' => ['name' => 'Version', 'value' => $GLOBALS['wp_version']],
                'site_url' => ['name' => 'Site URL', 'value' => site_url()],
                'main_url' => ['name' => 'Main URL', 'value' => $platform->getMainUrl()],
                'admin_url' => ['name' => 'Admin URL', 'value' => $platform->getSiteAdminUrl()],
                'abspath' => ['name' => 'Path (ABSPATH)', 'value' => ABSPATH],
                'plugin_path' => ['name' => 'Plugins directory', 'value' => $this->_application->getPackagePath()],
                'writeable_dir' => ['name' => 'Writeable directory', 'value' => $dir = $platform->getVarDir(), 'error' => !is_writeable($dir)],
                'lang_dir' => ['name' => 'Languages directory (WP_LANG_DIR)', 'value' => WP_LANG_DIR],
                'locale' => ['name' => 'Locale', 'value' => get_locale()],
                'debug' => ['name' => 'Debug mode (WP_DEBUG)', 'value' => defined('WP_DEBUG') && WP_DEBUG ? 'On' : 'Off'],
                'debug_display' => ['name' => 'Debug display (WP_DEBUG_DISPLAY)', 'value' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'On' : 'Off'],
                'debug_log' => ['name' => 'Debug log (WP_DEBUG_LOG)', 'value' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'On' : 'Off'],
                'disable_fatal_error_handler' => ['name' => 'WP_DISABLE_FATAL_ERROR_HANDLER', 'value' => defined('WP_DISABLE_FATAL_ERROR_HANDLER') && WP_DISABLE_FATAL_ERROR_HANDLER ? 'On' : 'Off'],
                'memory_limit' => ['name' => 'WP memory limit (WP_MEMORY_LIMIT)', 'value' => defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : ''],
                'theme' => ['name' => 'Theme', 'value' => wp_get_theme(get_template())->get('Name')],
            ],
        ];
        if ($rewrite_rules = $platform->getAllRewriteRules()) {
            $info['wordpress']['info']['rewrite_rules'] = [
                'name' => 'Rewrite rules',
                'no_escape' => true,
            ];
            foreach ($rewrite_rules as $regex => $rewrite) {
                $rewrite_rules[$regex] = '<li class="' . DRTS_BS_PREFIX . 'mb-2"><code>' . $regex . '</code>  <i class="fas fa-fw fa-long-arrow-alt-right"></i>  ' . $rewrite . '</li>';
            }
            $info['wordpress']['info']['rewrite_rules']['value'] = '<div style="max-height:300px; overflow:scroll;"><ul>' . implode(PHP_EOL, $rewrite_rules) . '</ul></div>';
        }
        $slugs = $this->_application->System_Slugs(null, 'Directory');
        $page_slugs = $this->_application->getPlatform()->getPageSlugs();
        foreach (array_keys($slugs) as $component) {
            foreach (array_keys($slugs[$component]) as $slug_name) {
                if (!empty($slugs[$component][$slug_name]['parent'])
                    || (!$real_slug = @$page_slugs[1][$component][$slug_name])
                ) continue;

                $error = false;
                if ((!$page_id = @$page_slugs[2][$real_slug])
                    || (!$permalink = get_permalink($page_id))
                ) {
                    if (isset($slugs[$component][$slug_name]['required'])
                        && !$slugs[$component][$slug_name]['required']
                    ) continue;

                    $error = true;
                }

                $info['wordpress']['info']['page_' . $slug_name] = [
                    'name' => 'Page - ' . $slugs[$component][$slug_name]['admin_title'],
                    'value' => $error ? '' : '<a href="' . $permalink . '">' . $this->_application->H(get_the_title($page_id)) . '</a>',
                    'no_escape' => !$error,
                    'error' => $error,
                ];
            }
        }
        if (!empty($_GET['advanced'])) {
            $info['wordpress']['info']['system_slugs'] = [
                'name' => 'System slugs',
                'no_escape' => true,
                'value' => '<pre>' . $this->_application->H(print_r($this->_application->System_Slugs(), true)) . '</pre>',
            ];
            $info['wordpress']['info']['page_slugs'] = [
                'name' => 'Page slugs',
                'no_escape' => true,
                'value' => '<pre>' . $this->_application->H(print_r($this->_application->getPlatform()->getPageSlugs(false), true)) . '</pre>',
            ];
            foreach ($this->_application->getPlatform()->getLanguages() as $lang) {
                $info['wordpress']['info']['page_slugs_' . $lang] = [
                    'name' => 'Page slugs (' . $lang . ')' . ($this->_application->getPlatform()->getCurrentLanguage() === $lang ? ' - Current' : ''),
                    'no_escape' => true,
                    'value' => '<pre>' . $this->_application->H(print_r($this->_application->getPlatform()->getPageSlugs($lang), true)) . '</pre>',
                ];
            }
        }
    }

    public function onSystemISlugsUpgradeSuccess(AbstractComponent $component)
    {
        $this->_createSlugPages($component);
    }

    public function onSystemISlugsInstallSuccess(AbstractComponent $component)
    {
        $this->_createSlugPages($component);
    }

    public function onSystemISlugsUninstallSuccess(AbstractComponent $component)
    {
        $this->_deleteSlugPages($component);
    }

    protected function _createSlugPages($component)
    {
        $platform = $this->_application->getPlatform();
        if ($languages = $platform->getLanguages()) {
            foreach ($languages as $lang) {
                $this->_doCreateSlugPages($component, $lang);
            }
        } else {
            $this->_doCreateSlugPages($component);
        }
    }

    protected function _doCreateSlugPages($component, $lang = false)
    {
        $page_slugs = $this->_application->getPlatform()->getPageSlugs($lang);
        $component_name = $component->getName();
        $slugs = (array)$this->_application->System_Slugs($component_name, null, false);
        foreach ($slugs as $slug_name => $slug) {
            if (isset($page_slugs[1][$component_name][$slug_name])) continue; // already exists, do not overwrite

            if (empty($slug['parent'])) {
                if (!$slug['title']) continue;

                if ($page_id = $this->_application->getPlatform()->createPage($slug['slug'], $slug['title'], $lang)) {
                    $page_slugs[0][$slug['slug']] = $slug['slug'];
                    $page_slugs[1][$component_name][$slug_name] = $slug['slug'];
                    $page_slugs[2][$slug['slug']] = $page_id;
                }
            } else {
                if (!isset($page_slugs[1][$component_name][$slug['parent']]) // no valid parent
                    || empty($slug['bundle_type'])
                    || (!$bundle = $this->_application->Entity_Bundle($slug['bundle_type'], $component_name, isset($slug['bundle_group']) ? $slug['bundle_group'] : ''))
                ) continue;

                // Save post type and slug
                $_slug = $page_slugs[1][$component_name][$slug['parent']] . '/' . $slug['slug'];
                call_user_func_array(
                    [$this->_application, 'WordPress_PageSettingsForm_saveSingle'],
                    [$bundle, $slug_name, $_slug, &$page_slugs]
                );
            }
        }
        // Remove slugs that no longer exist
        foreach ($page_slugs[1][$component_name] as $slug_name => $slug) {
            if (!isset($slugs[$slug_name])) {
                // Slug was removed
                unset($page_slugs[1][$component_name][$slug_name], $page_slugs[0][$slug], $page_slugs[2][$slug], $page_slugs[5][$slug]);
            }
        }

        $this->_application->getPlatform()->setPageSlugs($page_slugs, $lang);
    }

    protected function _deleteSlugPages($component)
    {
        $platform = $this->_application->getPlatform();
        if ($languages = $platform->getLanguages()) {
            foreach ($languages as $lang) {
                $this->_doDeleteSlugPages($component, $lang);
            }
        } else {
            $this->_doDeleteSlugPages($component);
        }
    }

    protected function _doDeleteSlugPages($component, $lang = false)
    {
        $page_slugs = $this->_application->getPlatform()->getPageSlugs($lang);
        if (!empty($page_slugs[0]) && !empty($page_slugs[1][$component->getName()])) {
            // Remove slugs and ids of the uninstalled plugin from the global slug list
            $component_slugs = array_flip(array_values($page_slugs[1][$component->getName()])); // slugs as key
            $page_slugs[0] = array_diff_key($page_slugs[0], $component_slugs); // remove from slugs by slug list
            $page_slugs[2] = array_diff_key($page_slugs[2], $component_slugs); // remove from page ids by slug list
            $page_slugs[5] = array_diff_key($page_slugs[5], $component_slugs); // remove from post_type/taxonomy info by slug list
            unset($page_slugs[1][$component->getName()]); // unset slugs, page titles by component
            foreach ($this->_application->Entity_Bundles(null, $component->getName()) as $bundle) {
                unset($page_slugs[4][$bundle->name]); // unset custom permalink settings
            }
        }
        $this->_application->getPlatform()->setPageSlugs($page_slugs, $lang);
    }

    public function onSystemComponentUpgraded($componentEntity, $previousVersion)
    {
        if ($componentEntity->name === $this->_name
            && version_compare($previousVersion, self::VERSION, '<')
        ) {
            // re-schedule event
            wp_clear_scheduled_hook('drts_cron');
            if (!wp_next_scheduled('drts_cron')) {
                wp_schedule_event(time(), 'twicedaily', 'drts_cron');
            }
        }
    }

    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        $form['fields'][$this->_name] = [
            '#tab' => 'System',
            '#weight' => 99,
        ] + ['licenses' => $this->_application->WordPress_LicenseKeySettingsForm([$this->_name, 'licenses'])];
    }

    public function formGetFieldTypes()
    {
        return ['wp_editor', 'wp_media_manager', 'wp_upload'];
    }

    public function formGetField($type)
    {
        switch ($type) {
            case 'wp_editor':
                return new FormField\EditorFormField($this->_application, $type);
            case 'wp_media_manager':
                return new FormField\MediaManagerFormField($this->_application, $type);
            case 'wp_upload':
                return new FormField\UploadFormField($this->_application, $type);
        }
    }

    public function systemMainRoutes($lang = null)
    {
        return [
            '/_drts/wp/upload' => [
                'controller' => 'UploadFile',
            ],
        ];
    }

    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route){}

    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route){}

    public function onCorePlatformWordPressInit()
    {
        $application = $this->_application;
        add_action('drts_cron', function () use ($application) {
            $application->System_Cron();
        });

        if (!is_admin()) {
            // Init page specific shortcodes
            if ($shortcodes = $this->_application->WordPress_Shortcodes()) {
                foreach (array_keys($shortcodes) as $shortcode) {
                    add_shortcode($shortcode, [$this->_application, 'WordPress_Shortcodes_doShortcode']);
                }
            }
        }

        // Override lost password URL
        if ($this->_application->isComponentLoaded('FrontendSubmit')
            && ($frontendsubmit_component = $this->_application->getComponent('FrontendSubmit'))
            && method_exists($frontendsubmit_component, 'isLoginFormEnabled')
            && $frontendsubmit_component->isLoginFormEnabled()
            && method_exists($frontendsubmit_component, 'showLostPasswordLink')
            && $frontendsubmit_component->showLostPasswordLink()
        ) {
            add_filter('lostpassword_url', function($url, $redirect) {
                $login_slug = $this->_application->getComponent('FrontendSubmit')->getSlug('login');
                return (string)$this->_application->Url('/' . $login_slug . '/lost_password', ['redirect_to' => $redirect]);
            }, 10, 2);
        }
    }

    public function uninstall($removeData = false)
    {
        wp_clear_scheduled_hook('drts_cron');

        if (!$removeData) return;

        // Delete pages
        if (($page_slugs = $this->_application->getPlatform()->getPageSlugs())
            && !empty($page_slugs[2])
        ) {
            foreach ($page_slugs[2] as $page_id) {
                wp_trash_post($page_id);
            }
        }
    }

    public function onFormScripts($options)
    {
        if (empty($options) || in_array('wordpress_mediamanager', $options)) {
            wp_enqueue_media();
            wp_enqueue_editor();
            $this->_application->getPlatform()
                ->addJsFile('wordpress-mediamanager.min.js', 'drts-wordpress-mediamanager', ['jquery-ui-sortable', 'drts']);
        }
    }

    public function validatePackage($package)
    {
        if (strpos($package, '-') === false) return true;

        if ($this->_application->getPlatform()->getOption($option_name = md5(site_url() . $package), false)) {
            if (($last_ts = $this->_application->getPlatform()->getOption('_' . $option_name))
                && $last_ts > time() - 604800 // last attempt is less than a week old
            ) return true;

            // Clear last time attempt value so that a new attempt can bem made
            $this->_application->getPlatform()->deleteOption('_' . $option_name);
        }

        $license_keys = $this->_application->getPlatform()->getOption('license_keys', []);
        if (!isset($license_keys[$package]['value'])
            || (!$license_key = trim($license_keys[$package]['value']))
            || empty($license_keys[$package]['package'])
        ) return false;

        // Do not attempt if last attempt is less than 10 min old
        if (($last_ts = $this->_application->getPlatform()->getOption('_' . $option_name))
            && $last_ts > time() - 600
        ) return false;

        // Update last attempt timestamp cache
        $this->_application->getPlatform()->setOption('_' . $option_name, time(), false);

        try {
            $info = $this->_application->getPlatform()
                ->getUpdater()
                ->getInfo($package, $license_keys[$package]['type'], $license_key, true);
            if (empty($info['download_link'])) {
                throw new Exception\RuntimeException('Invalid download link');
            }
        } catch (\Exception $e) {
            $this->_application->logError($e);
            if (!file_exists(WP_CONTENT_DIR . '/drts/assets/' . $option_name . '.txt')) {
                return false;
            }
        }

        $this->_application->getPlatform()->setOption($option_name, true, false);
        return true;
    }

    public function onSystemAllRoutesReloaded()
    {
        $this->_application->getPlatform()->flushRewriteRules();
    }
}
