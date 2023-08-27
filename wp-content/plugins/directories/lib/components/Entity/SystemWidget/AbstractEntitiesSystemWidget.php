<?php
namespace SabaiApps\Directories\Component\Entity\SystemWidget;

use SabaiApps\Directories\Component\System\Widget\AbstractWidget;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

abstract class AbstractEntitiesSystemWidget extends AbstractWidget
{
    protected $_bundleType;

    public function __construct(Application $application, $name, $bundleType)
    {
        parent::__construct($application, $name);
        $this->_bundleType = $bundleType;
    }

    abstract protected function _getBundle(array $settings);

    protected function _getDefaultSettings()
    {
        return [
            'limit' => 5,
            'sort' => 'random',
            'hide_title' => false,
            'show_summary' => true,
            'summary_num_chars' => 50,
            'show_published' => true,
            'show_thumbnail' => true,
            'thumbnail_size' => 64,
            'thumbnail_style' => 'rounded',
            'featured_only' => false,
        ];
    }

    protected function _systemWidgetInfo()
    {
        return [
            'title' => $bundle_type_label = $this->_application->Entity_BundleTypeInfo($this->_bundleType, 'label'),
            'summary' => sprintf(__("A list of your site's %s.", 'directories'), $bundle_type_label),
        ];
    }

    protected function _getWidgetSettings(array $settings)
    {
        if (!$bundle = $this->_getBundle($settings)) return;

        $default_settings = $this->_getDefaultSettings();
        $form = [
            'limit' => [
                '#type' => 'textfield',
                '#title' => __('Number of items to show (0 for unlimited)', 'directories'),
                '#integer' => true,
                '#default_value' => $default_settings['limit'],
                '#size' => 3,
                '#weight' => 5,
            ],
            'sort' => [
                '#type' => 'select',
                '#title' => __('Sort by', 'directories'),
                '#options' => $this->_application->Entity_Sorts_options($bundle),
                '#default_value' => $default_settings['sort'],
                '#weight' => 10,
            ],
            'hide_title' => [
                '#type' => 'checkbox',
                '#title' => __('Hide title', 'directories'),
                '#default_value' => !empty($default_settings['hide_title']),
                '#weight' => 15,
            ],
            'show_summary' => [
                '#type' => 'checkbox',
                '#title' => __('Show summary', 'directories'),
                '#default_value' => !empty($default_settings['show_summary']),
                '#weight' => 20,
            ],
            'summary_num_chars' => [
                '#type' => 'textfield',
                '#title' => __('Number of summary characters', 'directories'),
                '#integer' => true,
                '#default_value' => $default_settings['summary_num_chars'],
                '#size' => 6,
                '#weight' => 21,
            ],
        ];
        $props = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeInfo('properties');
        if (!empty($props['published'])) {
            $form['show_published'] = [
                '#type' => 'checkbox',
                '#title' => __('Show published date', 'directories'),
                '#default_value' => !empty($default_settings['show_published']),
                '#weight' => 25,
            ];
        }
        if ($this->_application->Entity_BundleTypeInfo($this->_bundleType, 'entity_image')) {
            $form['show_thumbnail'] = [
                '#type' => 'checkbox',
                '#title' => __('Show thumbnail', 'directories'),
                '#default_value' => !empty($default_settings['show_thubmanil']),
                '#weight' => 30,
            ];
            $form['thumbnail_size'] = [
                '#type' => 'textfield',
                '#title' => __('Thumbnail size in pixels', 'directories'),
                '#default_value' => $default_settings['thumbnail_size'],
                '#min_value' => 10,
                '#max_value' => 100,
                '#integer' => true,
                '#weight' => 31,
            ];
            $form['thumbnail_style'] = [
                '#type' => 'select',
                '#title' => __('Thumbnail style', 'directories'),
                '#options' => [
                    'rounded-0' => __('Square', 'directories'),
                    'rounded' => __('Rounded square', 'directories'),
                    'rounded-circle' => __('Circle', 'directories'),
                ],
                '#default_value' => $default_settings['thumbnail_style'],
                '#min_value' => 10,
                '#max_value' => 100,
                '#integer' => true,
                '#weight' => 32,
            ];
            $form['show_default_thumbnail'] = [
                '#type' => 'checkbox',
                '#title' => __('Show "No Image" image if no thumbnail', 'directories'),
                '#default_value' => true,
                '#weight' => 33,
            ];
        }
        if ($this->_application->Entity_BundleTypeInfo($this->_bundleType, 'featurable')) {
            $form['featured_only'] = [
                '#type' => 'checkbox',
                '#title' => __('Show featured items only', 'directories'),
                '#default_value' => !empty($default_settings['featured_only']),
                '#weight' => 50,
            ];
        }

        return $form;
    }

