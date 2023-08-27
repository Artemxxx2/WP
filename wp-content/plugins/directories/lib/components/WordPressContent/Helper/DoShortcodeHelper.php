<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;

class DoShortcodeHelper
{
    protected $_mainContent;

    public function help(Application $application, $atts, $content, $tag)
    {
        if (!is_array($atts)) $atts = [];

        switch ($tag) {
            case 'drts-entity':
                if (empty($atts['id'])) {
                    if (!isset($GLOBALS['drts_entity'])) {
                        return '[' . $tag . (empty($atts) ? '' : $application->Attr($atts)) . ']';
                    }

                    $entity = $GLOBALS['drts_entity'];
                    $application->Entity_Field_load($entity);
                } else {
                    if (!$atts['id'] = intval($atts['id'])) return;

                    $type = isset($atts['type']) && in_array($atts['type'], ['term']) ? $atts['type'] : 'post';
                    if (!$entity = $application->Entity_Entity($type, $atts['id'])) return;
                }
                if (!empty($atts['field'])) {
                    if ((!$field = $application->Entity_Field($entity, $atts['field']))
                        || (!$field_type = $application->Field_Type($field->getFieldType(), true))
                    ) {
                        $application->logError('Shortcode [' . $tag . ']: Invalid field "' . $atts['field']) . '" specified using the field parameter.';
                        return;
                    }
                    if (!$field_type instanceof \SabaiApps\Directories\Component\Field\Type\IHumanReadable) {
                        $application->logWarning('Shortcode [' . $tag . ']: Unsupported field type "' . $field->getFieldType() . '" specified using the field parameter.');
                        return;
                    }
                    $separator = isset($atts['separator']) ? $atts['separator'] : null;
                    $key = isset($atts['key']) ? $atts['key'] : null;

                    return $field_type->fieldHumanReadableText($field, $entity, $separator, $key);
                } elseif (!empty($atts['display_element'])) {
                    if ((!$bundle = $application->Entity_Bundle($entity))
                        || (!$display = $application->Display_Display($bundle->name))
                        || (!$element = $application->Display_Display_element($display, $atts['display_element']))
                    ) return;

                    // Remove some unwanted options
                    $element['visibility']['hide_on_parent'] = $element['visibility']['globalize'] = false;
                    $rendered = $application->Display_Render_element($bundle, $element, $entity, 'div', true, true);
                    if ($rendered === null) return;

                    // Add default CSS/JS
                    $id = 'drts-wordpress-display-element-' . $atts['display_element'] . '-' . uniqid();
                    $application->getPlatform()->loadDefaultAssets()
                        ->addJsInline('drts', 'DRTS.init($("#' . $id . '"));', true);
                    if ($js_html = $application->getPlatform()->getJsHtml(true, false)) {
                        $application->getPlatform()->addJsInline('drts', $js_html, false);
                    }
                    $application->Display_Render_css();

                    $show_header = !isset($atts['heading']) || $atts['heading'];
                    return sprintf(
                        '<div id="%s" class="drts%s"><div class="drts-display %s%s" data-display-type="%s" data-display-name="%s">%s</div></div>',
                        $id,
                        $application->getPlatform()->isRtl() ? ' drtsrtl' : '',
                        $display['class'],
                        $show_header ? '' : ' drts-display-no-header',
                        $display['type'],
                        $display['name'],
                        $rendered
                    );
                } elseif (isset($atts['entity_seo'])) {
                    if (!$bundle = $application->Entity_Bundle($entity)) return;

                    if (!empty($bundle->info['entity_schemaorg']['type'])) {
                        return $application->Entity_SchemaOrg_render($entity, $bundle->info['entity_schemaorg']);
                    }
                } else {
                    if (empty($atts['id'])) {
                        if ($application->getPlatform()->isSingleEntityPage()) {
                            if (!isset($this->_mainContent)) {
                                $this->_mainContent = $application->getPlatform()->getMainContent();
                            }
                            return $this->_mainContent;
                        }
                        return '[' . $tag . ']'; // will be replaced by main content through platform
                    }
                }

                $cache = $title = null;
                if (isset($atts['title'])) {
                    $title = empty($atts['title']) ? false : $atts['title'];
                    unset($atts['title']);
                }
                if (isset($atts['cache'])) {
                    $cache = !empty($atts['cache']);
                    unset($atts['cache']);
                }

                return $application->getPlatform()->render(
                    $application->Entity_Path($entity),
                    ['settings' => ['display' => isset($atts['display']) ? $atts['display'] : 'detailed']],
                    [
                        'cache' => $cache,
                        'title' => $title,
                    ]
                );

            default:
        }
    }
}