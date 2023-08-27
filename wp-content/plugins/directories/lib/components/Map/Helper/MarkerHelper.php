<?php
namespace SabaiApps\Directories\Component\Map\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class MarkerHelper
{
    protected static $_defaultImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAPAAAAC0CAQAAAAAlWljAAABIklEQVR42u3RAQ0AAAjDMK4c6aAD0klYM116XAADFmABFmABFmABBizAAizAAizAAgxYgAVYgAVYgAVYgAELsAALsAALsAADFmABFmABFmABBizAAizAAizAAizAgAVYgAVYgAVYgAELsAALsAALsAADBgxYgAVYgAVYgAUYsAALsAALsAALMGABFmABFmABFmABBizAAizAAizAAgxYgAVYgAVYgAUYsAALsAALsAALsAADFmABFmABFmABBizAAizAAizAAgzYBMACLMACLMACLMCABViABViABViAAQuwAAuwAAuwAAswYAEWYAEWYAEWYMACLMACLMACLMCABViABViABViABRiwAAuwAAuwAAswYAEWYAEWYAEWYMACrKst65UNXM2XNOgAAAAASUVORK5CYII=';

    public function help(Application $application, Entity\Type\IEntity $entity, $fieldName, array $settings, $content = null, $checkGlobalLocationTerm = true)
    {
        $term_ids = [];
        if ($checkGlobalLocationTerm
            && isset($GLOBALS['drts_entity'])
            && $GLOBALS['drts_entity'] instanceof Entity\Type\IEntity
            && $GLOBALS['drts_entity']->isTaxonomyTerm()
            && $GLOBALS['drts_entity']->getBundleType() === 'location_location'
        ) {
            $term_ids[] = $GLOBALS['drts_entity']->getId();
            foreach ($application->Entity_Types_impl($GLOBALS['drts_entity']->getType())->entityTypeDescendantEntityIds($GLOBALS['drts_entity']) as $parent_term_id) {
                $term_ids[] = $parent_term_id;
            }
        }

        $markers = [];
        if ($values = $entity->getFieldValue($fieldName)) {
            foreach ($values as $key => $value) {
                if (!$value['lat'] || !$value['lng']) continue;

                if (!empty($term_ids)
                    && !in_array($value['term_id'], $term_ids)
                ) continue;

                $icon = null;
                if (!isset($icons)) {
                    $icons = [];
                    if (empty($settings['view_marker_icon'])
                        || $settings['view_marker_icon'] === 'image'
                    ) {
                        // Icon is entity image
                        $size = isset($settings['marker_size']) ? $this->_getSize($settings['marker_size']) : 'icon';
                        if ($icon = $application->Entity_Image($entity, $size)) {
                            $icons[] = ['url' => $icon];
                        } else {
                            if ($image_url = $application->System_NoImage_url('icon', $entity)) {
                                $icons[] = ['url' => $image_url];
                            }
                        }
                    } else {
                        // Icon is taxonomy term image/icon
                        $term_bundle_type = $settings['view_marker_icon'];
                        if ($terms = $entity->getFieldValue($term_bundle_type)) {
                            $is_map_location_taxonomy = $application->Entity_BundleTypeInfo($term_bundle_type, 'map_location_taxonomy');
                            foreach ($terms as $term) {
                                if (!$icon = $this->_getTaxonomyTermIcon($term)) continue;

                                $icons[$term->getId()] = $icon;
                                if (!$is_map_location_taxonomy) break;
                            }
                        }
                    }
                }
                if (!empty($icons)) {
                    if (!empty($value['term_id'])
                        && isset($icons[$value['term_id']])
                    ) {
                        $icon = $icons[$value['term_id']];
                    } else {
                        if (empty($is_map_location_taxonomy)) {
                            $icon = array_values($icons)[0];
                        }
                    }
                }

                if (!isset($content)) {
                    if (!isset($image)) {
                        $image_size = isset($settings['marker_image_size']) ? $settings['marker_image_size'] : 'thumbnail';
                        if (!$image = $application->Entity_Image($entity, $image_size)) {
                            $image = false;
                        }
                    }
                    if (!isset($permalink)) {
                        if (!isset($settings['marker_link']) || $settings['marker_link']) {
                            $permalink = $application->Entity_Permalink($entity, [
                                'atts' => ['class' => DRTS_BS_PREFIX . 'text-white'],
                                'skip_entity_title_filter' => !isset($settings['skip_entity_title_filter']) || !empty($settings['skip_entity_title_filter']),
                            ]);
                        } else {
                            $permalink = $application->H($entity->getTitle());
                        }
                    }
                    $address = $application->Filter(
                        'map_marker_address',
                        isset($value['display_address']) ? $value['display_address'] : (isset($value['address']) ? $value['address'] : ''),
                        [$entity, $value, $settings]
                    );
                    $_content = $this->_getContent($application, $entity, $permalink, $image, $address);
                } else {
                    $_content = $content;
                }

                $markers[$key] = array(
                    'index' => $key,
                    'entity_id' => $entity->getId(),
                    'content' => $_content,
                    'lat' => $value['lat'],
                    'lng' => $value['lng'],
                    'icon' => $icon,
                );
            }
        }
        return $markers;
    }

