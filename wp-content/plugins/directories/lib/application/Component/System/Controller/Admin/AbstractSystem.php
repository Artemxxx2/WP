<?php
namespace SabaiApps\Directories\Component\System\Controller\Admin;

use SabaiApps\Directories\Context;

abstract class AbstractSystem extends AbstractSettings
{
    protected $_submitable = false;
    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        $form = array(
            '#tabs' => array(
                'info' => array(
                    '#title' => __('System Info', 'directories'),
                    '#weight' => 10,
                ),
                'tools' => array(
                    '#title' => __('Tools', 'directories'),
                    '#weight' => 20,
                ),
                'logs' => array(
                    '#title' => __('Logs', 'directories'),
                    '#weight' => 90,
                ),
            ),
            '#tab_style' => 'pill_less_margin',
            'info' => array(
                '#tab' => 'info',
                'components' => array('#weight' => 10) + $this->_getComponents($context),
            ) + $this->_getSystemInfo($context),
            'tools' => array(
                '#tab' => 'tools',
            ) + $this->_getTools($context),
            'logs' => array(
                '#tab' => 'logs',
            ) + $this->_getLogs($context),
        );
        $form = $this->Filter('system_admin_system_form', $form);
        if (count($form['#tabs']) <= 1) {
            $form['#tabs'] = [];
        } else {
            // Show tab requested if any
            if ($tab = $context->getRequest()->asStr('tab', null, array_keys($form['#tabs']))) {
                $form['#tabs'][$tab]['#active'] = true;
            }
        }

