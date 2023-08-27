<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Field\Widget\CheckboxesWidget;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class ReferenceCheckboxesFieldWidget extends CheckboxesWidget
{
    protected function _fieldWidgetInfo()
    {
        $info = parent::_fieldWidgetInfo();
        $info['field_types'] = ['entity_reference'];
        $info['default_settings'] += [
            'own_only' => false,
            'max_num' => 50,
        ];
        return $info;
    }

    public function fieldWidgetSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return parent::fieldWidgetSettingsForm($fieldType, $bundle, $settings, $parents, $rootParents) + [
            'own_only' => [
                '#title' => __('Show own content items only', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['own_only']),
            ],
            'max_num' => [
                '#title' => __('Max number of options', 'directories'),
                '#type' => 'slider',
                '#default_value' => $settings['num_suggest'],
                '#min_value' => 0,
                '#max_value' => 120,
                '#min_text' => __('Unlimited', 'directories'),
                '#integer' => true,
                '#step' => 12,
            ],
        ];
    }

    protected function _getReferenceBundle(IField $field)
    {
        $settings = $field->getFieldSettings();
        if (empty($settings['bundle'])) return;

        return $this->_application->Entity_Bundle($settings['bundle']);
    }

    protected function _getDefaultOptions(IField $field, array $settings, $value = null, IEntity $entity = null, $language = null)
    {
        $ret = [];
        if (isset($value)) {
            foreach ((array)$value as $_value) {
                $ret[] = is_object($_value) ? $_value->getId() : $_value;
            }
        }
        return $ret;
    }

    protected function _loadOptions(IField $field, array $settings, IEntity $entity = null, $language = null)
    {
        if (!isset($this->_options[$field->getFieldId()])) {
            $options = [
                'options' => [],
                'icons' => [],
                'default' => [],
            ];

            if ($bundle = $this->_getReferenceBundle($field)) {
                $query = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name);
                if (!empty($settings['own_only'])) {
                    if (isset($entity)
                        && $entity->getAuthorId()
                    ) {
                        $query->fieldIs('author', $entity->getAuthorId());
                    } else {
                        if (!$this->_application->getUser()->isAnonymous()) {
                            $query->fieldIs('author', $this->_application->getUser()->id);
                        }
                    }
                }
                if (!empty($settings['sort'])) {
                    $_field = 'title';
                    $order = 'ASC';
                } else {
                    $_field = 'published';
                    $order = 'DESC';
                }
                $query->sortByField($_field, $order);
                foreach ($query->fetch($settings['max_num'], 0, $language, false) as $_entity) {
                    $options['options'][$_entity->getId()] = $this->_application->Entity_Title($_entity);
                }
            }

            $this->_options[$field->getFieldId()] = $options;
        }

        return $this->_options[$field->getFieldId()];
    }
}
