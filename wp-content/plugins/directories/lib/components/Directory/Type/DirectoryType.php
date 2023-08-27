<?php
namespace SabaiApps\Directories\Component\Directory\Type;

class DirectoryType extends AbstractType
{
    protected function _directoryInfo()
    {
        return array(
            'label' => _x('Directory', 'directory type', 'directories'),
            'content_types' => array('listing'),
        );
    }

    public function directoryContentTypeInfo($contentType)
    {
        switch ($contentType) {
            case 'listing':
                return array(
                    'component' => 'Directory',
                    'entity_type' => 'post',
                    'suffix' => 'dir_ltg',
                    'slug' => 'listing',
                    'is_primary' => true,
                    'label' => __('Listings', 'directories'),
                    'label_singular' => __('Listing', 'directories'),
                    'label_add' => __('Add Listing', 'directories'),
                    'label_all' => __('All Listings', 'directories'),
                    'label_select' => __('Select Listing', 'directories'),
                    'label_search' => __('Search Listings', 'directories'),
                    'label_count' => __('%s listing', 'directories'),
                    'label_count2' => __('%s listings', 'directories'),
                    'label_page' => __('Listing: %s', 'directories'),
                    'icon' => 'fas fa-file-alt',
                    'public' => true,
                    'properties' => array(
                        'content' => array(
                            'label' => __('Listing Description', 'directories'),
                            'weight' => 10,
                            'widget_settings' => array('rows' => 10),
                        ),
                    ),
                    'fields' => [],
                    'taxonomies' => [],
                    'privatable' => false,
                    'featurable' => true,
                    'expirable' => true,
                    'frontendsubmit_enable' => true,
                    'frontendsubmit_guest' => true,
                    'permalink' => true,
                    'entity_tag' => 'directory_category',
                    'voting_enable' => array('updown', 'rating', 'bookmark'),
                    'search_enable' => true,
                    'search_fields' => array(
                        'search_keyword' => [],
                    ),
                    'directory_category_enable' => true,
                    'directory_category_field' => array('weight' => 2),
                    'directory_tag_enable' => true,
                    'directory_tag_field' => array('weight' => 98),
                    'displays' => 'directory_listing_displays.php',
                    'views' => 'directory_listing_views.php',
                );
        }
    }
}
