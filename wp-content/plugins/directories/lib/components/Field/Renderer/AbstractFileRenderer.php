<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

abstract class AbstractFileRenderer extends AbstractRenderer
{
    protected $_fieldTypes;
    
    protected function _fieldRendererInfo()
    {
         return array(
            'field_types' => $this->_fieldTypes,
            'default_settings' => array(
                '_separator' => ' ',
            ),
            'inlineable' => true,
        );
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = [];
        foreach ($values as $value) {
            $extension = $this->_getFileExtension($field, $settings, $value, $entity);
            if (!$rendered = $this->_application->Filter('field_render_file', '', [$field, $settings, $value, $entity, $extension])) {
                $rendered = $this->_renderFile($field, $settings, $value, $entity, $extension);
            }
            $ret[] = $rendered;
        }
        return implode($settings['_separator'], $ret);
    }

    protected function _renderFile(IField $field, array $settings, $value, Entity\Type\IEntity $entity, $extension)
    {
        $rendered = $this->_getFileLink($field, $settings, $value, $entity);
        if ($icon = $this->_application->FileIcon($extension)) {
            $rendered = '<i class="' . $icon . '"></i> ' . $rendered;
        }
        return $rendered;
    }
    
    abstract protected function _getFileExtension(IField $field, array $settings, $value, Entity\Type\IEntity $entity);
    abstract protected function _getFileLink(IField $field, array $settings, $value, Entity\Type\IEntity $entity);
}