        return $form;
    }
    
    protected function _getLogs(Context $context)
    {
        if (!$logs = $this->Filter('system_admin_system_logs', [])) return [];
        
        $form = array(
            '#js' => 'function copyToClipboard(id, btn) {
  var $tmp = jQuery("<textarea>"), $btn = jQuery(btn);
  jQuery("body").append($tmp);
  $tmp.val(jQuery(id).find("pre").text()).select();
  document.execCommand("copy");
  $tmp.remove();
  $btn.off("mouseleave").on("mouseleave", function(){$btn.sabaiTooltip("hide");});
}',
        );
        $site_path = $this->_application->getPlatform()->getSitePath();
        foreach ($logs as $key => $log) {
            if (empty($log['file'])) continue;
            
            if (strpos($log['file'], $site_path) === 0) {
                $file = substr($log['file'], strlen($site_path));
            } else {
                $file = $log['file'];
            }
            $label = $log['label'];
            if (!empty($log['days'])) {
                $label = sprintf(_n('%s (cleared every %d day)', '%s (cleared every %d days)', $log['days'], 'directories'), $label, $log['days']);
            }
            $form[$key] = array(
                '#weight' => empty($log['weight']) ? 5 : $log['weight'],
                '#type' => 'tableselect',
                '#class' => 'drts-data-table',
                '#header' => array(
                    'logs' => array(
                        'label' => $this->H($label) . ': <code>' . $file . '</code>',
                        'no_escape' => true,
                    ),
                ),
                '#options' => [],
                '#disabled' => true,
            );
            if (file_exists($log['file'])) {
                $id = 'drts-system-log-' . $key;
                $url = $this->Url('/_drts/system/log', array('log' => $key), '', '&');
                $btn_class = DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-sm ' . DRTS_BS_PREFIX . 'btn-outline-secondary';
                $onclick = 'copyToClipboard(\'#' . $id. '\', this); return false;';
                $html = '<div id="' . $id . '" style="width:100%; max-height:500px; overflow:scroll;" class="' . DRTS_BS_PREFIX . 'mb-2"><pre></pre></div>'
                    . '<button type="button" data-title="' . __('Copied!', 'directories') . '" data-trigger="click" rel="sabaitooltip" class="' . $btn_class . '" onclick="' . $onclick .'">'
                    . '<i class="fas fw fa-copy"></i> ' . __('Copy to clipboard', 'directories') . '</button>';
                $form[$key]['#js_ready'] = '$.get("' . $url . '", function(data) { $("#' . $id . ' > pre").text(data); });';
                $form[$key]['#options'][$key]['logs'] = $html;
            }
        }
        
        return $form;
    }

    protected function _getTools(Context $context)
    {
        if (!$tools = $this->System_Tools()) return [];

        $form = array(
            '#type' => 'tableselect',
            '#class' => 'drts-data-table',
            '#header' => array(
                'name' => array('span' => 0),
                'button' => array('span' => 0),
            ),
            '#options' => [],
            '#disabled' => true,
            '#row_attributes' => array(
                '@all' => array(
                    'name' => array(
                        'style' => 'width:70%;'
                    ),
                )
            ),
        );
        foreach (array_keys($tools) as $tool_name) {
            if (!$tool = $this->System_Tools_impl($tool_name, true)) continue;

            $tool_info = $tool->systemToolInfo();
            $link_params = ['tool' => $tool_name];
            if (!empty($tool_info['redirect'])) {
                $link_params['redirect'] = (string)$context->getRoute();
            }
            $form['#options'][$tool_name] = array(
                'name' =>  '<strong class="drts-system-component-label ' . DRTS_BS_PREFIX . 'mb-1 ' . DRTS_BS_PREFIX . 'd-block">' . $this->H($tool_info['label']) . '</strong>'
                    . '<small class="drts-system-component-package">' . $this->H($tool_info['description']) . '</small>',
                'button' => $this->LinkTo(
                    isset($tool_info['btn_label']) ? $tool_info['btn_label'] : $tool_info['label'],
                    $this->Url('/_drts/system/tool_with_progress', $link_params),
                    [
                        'btn' => true,
                        'container' => 'modal',
                        'modalSize' => 'lg',
                    ],
                    ['class' => 'drts-bs-btn drts-bs-btn-outline-secondary']
                ),
            );
        }
        return $form;
    }
    
    protected function _getSystemInfo(Context $context)
    {
        $info = array(
            'server' => array(
                'weight' => 1,
                'label' => '<i class="fas fa-fw fa-server"></i> ' . $this->H(__('Server environment', 'directories')),
                'label_no_escape' => true,
                'info' => array(
                    'version' => array('name' => 'PHP version', 'value' => phpversion(), 'error' => version_compare(phpversion(), '5.3.0', '<')),
                    'memory_limit' => array('name' => 'PHP memory limit', 'value' => $memory = ini_get('memory_limit'), 'warning' => !strpos($memory, 'G') && intval($memory) < 128),
                    'max_execution_time' => array('name' => 'PHP max execution time', 'value' => ini_get('max_execution_time')),
                    'upload_max_filesize' => array('name' => 'PHP upload max file size', 'value' => ini_get('upload_max_filesize')),
                    'post_max_size' => array('name' => 'PHP POST max size', 'value' => ini_get('post_max_size')),
                    'session_gc_maxlifetime' => array('name' => 'PHP session GC max lifetime', 'value' => ini_get('session.gc_maxlifetime')),
                    'session_cookie_lifetime' => array('name' => 'PHP session cookie lifetime', 'value' => ini_get('session.cookie_lifetime')),
                    'mbstring' => array('name' => 'PHP mbstring extension', 'value' => $on = (function_exists('mb_detect_encoding') ? 'On' : 'Off'), 'error' => !$on),
                    'gd' => array('name' => 'PHP GD support', 'value' => extension_loaded('gd') ? 'On' : 'Off'),
                    'finfo_file' => array('name' => 'PHP fileinfo extension', 'value' => $on = (function_exists('finfo_file') ? 'On' : 'Off'), 'error' => !$on),
                    'openssl_version' => array('name' => 'PHP OpenSSL version', 'value' => OPENSSL_VERSION_TEXT),
                    'self' => array('name' => '$_SERVER[\'PHP_SELF\']', 'value' => isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'N/A'),
                    'request_uri' => array('name' => '$_SERVER[\'REQUEST_URI\']', 'value' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A'),
                    'query_string' => array('name' => '$_SERVER[\'QUERY_STRING\']', 'value' => isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : 'N/A'),
                    'script_name' => array('name' => '$_SERVER[\'SCRIPT_NAME\']', 'value' => isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'N/A'),
                ),
            ),
        );
        $form = [];
        foreach ($this->Filter('system_admin_system_info', $info) as $key => $_info) {
            $form[$key] = array(
                '#weight' => empty($_info['weight']) ? 5 : $_info['weight'],
                '#type' => 'tableselect',
                '#header' => array(
                    'name' => array(
                        'label' => $_info['label'],
                        'span' => 2,
                        'no_escape' => !empty($_info['label_no_escape']),
                    ),
                    'value' => array('span' => 0),
                ),
                '#options' => [],
                '#disabled' => true,
                '#row_attributes' => array(
                    '@all' => array(
                        'name' => array(
                            'style' => 'width:30%;'
                        ),
                    )
                ),
            );
            foreach ($_info['info'] as $_info_key => $_info_value) {
                $form[$key]['#options'][$_info_key] = array(
                    'name' => $this->H($_info_value['name']),
                    'value' => empty($_info_value['no_escape']) ? $this->H($_info_value['value']) : $_info_value['value'],
                );
                if (!empty($_info_value['warning'])) {
                    $form[$key]['#row_attributes'][$_info_key]['@row']['class'] = 'drts-bs-table-warning';
                } elseif (!empty($_info_value['error'])) {
                    $form[$key]['#row_attributes'][$_info_key]['@row']['class'] = 'drts-bs-table-danger';
                }
            }
        }
        
        return $form;
    }
    
    private function _getComponents(Context $context)
    {        
        $form = array(
            'installed' => array(
                '#type' => 'tableselect',
                '#weight' => 2,
                '#header' => array(
                    'name' => array(
                        'label' => '<i class="fas fa-fw fa-plug"></i> ' . $this->H(__('Components', 'directories')),
                        'no_escape' => true,
                        'span' => 3,
                    ),
                    'description' => array(
                        'span' => 0,
                    ),
                ),
                '#multiple' => true,
                '#options' => [],
                '#row_attributes' => array(
                    '@all' => array(
                        'name' => array(
                            'style' => 'width:30%;'
                        ),
                    )
                ),
                '#disabled' => true,
            ),
        );

        $local_components = $this->LocalComponents($this->getPlatform()->isDebugEnabled());
        
        // Fetch components
        $components = $packages = [];
        foreach ($this->getModel('Component', 'System')->fetch(0, 0, 'name', 'ASC') as $component) {
            if (!$this->isComponentLoaded($component->name)) continue;

            try {
                $_component = $this->getComponent($component->name);
            } catch (\Exception $e) {
                $this->logError($e->getMessage());
                continue;
            }
            $info = array(
                'description' => $local_components[$component->name]['description'],
                'url' => null,
                'version' => $component->version,
                'upgradeable' => $_component->isUpgradeable($component->version, $local_components[$component->name]['version']),
                'package' => $package = $_component->getPackage(),
            );
            $packages[$package] = $package;
            if (isset($components[$component->name])) {
                $components[$component->name] += $info;
            } else {
                $components[$component->name] = $info;
            }
        }
        ksort($components);
        
        foreach ($components as $component_name => $component) {
            if ($component['upgradeable']) {
                $form['installed']['#row_attributes'][$component_name]['@row']['class'] = 'drts-bs-table-warning';
            }
            $form['installed']['#row_attributes'][$component_name]['@row']['id'] = 'drts-system-admin-component-' . $component_name;
            $form['installed']['#options'][$component_name] = $this->_getComponentRow($component_name, $component, count($packages) > 1);
        }
        
        return $form;
    }
    
    protected function _getComponentRow($componentName, $component, $showPackage = true)
    {    
        return array(
            'name' => '<strong data-package-name="' . $this->H($component['package']) . '">' . $componentName . '</strong>'
                . ' <small>v' . $this->H($component['version']) . '</small>',
            'description' => $this->H($component['description']),
        );
    }
}