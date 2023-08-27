<?php
namespace SabaiApps\Directories\Component\WordPress\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class ShortcodesHelper
{
    public function help(Application $application)
    {
        if (!$shortcodes = $application->getPlatform()->getCache('wordpress_shortcodes', false)) {
            $shortcodes = [];
            $slugs = $application->System_Slugs(null, false);
            foreach (array_keys($slugs) as $component_name) {
                foreach ($slugs[$component_name] as $slug_name => $slug_info) {
                    if (isset($slug_info['wp_shortcode'])
                        && !is_array($slug_info['wp_shortcode']) // array meaning using an existing shortcode, so no need to register here
                    ) {
                        $shortcodes[$slug_info['wp_shortcode']] = [
                            'component' => $component_name,
                            'slug' => $slug_name,
                            'path' => null,
                        ];
                    }
                }
            }
            $shortcodes = $application->Filter('wordpress_shortcodes', $shortcodes);
            $application->getPlatform()->setCache($shortcodes, 'wordpress_shortcodes', 0);
        }

        return $shortcodes;
    }
    
    public function doShortcode(Application $application, $atts, $content, $tag)
    {
        if (!is_array($atts)) $atts = [];
        $shortcodes = $this->help($application);
        $shortcode = $shortcodes[$tag];
        if (isset($atts['component'])) {
            $component = $atts['component'];
            unset($atts['component']);
        } else {
            $component = $shortcode['component'];
        }
        if (!$component
            || !$application->isComponentLoaded($component)
        ) return;

        $options = [];
        if (isset($atts['title'])) {
            $options['title'] = empty($atts['title']) ? false : $atts['title'];
            unset($atts['title']);
        }
        if (isset($atts['cache'])) {
            $options['cache'] = empty($atts['cache']) ? false : (int)$atts['cache'];
            unset($atts['cache']);
        }
        if (isset($atts['id'])) {
            $options['container'] = empty($atts['id']) ? null : $atts['id'];
            unset($atts['id']);
        }
        try {
            $filtered = $application->Filter(
                'wordpress_do_shortcode',
                ['atts' => (array)$atts] + $shortcode,
                [$tag, $component]
            );
            if (isset($filtered['path'])) {
                $path = $filtered['path'];
            } elseif (isset($filtered['slug'])) {
                $path = '/' . $application->getComponent($component)->getSlug($filtered['slug']);
            } elseif (isset($filtered['content'])) {
                return $filtered['content'];
            } else {
                throw new Exception\RuntimeException('Shortcode [' . $tag . ']: No path, slug, or content in shortcode.');
            }
            foreach (['title', 'class'] as $option) {
                if (isset($filtered[$option])) {
                    $options[$option] = $filtered[$option];
                }
            }
        } catch (Exception\IException $e) {
            $application->logError($e);
            return;
        }

        return $application->getPlatform()->render(
            $path,
            [isset($shortcode['atts_name']) ? $shortcode['atts_name'] : 'settings' => $filtered['atts']],
            $options
        );
    }
}