    protected function _getWidgetContent(array $settings)
    {
        if (!$query = $this->_getQuery($settings)) return;

        $query = $this->_application->Filter('entity_widget_list_query', $query, array($this->_bundleType, $settings, $this->_name));
        $entities = $query->fetch($settings['limit']);
        if (empty($entities)) return;

        $ret = [];
        $image_class = isset($settings['thumbnail_style']) ? DRTS_BS_PREFIX . $settings['thumbnail_style'] : null;
        $image_style = 'width:' . $settings['thumbnail_size'] . 'px;';
        if (isset($settings['thumbnail_style'])
            && $settings['thumbnail_style'] === 'rounded-circle'
        ) {
            $image_size = 'icon_xl';
            $image_style .= 'height:' . $settings['thumbnail_size'] . 'px;';
        } else {
            $image_size = 'thumbnail_scaled';
        }
        foreach ($entities as $entity) {
            if (!empty($settings['show_thumbnail'])
                && ($src = $this->_application->Entity_Image($entity, $image_size))
            ) {
                $image = [
                    'src' => $src,
                    'alt' => $this->_application->Entity_Title($entity),
                    'style' => $image_style,
                    'class' => $image_class,
                ];
            } else {
                if (!empty($settings['show_default_thumbnail'])) {
                    $image = [
                        'src' => $this->_application->System_NoImage('thumbnail', true, $entity),
                        'alt' => $this->_application->Entity_Title($entity),
                        'style' => $image_style,
                        'class' => $image_class . ' drts-system-widget-no-image',
                    ];
                } else {
                    $image = null;
                }
            }
            $meta = [];
            if (!empty($settings['show_published'])) {
                $meta[] = '<i class="far fa-fw fa-calendar"></i> ' . $this->_application->System_Date($entity->getTimestamp(), true);
            }
            $content = [
                'summary' => !empty($settings['show_summary']) ? $this->_application->Summarize($entity->getContent(), $settings['summary_num_chars']) : null,
                'title_link' => !isset($settings['hide_title']) || !$settings['hide_title'] ? $this->_application->Entity_Permalink($entity) : null,
                'meta' => $meta,
                'image' => $image,
                'url' => $image ? $this->_application->Entity_PermalinkUrl($entity) : null,
            ];
            $ret[] = $this->_application->Filter('entity_system_widget_entity_content', $content, [$this->_name, $entity, $settings]);
        }
        return $ret;
    }

    protected function _getQuery(array $settings)
    {
        if (!$bundle = $this->_getBundle($settings)) return;

        $query = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)
            ->fieldIs('status', $this->_application->Entity_Status($bundle->entitytype_name, 'publish'))
            ->sort($settings['sort'], $this->_application->Entity_Sorts($bundle), $this->_getCacheId($settings));
        if (!empty($settings['featured_only'])) {
            $query->fieldIsNotNull('entity_featured');
        }
        if (!empty($bundle->info['taxonomies'])
            && ($term = $this->_isOnTaxonomyTermPage())
            && in_array($term->getBundleName(), $bundle->info['taxonomies'])
        ) {
            $query->fieldIs($term->getBundleType(), $term->getId());
        }

        return $query;
    }

    protected function _isOnTaxonomyTermPage()
    {
        if (isset($GLOBALS['drts_entity'])) {
            $entity = $GLOBALS['drts_entity'];
            if ($entity instanceof IEntity
                && $entity->isTaxonomyTerm()
            ) {
                return $entity;
            }
        }
    }

    protected function _getCacheId(array $settings)
    {
        if (($term = $this->_isOnTaxonomyTermPage())
            && ($bundle = $this->_getBundle($settings))
            && !empty($bundle->info['taxonomies'])
            && in_array($term->getBundleName(), $bundle->info['taxonomies'])
        ) {
            $settings['_term_id'] = $term->getId();
        }
        return 'widgets_widget_' . $this->_name . '_' . md5(serialize($settings));
    }
}
