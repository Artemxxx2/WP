<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class ColorRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(),
        );
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = [];
        foreach ($values as $value) {
            $value = $this->_application->H($value);
            $ret[] = '<span class="drts-field-color-icon" style="background-color:' . $value . '" data-color="' . $value . '">&nbsp;</span>';
        }
        return implode(PHP_EOL, $ret);
    }
}