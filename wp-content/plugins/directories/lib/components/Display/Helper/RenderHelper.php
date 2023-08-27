<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Assets;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class RenderHelper
{
    protected static $_current = [], $_cssLoaded, $_defaults = [
        'attr' => [],
        'pre_render' => false,
        'tag' => 'div',
        'element_tag' => 'div',
        'render_empty' => false,
        'cache' => false,
        'html_as_array' => false,
    ];

    public function help(Application $application, $bundle, $displayName, $var, array $options = [])
    {
        if (!$bundle = $application->Entity_Bundle($bundle)) return'';

        if (is_array($displayName)) {
            $display = $displayName;
        } else {
            if (!$display = $application->Display_Display($bundle->name, $displayName)) {
                // Use default display if requested display does not exist and is a custom display
                if (strpos($displayName, '-')
                    && ($name_parts = explode('-', $displayName))
                    && !empty($name_parts[0])
                ) {
                    if (!$display = $application->Display_Display($bundle->name, $name_parts[0])) {
                        return '';
                    }
                    $application->logWarning(sprintf(
                        'Display %s does not exist, falling back to default %s display.',
                        $displayName,
                        $name_parts[0]
                    ));
                }
            }
        }

        $options += self::$_defaults;

        if ($options['cache'] !== false) {
            $cache_id = $this->_getDisplayCacheId($display, $bundle, $var, $options);
            if (false !== $cached = $application->getPlatform()->getCache($cache_id, 'display_rendered')) {
                if (!empty($cached['assets'])) {
                    Assets::load($application->getPlatform(), $cached['assets']);
                }
                return $cached['display'];
            }
            $application->getPlatform()->trackAssets(true);
        }

        switch ($display['type']) {
            case 'entity':
                if (!$var instanceof IEntity) return [];

                if ($options['pre_render'] && $display['pre_render']) {
                    $_var = ['entities' => [$var->getId() => $var], 'html' => &$display['html']];
                    $this->preRender($application, $display, $bundle, $_var);
                }
                if ($display['name'] === 'detailed') {
                    if (!isset($GLOBALS['drts_display_elements'])) {
                        $GLOBALS['drts_display_elements'] = [];
                    }
                }
                $options['attr'] += [
                    'data-entity-id' => $var->getId(),
                    'class' => $application->Entity_HtmlClass($var),
                    'data-type' => $var->getType(),
                    'data-content-type' => $var->getBundleType(),
                    'data-content-name' => $var->getBundleName(),
                ];
                break;

            case 'form':
            case 'filters':
                if (!$var instanceof Form\Form) return [];
                break;

            default:
                return;
        }

        // Track current display
        array_push(self::$_current, array($bundle->name, $display['name']));

        // HTML
        $html = [];
        foreach (array_keys($display['elements']) as $element_id) {
            if (!$rendered = $this->element(
                $application,
                $bundle,
                $display['elements'][$element_id],
                $var,
                $options['element_tag']
            )) continue;

            $html[$element_id] = $rendered;
        }
        if (empty($html) && !$options['render_empty']) {
            array_pop(self::$_current);
            if (isset($cache_id)) {
                $application->getPlatform()->setCache('', $cache_id, null, 'content');
            }
            return '';
        }

        // Attributes
        $options['attr']['data-display-type'] = $display['type'];
        $options['attr']['data-display-name'] = $display['name'];
        // Add CSS class
        $class = 'drts-display ' . $display['class'];
        if (isset($options['attr']['class'])) {
            $options['attr']['class'] .= ' ' . $class;
        } else {
            $options['attr']['class'] = $class;
        }

        // CSS
        $this->css($application, $display);

        // Let others modify output
        $ret = $application->Filter('display_render', array('html' => $html, 'attr' => $options['attr']), array($display, $bundle, $var, $options));

        // Concatenate and wrap with tags
        if ($options['tag']) {
            $ret = '<' . $options['tag'] . $application->Attr($ret['attr']) . '>' . implode(PHP_EOL, $ret['html']) . '</' . $options['tag'] . '>';
        } else {
            if (empty($options['html_as_array'])) {
                $ret = implode(PHP_EOL, $ret['html']);
            }
        }

        array_pop(self::$_current);

        // Cache?
        if (isset($cache_id)) {
            $assets = $application->getPlatform()->getTrackedAssets();
            $application->getPlatform()->trackAssets(false)
                ->setCache(['display' => $ret, 'assets' => $assets], $cache_id, $options['cache'], 'display_rendered');
        }

        return $ret;
    }

    public function css(Application $application, array $display = null)
    {
        if (!isset(self::$_cssLoaded)) {
            self::$_cssLoaded = [];

            // Load default CSS stylesheets
            $application->getPlatform()->loadDefaultAssets(false);
        }
        if (isset($display)
            && !empty($display['css'])
        ) {
            if (!isset(self::$_cssLoaded[$display['bundle_name']][$display['type']][$display['name']])) {
                self::$_cssLoaded[$display['bundle_name']][$display['type']][$display['name']] = true;

                $application->getPlatform()->addCss($display['css'], 'drts');
            }
        }
    }

    protected function _getDisplayCacheId(array $display, Bundle $bundle, $var, array $options)
    {
        $ret = 'display_rendered_' . $bundle->name . '_' . $display['type'] . '_' . $display['name'];
        if ($display['type'] === 'entity') {
            $ret .= '_' . $var->getId();
        }
        $ret .= '_' . md5(serialize($options));

        return $ret;
    }

    protected function _getElementCacheId(Bundle $bundle, $elementId, $entity = null)
    {
        $ret = 'display_rendered_' . $bundle->name . '_element_' . $elementId;
        if ($entity instanceof IEntity) {
            $ret .= '_' . $entity->getId();
        }

        return $ret;
    }

    public function clearDisplayCache(Application $application)
    {
        $application->getPlatform()->clearCache('display_rendered');
    }

    public function clearElementCache(Application $application)
    {
        $application->getPlatform()->clearCache('display_element_rendered');
    }

    public function element(Application $application, Bundle $bundle, array $element, $var, $tag = 'div', $preRender = false, $track = false)
    {
        // Show in backend only?
        if (!empty($element['visibility']['admin_only'])
            && !$application->getPlatform()->isAdmin()
        ) return;

        if ($var instanceof IEntity) {
            // Hide on parent content page?
            if (!empty($element['visibility']['hide_on_parent'])
                && $var->isOnParentPage()
            ) return;

            if (!empty($element['visibility']['hide_on_mobile'])
                && $application->isMobile()
            ) return;
        }

        // Cached?
        if (!empty($element['advanced']['cache'])) {
            $cache_id = $this->_getElementCacheId($bundle, $element['id'], $var);
            if (false !== ($cached = $application->getPlatform()->getCache($cache_id, 'display_element_rendered'))
                && is_string($cached)
            ) {
                // Allow access from outside?
                if (!empty($element['visibility']['globalize'])) {
                    $GLOBALS['drts_display_elements'][$bundle->name][$element['id']] = $cached;

                    // Remove from display?
                    if (!empty($element['visibility']['globalize_remove'])) return;
                }

                return $cached;
            }
        }
        
        if ($track) array_push(self::$_current, array($bundle->name, $element['display']));

        $rendered = $this->_renderElement($application, $bundle, $element, $var, $tag, $preRender);
        
        if ($track) array_pop(self::$_current);
            
        if (!$rendered) return;

        // Cache?
        if (isset($cache_id)) {
            $application->getPlatform()->setCache($rendered, $cache_id, $element['advanced']['cache'], 'display_element_rendered');
        }

        // Allow access from outside?
        if (!empty($element['visibility']['globalize'])) {
            $GLOBALS['drts_display_elements'][$bundle->name][$element['id']] = $rendered;

            // Remove from display?
            if (!empty($element['visibility']['globalize_remove'])) return;
        }

        return $rendered;
    }

    protected function _renderElement(Application $application, Bundle $bundle, array $element, $var, $tag, $preRender)
    {
        try {
            // Filter whether or not to render element
            $render = $application->Filter(
                'display_element_render',
                true,
                [$bundle, $element, $var]
            );

            if ($render) {
                $element_impl = $application->Display_Elements_impl($bundle, $element['name']);
                if ($preRender
                    && !empty($element['pre_render'])
                ) {
                    $_var = $var instanceof IEntity ? ['entities' => [$var->getId() => $var]] : $var;
                    $element_impl->displayElementPreRender($bundle, $element, $_var);
                }
                $rendered = $element_impl->displayElementRender($bundle, $element, $var);
            } else {
                $rendered = '';
            }
        } catch (Exception\IException $e) {
            $application->logError($e);
            return;
        }

        // Init HTML and add more attributes if any
        if (is_array($rendered)) {
            if (!empty($rendered['raw'])) {
                return $rendered['raw'];
            }
            $attr = $this->_getElementAtts($element, $var);
            if (isset($rendered['style'])) {
                $attr['style'] .= $rendered['style'];
            }
            if (isset($rendered['attr'])) {
                $attr += $rendered['attr'];
            }
            if (isset($rendered['class'])) {
                $attr['class'] .= ' ' . $rendered['class'];
            }
            if (isset($rendered['id'])) {
                $attr['id'] = $rendered['id'];
            }
            $html = $rendered['html'];
            $force_output = !empty($rendered['force_output']);
        } else {
            $attr = $this->_getElementAtts($element, $var);
            $html = (string)$rendered;
            $force_output = false;
        }

        // Filter rendered element
        $output = $application->Filter(
            'display_element_rendered',
            ['html' => $html, 'attr' => $attr, 'force_output' => $force_output],
            [$bundle, $element, $var]
        );

        // Do not show if no content to display (if not forced) and not a table column
        if ((!strlen($output['html']) && empty($output['force_output']))
            && $tag !== 'td'
        ) return;

        // Add heading?
        $heading = '';
        if (isset($element['heading']['label'])) {
            $heading = $application->Display_ElementLabelSettingsForm_label(
                $element['heading'],
                $application->Display_Elements_impl($bundle, $element['name'])->displayElementStringId('heading', $element['_element_id'])
            );
            if (strlen($heading)) {
                if ($var instanceof IEntity) {
                    $heading = $application->Entity_Tokens_replace($heading, $var, true);
                }
                $heading = '<div class="drts-display-element-header"><span>' . $heading . '</span></div>';
            } else {
                $output['attr']['class'] .= ' drts-display-element-no-header';
            }
        }

        // Add labels/buttons?
        if ($var instanceof IEntity
            && strpos($element['display'], 'summary') === 0
        ) {
            if (!empty($element['settings']['_labels']['enable'])
                && $application->Display_Elements_impl($bundle, $element['name'])->displayElementInfo($bundle, 'labellable')
                && ($labels_html = $application->Display_Labels_renderLabels($bundle, $element['settings']['_labels'], $element['settings']['labels'], $var))
            ) {
                $output['html'] .= '<div class="drts-display-element-labels" data-position="' . $element['settings']['_labels']['position'] . '">' . $labels_html . '</div>';
            }
            if (!empty($element['settings']['_buttons']['enable'])
                && $application->Display_Elements_impl($bundle, $element['name'])->displayElementInfo($bundle, 'buttonable')
                && ($buttons_html = $application->Display_Buttons_renderButtons($bundle, $element['settings']['_buttons'], $element['settings']['buttons'], $element['display'], $var))
            ) {
                $output['html'] .= '<div class="drts-display-element-buttons" data-position="' . $element['settings']['_buttons']['position'] . '" data-hover="' . (int)$element['settings']['_buttons']['hover'] . '">' . $buttons_html . '</div>';
            }
        }

        return '<' . $tag . ' data-name="' . $element['name'] . '"' . $application->Attr($output['attr']) . '>' . $heading . $output['html'] . '</' . $tag . '>';
    }

    protected function _getElementAtts(array $element, $var)
    {
        // Init attributes
        $attr = [
            'class' => strtr($element['class'], ['%bs_prefix%' => DRTS_BS_PREFIX]),
            'style' => isset($element['css']['style']) ? $element['css']['style'] : '',
        ];
        // Set CSS ID
        if (isset($element['css']['id'])
            && $element['css']['id'] !== ''
        ) {
            $attr['id'] = $element['css']['id'];
            if ($var instanceof IEntity
                && strpos($attr['id'], '%id%') !== false
            ) {
                $attr['id'] = str_replace('%id%', $var->getId(), $attr['id']);
            }
        }

        return $attr;
    }

    public function preRender(Application $application, array $display, Bundle $bundle, &$var)
    {
        foreach ($display['elements'] as $element) {
            if (empty($element['pre_render'])
                || (!$element_impl = $application->Display_Elements_impl($bundle, $element['name'], true))
            ) continue;

            // Skip if cached
            if (!empty($element['advanced']['cache'])
                && $display['type'] === 'entity'
            ) {
                $_var = $var;
                foreach (array_keys($var['entities']) as $entity_id) {
                    if (false !== $application->getPlatform()->getCache($this->_getElementCacheId($bundle, $element['id'], $var['entities'][$entity_id]), 'display_element_rendered')) {
                        unset($_var['entities'][$entity_id]);
                    }
                }
                if (!empty($_var['entities'])) {
                    $element_impl->displayElementPreRender($bundle, $element, $_var);
                }
            } else {
                $element_impl->displayElementPreRender($bundle, $element, $var);
            }
        }
    }

    public static function isRendering($bundleName = null, $displayName = null)
    {
        if (empty(self::$_current)) return false;

        if (isset($bundleName)) {
            $current = end(self::$_current);
            if ($bundleName !== $current[0]) return false;

            if (isset($displayName)) {
                if ($displayName !== $current[1]) return false;
            }
        }
        return true;
    }
}
