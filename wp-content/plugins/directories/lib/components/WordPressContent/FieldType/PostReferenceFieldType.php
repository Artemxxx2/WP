<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class PostReferenceFieldType extends Entity\FieldType\ReferenceFieldType
{
    protected function _fieldTypeInfo()
    {
        return [
            'label' => _x('Post Reference', 'post reference field type', 'directories'),
            'schema_type' => 'entity_reference',
            'default_settings' => [
                'post_type' => 'post',
            ]
        ] + parent::_fieldTypeInfo();
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $exclude = $this->_application->getComponent('WordPressContent')->getPostTypeNames();
        $exclude[] = 'attachment';
        $options = [];
        foreach (get_post_types(['publicly_queryable' => true, 'public' => true], 'object') as $post_type) {
            if (in_array($post_type->name, $exclude)) continue;

            $options[$post_type->name] = $post_type->labels->singular_name;
        }
        return [
            'post_type' => [
                '#type' => 'select',
                '#title' => __('Post type', 'directories'),
                '#options' => $options,
                '#default_value' => $settings['post_type'],
                '#required' => true,
            ],
        ];
    }

    public function fieldTypeOnLoad(IField $field, array &$values, Entity\Type\IEntity $entity, array $allValues)
    {
        foreach ($values as $key => $value) {
            $values[$key] = $value['value'];
        }
    }

    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {
        $new = [];
        foreach ($valueToSave as $value) {
            $new[] = $value['value'];
        }
        return $currentLoadedValue !== $new;
    }

    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        $info = parent::fieldQueryableInfo($field, $inAdmin);
        $info['tip'] .= ' ' . __('Enter "_current_post_" for current WordPress post.', 'directories');
        return $info;
    }

    protected function _getReferenceEntityIds(array $ids, &$includeNull)
    {
        $ret = [];
        foreach ($ids as $id) {
            if ($id == 0) {
                $includeNull = true;
            } elseif ($id === '_current_post_') {
                if ($post_id = get_the_ID()) {
                    $ret[] = $post_id;
                }
            } else {
                $ret[] = $id;
            }
        }
        return $ret;
    }
}