    protected function _getTaxonomyTermIcon(Entity\Type\IEntity $term)
    {
        if ($icon = $term->getCustomProperty('image_src')) {
            return ['url' => $icon];
        }
        if ($icon = $term->getCustomProperty('icon_src')) {
            return ['url' => $icon, 'is_full' => true];
        }
        $icon = $term->getCustomProperty('icon');
        $color = $term->getCustomProperty('color');
        if ($icon || $color) {
            return ['icon' => $icon, 'icon_color' => $color];
        }
    }
    
    protected function _getContent(Application $application, Entity\Type\IEntity $entity, $permalink, $imageSrc, $address)
    {
        if (!$content = $application->Filter('map_marker_content', null, [$entity, $permalink, $imageSrc, $address])) {
            $image_html = '<img class="' . DRTS_BS_PREFIX . 'card-img drts-no-image" src="' . self::$_defaultImage . '" alt="" />';
            if ($imageSrc) {
                $image_html .= '<img class="' . DRTS_BS_PREFIX . 'card-img" src="' . $imageSrc . '" alt="' . $application->H($entity->getTitle()) . '" />';
            }
            $content = sprintf(
                '<div class="%1$scard %1$sborder-0 %1$sbg-dark %1$stext-white drts-map-marker-content">
%2$s
<div class="%1$scard-img-overlay %1$sp-2">
<div class="%1$scard-title">%3$s</div>
<address class="drts-map-marker-address %1$scard-text">%4$s</address>
</div></div>',
                DRTS_BS_PREFIX,
                $image_html,
                $permalink,
                $application->Htmlize($address, true)
            );
        }
        return $content;
    }
    
    protected function _getSize($size)
    {   
        if (!is_numeric($size)) return $size;
        
        if ($size > 54) return 'icon_xl';
        
        return $size <= 38 ? 'icon' : 'icon_lg';
    }

    public function iconOptions(Application $application, Entity\Model\Bundle $bundle)
    {
        $ret = [];
        if ($bundle->info['entity_image']) {
            $ret['image'] = __('Show image', 'directories');
        }
        if (!empty($bundle->info['taxonomies'])) {
            foreach ($bundle->info['taxonomies'] as $taxonomy_bundle_type => $taxonomy) {
                if (!$taxonomy_bundle = $application->Entity_Bundle($taxonomy)) continue;

                if (empty($taxonomy_bundle->info['entity_image'])
                    && empty($taxonomy_bundle->info['entity_icon'])
                ) continue;

                $ret[$taxonomy_bundle_type] = __('Show taxonomy image/icon', 'directories')
                    . ' - ' . $taxonomy_bundle->getLabel('singular');
            }
        }
        if (!empty($ret)) {
            $ret['default'] = __('Default', 'directories');
        }

        return $ret;
    }
}