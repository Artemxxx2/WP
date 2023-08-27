<?php
namespace SabaiApps\Directories\Component\WordPressContent\DisplayStatistic;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class PostViewsDisplayStatistic extends Display\Statistic\AbstractStatistic
{   
    protected function _displayStatisticInfo(Entity\Model\Bundle $bundle)
    {
        return [
            'label' => __('Post view count', 'directories'),
            'default_settings' => [
                '_icon' =>  'fas fa-eye',
                'source' => null,
            ],
        ];
    }

    public function displayStatisticSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [], $type = 'icon')
    {
        return [
            'source' => [
                '#type' => 'select',
                '#title' => __('Statistics source', 'directories'),
                '#options' => self::getSources(),
                '#horizontal' => true,
                '#default_value' => $settings['source'],
            ],
        ];
    }

    public static function getSources()
    {
        $sources = [];
        if (defined('KOKO_ANALYTICS_VERSION')) { // Koko Analytics
            $sources['koko-analytics'] = 'Koko Analytics';
        }
        if (function_exists('wp_statistics_pages')) { // WP Statistics
            $sources['wp-statistics'] = 'WP Statistics';
        }
        if (function_exists('wpp_get_views')) { // WordPress Popular Posts
            $sources['wordpress-popular-posts'] = 'WordPress Popular Posts';
        }
        if (function_exists('pvc_get_post_views')) { // Post Views Counter
            $sources['post-views-counter'] = 'Post Views Counter';
        }
        return $sources;
    }

    public function displayStatisticRender(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (empty($settings['source'])) {
            if (!$sources = self::getSources()) return;

            $source = array_keys($sources)[0];
        } else {
            $source = $settings['source'];
        }
        $post_id = (int)$entity->getId();
        switch ($source) {
            case 'koko-analytics':
                if (defined('KOKO_ANALYTICS_VERSION')) {
                    global $wpdb;
                    $count = $wpdb->get_var('SELECT SUM(pageviews) FROM ' . $wpdb->prefix . 'koko_analytics_post_stats s WHERE s.id = ' . $post_id);
                }
                break;
            case 'wp-statistics':
                if (function_exists('wp_statistics_pages')) {
                    $count = wp_statistics_pages('total', '', $post_id);
                }
                break;
            case 'wordpress-popular-posts':
                if (function_exists('wpp_get_views')) {
                    $count = wpp_get_views($post_id);
                }
                break;
            case 'post-views-counter':
                if (function_exists('pvc_get_post_views')) {
                    $count = pvc_get_post_views($post_id);
                }
                break;
        }
        if (!isset($count)) return;
        
        return [
            'number' => $count,
            'format' => _n('%d view', '%d views', $count, 'directories'),
        ];
    }
}