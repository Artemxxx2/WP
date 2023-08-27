<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class ReferenceFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return [
            'field_types' => [$this->_name],
            'default_settings' => [
                'view' => null,
            ],
            'accept_multiple' => true,
        ];
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return array(
            'view' => array(
                '#title' => __('Select view', 'directories'),
                '#type' => 'select',
                '#horizontal' => true,
                '#options' => $this->_getViewOptions($field),
                '#default_value' => $settings['view'],
            ),
        );
    }

    protected function _getViewOptions(Field\IField $field)
    {
        $views = [];
        $field_settings = $field->getFieldSettings();
        if (!empty($field_settings['bundle'])
            && ($bundle = $this->_application->Entity_Bundle($field_settings['bundle']))
        ) {
            foreach ($this->_application->getModel('View', 'View')->bundleName_is($bundle->name)->fetch() as $view) {
                $views[$view->name] = $view->getLabel();
            }
        }
        return $views;
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['bundle'])
            || (!$bundle = $this->_application->Entity_Bundle($field_settings['bundle']))
            || (!$view_path = $this->_getBundleViewPath($bundle, $field, $settings))
        ) return;

        $referenced_item_ids = [];
        foreach ($values as $referenced_item) {
            $referenced_item_ids[] = $referenced_item->getId();
        }
        if (empty($referenced_item_ids)) return;

        $view_settings = $this->_getViewSettings($field, $settings);
        $view_settings['query']['fields'][$bundle->entitytype_name . '_id'] = implode(',', $referenced_item_ids);
        $view_settings['filter']['show'] = false;

        return $this->_application->getPlatform()->render(
            $view_path,
            [
                'settings' => [
                    'load_view' => $this->_getViewName($field, $settings),
                    'mode' => $this->_getViewMode($field, $settings),
                    'push_state' => false,
                    'settings' => $view_settings,
                ],
            ],
            [
                'cache' => false,
                'title' => false,
                'container' => null,
                'render_assets' => false,
            ]
        );
    }

    protected function _getViewName(Field\IField $field, array &$settings)
    {
        return $settings['view'];
    }

    protected function _getViewMode(Field\IField $field, array &$settings){}

    protected function _getViewSettings(Field\IField $field, array &$settings)
    {
        return [];
    }

    protected function _getBundleViewPath(Entity\Model\Bundle $bundle, Field\IField $field, array &$settings)
    {
        return $bundle->getPath();
    }

    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        return [
            'view' => [
                'label' => __('Select view', 'directories'),
                'value' => $this->_getViewOptions($field)[$settings['view']],
            ],
        ];
    }
}
