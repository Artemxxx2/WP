<?php
namespace SabaiApps\Directories\Component\Review\EntityBundleType;

use SabaiApps\Directories\Component\Entity\BundleType\AbstractBundleType;

class ReviewEntityBundleType extends AbstractBundleType
{
    protected function _entityBundleTypeInfo()
    {
        return [
            'type' => $this->_name,
            'entity_type' => 'post',
            'slug' => 'reviews',
            'suffix' => 'rev_rev',
            'component' => 'Review',
            'parent' => 'review_enable',
            'label' => __('Reviews', 'directories-reviews'),
            'label_singular' => __('Review', 'directories-reviews'),
            'label_add' => __('Write a Review', 'directories-reviews'),
            'label_all' => __('All Reviews', 'directories-reviews'),
            'label_count' => __('%s review', 'directories-reviews'),
            'label_count2' => __('%s reviews', 'directories-reviews'),
            'label_page' => __('Review: %s', 'directories-reviews'),
            'icon' => 'fas fa-pencil-alt',
            'public' => true,
            'properties' => [
                'content' => [
                    'label' => __('Review', 'directories-reviews'),
                    'widget_settings' => ['rows' => 10],
                    'required' => true,
                    'weight' => 2,
                ],
            ],
            'fields' => __DIR__ . '/review_fields.php',
            'displays' => __DIR__ . '/review_displays.php',
            'views' => __DIR__ . '/review_views.php',
            'voting_enable' => ['updown', 'bookmark'],
            'frontendsubmit_enable' => true,
            'frontendsubmit_guest' => true,
            'permalink' => ['slug' => 'review'],
            'entity_image' => 'review_photos',
            'view_list_grid_cols' => ['xs' => 12, 'md' => 6, 'xl' => 4],
        ];
    }

    public function entityBundleTypeSettingsForm(array $settings, array $parents = [])
    {
        return [
            '#title' => __('Review Settings', 'directories-reviews'),
            'review_criteria' => [
                '#type' => 'options',
                '#title' => __('Rating criteria', 'directories-reviews'),
                '#horizontal' => true,
                '#disable_icon' => true,
                '#disable_add_csv' => true,
                '#multiple' => true,
                '#default_value' => [
                    'options' => $criteria = (empty($settings['review_criteria']) ? [] : $settings['review_criteria']),
                    'default' => array_keys($criteria),
                ],
                '#value_title' => __('slug', 'directories-reviews'),
                '#slugify_value' => true,
                '#default_options_only' => true,
                '#value_regex' => '/^[a-z0-9_]{1,40}$/',
                '#value_regex_error_message' => __('Rating criteria slugs must be 1-40 characters long.', 'directories-reviews'),
            ],
        ];
    }
}
