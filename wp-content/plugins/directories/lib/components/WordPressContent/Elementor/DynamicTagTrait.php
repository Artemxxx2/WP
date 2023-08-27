<?php
namespace SabaiApps\Directories\Component\WordPressContent\Elementor;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use Elementor\Controls_Manager;

trait DynamicTagTrait
{
    public function get_group()
    {
        return 'drts';
    }

    protected function _getDynamicTagFields(array $fieldTypes = null, $interface = null, $entityType = 'post')
    {
        $fields = [];
        $field_types = empty($fieldTypes) ? null : (array)$fieldTypes;
        foreach (drts()->Entity_Bundles() as $bundle) {
            if (empty($bundle->info['public'])
                || !empty($bundle->info['internal'])
            ) continue;

            if ($entityType === 'term') {
                if (empty($bundle->info['is_taxonomy'])) continue;
            } else {
                if (!empty($bundle->info['is_taxonomy'])) continue;
            }

            $options = drts()->Entity_Field_options($bundle, [
                'type' => $field_types,
                'interface' => $interface,
                'exclude_disabled' => true,
                'exclude_property' => true,
            ]);
            if (empty($options)) continue;

            $fields[] = [
                'label' => $bundle->getGroupLabel() . ' - ' . $bundle->getLabel('singular'),
                'options' => $options,
            ];
        }

        uasort($fields, function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        return $fields;
    }

    protected function _getDynamicTagField(IEntity $entity, $fieldName)
    {
        if (!$field = drts()->Entity_Field($entity, $fieldName)) {
            drts()->logError(sprintf('Invalid Elementor dynamic tag field %s for content type %s.', $fieldName, $entity->getBundleName()));
            return;
        }
        return $field;
    }

    protected function _getEntity($registerGlobals = true)
    {
        if (isset($GLOBALS['drts_entity'])) {
            drts()->Entity_Field_load($GLOBALS['drts_entity']);
            return $GLOBALS['drts_entity'];
        }

        if (!$obj = get_queried_object()) return;

        if ($obj instanceof \WP_Post) {
            if (!drts()->getComponent('WordPressContent')->hasPostType($obj->post_type)) return;

            $entity = new \SabaiApps\Directories\Component\WordPressContent\EntityType\PostEntity($obj);
            if ($registerGlobals
                && is_single()
            ) {
                $GLOBALS['drts_entity'] = $entity;
            }
        } elseif ($obj instanceof \WP_Term) {
            if (!drts()->getComponent('WordPressContent')->hasTaxonomy($obj->taxonomy)) return;

            $entity = new \SabaiApps\Directories\Component\WordPressContent\EntityType\TermEntity($obj);
            if ($registerGlobals
                && is_tax()
            ) {
                $GLOBALS['drts_entity'] = $entity;
            }
        } else {
            return;
        }
        drts()->Entity_Field_load($entity);

        return $entity;
    }
}
