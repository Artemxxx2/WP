<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class MetadataHelper extends Entity\Helper\UrlHelper
{
    static protected $_singleItemPageMetaKeys;

    public function post(Application $application, $value, $postId, $metaKey, $single)
    {
        if ((!$post_type = get_post_type($postId))
            || !$application->getComponent('WordPressContent')->hasPostType($post_type)
        ) {
            // Check if single item page for taxonomy
            if (($post_type !== 'product') // this may happen with some themes
                && is_tax()
                && ($term = get_term($postId))
                && !is_wp_error($term)
                && $application->getComponent('WordPressContent')->hasTaxonomy($term->taxonomy)
            ) {
                $bundle_name = $term->taxonomy;
            } else {
                return $value;
            }
        } else {
            if (is_tax()) {
                $bundle_name = get_queried_object()->taxonomy;
            } elseif (is_single()) {
                $bundle_name = $post_type;
            }
        }

        if (!empty($metaKey)) {
            if (!isset(self::$_singleItemPageMetaKeys)) {
                self::$_singleItemPageMetaKeys = $application->Filter('wordpresscontent_single_item_page_meta_keys', self::_getThemePostMetaKeys($application));
            }

            // Check if this is a meta key for the single item page
            $found = false;
            if (empty(self::$_singleItemPageMetaKeys) // any meta key allowed
                || in_array($metaKey, self::$_singleItemPageMetaKeys[0])
            ) {
                $found = true;
            } else {
                if (!empty(self::$_singleItemPageMetaKeys[1])) {
                    foreach (self::$_singleItemPageMetaKeys[1] as $prefix) {
                        if (strpos($metaKey, $prefix) === 0) {
                            $found = true;
                        }
                    }
                }
            }

            if ($found) {
                // Fetch meta key value configured for the single item page.
                if (isset($bundle_name)
                    && ($page_id = $application->getComponent('WordPressContent')->getBundleSingleItemPageId($bundle_name))
                ) {
                    remove_filter('get_post_metadata', [$application, 'WordPressContent_Metadata_post'], 10); // prevent loop
                    $value = get_post_meta($page_id, $metaKey, $single);
                    if ($single) $value = [$value];  // WordPress looks for index 0 if single value returned by get_metadata filter is array
                    add_filter('get_post_metadata', [$application, 'WordPressContent_Metadata_post'], 10, 4);
                }
                return $value;
            }
        }  else {
            // Called without a meta key
            if (isset($bundle_name)
                && ($page_id = $application->getComponent('WordPressContent')->getBundleSingleItemPageId($bundle_name))
            ) {
                // Call get_post_meta with single item page ID
                remove_filter('get_post_metadata', [$application, 'WordPressContent_Metadata_post'], 10); // prevent loop
                $value = get_post_meta($page_id);
                add_filter('get_post_metadata', [$application, 'WordPressContent_Metadata_post'], 10, 4);
                return $value;
            }
        }

        return $this->_getMetadata($application, 'post', $postId, $metaKey, $single, $value);
    }

    public function term(Application $application, $value, $termId, $metaKey, $single)
    {
        if ((!$taxonomy = \SabaiApps\Directories\Component\WordPressContent\WordPressContentComponent::getTermTaxonomy($termId))
            || !$application->getComponent('WordPressContent')->hasTaxonomy($taxonomy)
        ) return $value;

        return $this->_getMetadata($application, 'term', $termId, $metaKey, $single, $value);
    }

    protected function _getMetadata(Application $application, $entityType, $entityId, $metaKey, $single, $value)
    {
        if (!empty($metaKey)) {
            if (strpos($metaKey, '_drts_') !== 0
                || (!$field_name = substr($metaKey, strlen('_drts_')))
            ) {
                return $value; // meta key must start with _drts_ when requesting for a specific field
            }
        }

        if ($entity = $application->Entity_Entity($entityType, $entityId)) {
            if (isset($field_name)) {
                $key = null;
                if (strpos($field_name, '__')) {
                    $parts = explode('__', $field_name);
                    $field_name = $parts[0];
                    unset($parts[0]);
                    $key = array_values($parts);
                }
                if ($single) {
                    if (null === $value = $entity->getSingleFieldValue($field_name, $key)) {
                        $value = '';
                    }
                    $value = [$value]; // WordPress looks for index 0 if single value returned by get_metadata filter is array
                } else {
                    $value = $entity->getFieldValue($field_name, $key);
                    if ($value === null
                        || $value === false
                    ) {
                        $value = [];
                    }
                }
            } else {
                // Need to call get_post_meta() again since returning a non null value
                // with get_post_metadata filter will not include other meta values.
                remove_filter('get_post_metadata', [$application, 'WordPressContent_Metadata_post'], 10); // prevent loop
                $value = get_post_meta($entity->getId());
                add_filter('get_post_metadata', [$application, 'WordPressContent_Metadata_post'], 10, 4);

                // Add all field values
                $_value = $entity->getFieldValues();
                foreach (array_keys($_value) as $field_name) {
                    if (!isset($_value[$field_name])) continue;

                    $value['_drts_' . $field_name] = is_array($_value[$field_name]) ? $_value[$field_name] : [$_value[$field_name]];
                }
            }
        }
        return $value;
    }

    protected static function _getThemePostMetaKeys(Application $application)
    {
        $keys = $prefix = [];
        switch ($application->getComponent('WordPressContent')->getThemeSlug()) {
            case 'divi':
                $prefix[] = '_et_';
                break;
            case 'extra':
                $prefix[] = '_extra_';
                break;
            case 'salient':
                $prefix[] = '_nectar_';
                break;
            case 'avada':
                $prefix[] = 'pyre_';
                $prefix[] = 'sbg_';
                $prefix[] = '_fusion';
                break;
            case 'astra':
                $keys = ['site-post-title', 'site-sidebar-layout', 'site-content-layout', 'ast-main-header-display', 'footer-sml-layout', 'ast-featured-img', 'theme-transparent-header-meta'];
                break;
            case 'enfold':
                $keys = ['layout', 'sidebar', 'footer', 'header_title_bar', 'header_transparency'];
                break;
            case 'total':
                $prefix[] = 'wpex_';
                $keys = ['sidebar'];
                break;
            case 'x';
                $prefix[] = '_x_';
                break;
            case 'the7':
                $prefix[] = '_dt_';
                break;
            case 'betheme':
                $prefix[] = 'mfn-';
                break;
            case 'genesis':
                $prefix[] = 'genesis_layout';
                break;
            case 'newspaper':
                $prefix[] = 'td_';
                $prefix[] = 'tdb_';
                $prefix[] = 'tdc_';
                break;
            case 'Uncode':
                $prefix[] = '_uncode_';
                break;
            case 'woodmart':
                $prefix[] = '_woodmart_';
                break;
            default:
        }
        $keys[] = '_wp_page_template';
        $prefix[] = '_wpb_'; // WPBakery Page Builder

        return [$keys, $prefix];
    }
}