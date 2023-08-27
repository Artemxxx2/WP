<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class ReferenceLinkFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'label' => __('Link', 'directories'),
            'field_types' => ['entity_reference'],
            'default_settings' => [
                'icon' => false,
                'icon_size' => 'sm',
                'no_link' => false,
                '_separator' => ', ',],
            'inlineable' => true,
        ];
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        if (!$bundle = $this->_getReferencedBundle($field)) return;

        return $this->_application->System_Util_iconSettingsForm($bundle, $settings, $parents) + [
            'no_link' => [
                '#type' => 'checkbox',
                '#title' => __('Do not link', 'directories'),
                '#default_value' => !empty($settings['no_link']),
                '#horizontal' => true,
            ],
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        if (!$bundle = $this->_getReferencedBundle($field)) return;

        $entity_ids = [];
        foreach ($values as $referenced_item) {
            $entity_ids[] = $referenced_item->getId();
        }
        if (empty($entity_ids)) return;

        $entities = $this->_application->Entity_Entities($bundle->entitytype_name, $entity_ids, !empty($settings['icon']), true);

        // Render permalinks
        $options = $this->_application->System_Util_iconSettingsToPermalinkOptions($bundle, $settings);
        $options['no_link'] = !empty($settings['no_link']);
        $ret = [];
        foreach (array_keys($entities) as $i) {
            $ret[] = $this->_application->Entity_Permalink($entities[$i], $options);
        }

        return implode($settings['_separator'], $ret);
    }

    protected function _getReferencedBundle(Field\IField $field)
    {
        $field_settings = $field->getFieldSettings();
        if (!empty($field_settings['bundle'])
            && ($bundle = $this->_application->Entity_Bundle($field_settings['bundle']))
        ) return $bundle;
    }
}
