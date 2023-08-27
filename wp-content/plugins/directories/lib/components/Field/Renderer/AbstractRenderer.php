<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

abstract class AbstractRenderer implements IRenderer
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function fieldRendererInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_fieldRendererInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function fieldRendererInit(IField $field, array $settings){}

    public function fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = (array)$this->_fieldRendererSettingsForm($field, $settings, $parents);
        if (!$this->fieldRendererInfo('accept_multiple')) {
            if (1 !== $max_num_items = $field->getFieldMaxNumItems()) {
                $form += array(
                    '_limit' => array(
                        '#type' => 'slider',
                        '#integer' => true,
                        '#min_value' => 0,
                        '#max_value' => $max_num_items === 0 ? 20 : $max_num_items,
                        '#min_text' => __('Unlimited', 'directories'),
                        '#title' => __('Max items to display', 'directories'),
                        '#default_value' => isset($settings['_limit']) ? $settings['_limit'] : null,
                        '#weight' => 100,
                    ),
                );
                if (false !== $this->fieldRendererInfo('separatable')) {
                    $form += array(
                        '_separator' => array(
                            '#states' => array(
                                'invisible' => array(
                                    sprintf('input[name="%s[_limit]"]', $this->_application->Form_FieldName($parents)) => array('value' => 1),
                                ),
                            ),
                            '#type' => 'textfield',
                            '#title' => __('Field value separator', 'directories'),
                            '#min_length' => 1,
                            '#default_value' => isset($settings['_separator']) ? $settings['_separator'] : null,
                            '#weight' => 101,
                            '#no_trim' => true,
                        ),
                    );
                }
            } else {
                if (false !== $this->fieldRendererInfo('separatable')) {
                    $form += array(
                        '_separator' => array(
                            '#type' => 'hidden',
                            '#default_value' => isset($settings['_separator']) ? $settings['_separator'] : null,
                        ),
                    );
                }
            }
        }
        return $form;
    }
    
    public function fieldRendererAmpSettingsForm(IField $field, array $settings, array $parents = [])
    {
        
    }
    
    public function fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values = null)
    {
        if (!isset($values)) {
            $values = $entity->getFieldValue($field->getFieldName());
        }
        if (!empty($settings['_limit'])
            && (0 < $more = count($values) - $settings['_limit'])
        ) {
            $values = array_slice($values, 0, $settings['_limit']);
        } else {
            $more = 0;
        }
        
        return $this->_fieldRendererRenderField($field, $settings, $entity, $values, $more);
    }

    abstract protected function _fieldRendererInfo();
    abstract protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0);
    
    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = []){}
    
    public function fieldRendererIsPreRenderable(IField $field, array $settings)
    {
        return false;
    }
    
    public function fieldRendererPreRender(IField $field, array $settings, array $entities){}
    
    public function fieldRendererReadableSettings(IField $field, array $settings)
    {
        $ret = (array)$this->_fieldRendererReadableSettings($field, $settings);
        uasort($ret, function($a, $b) {
            return (int)@$a['weight'] < (int)@$b['weight'] ? -1 : 1;
        });
        if (!$this->fieldRendererInfo('accept_multiple')
            && (1 !== $field->getFieldMaxNumItems())
        ) {
            $ret['_limit'] = [
                'label' => __('Max items to display', 'directories'),
                'value' => empty($settings['_limit']) ? __('Unlimited', 'directories') : $settings['_limit'],
            ];
            if (false !== $this->fieldRendererInfo('separatable')
                && isset($settings['_separator'])
                && strlen($settings['_separator'])
            ) {
                $ret['_separator'] = [
                    'label' => __('Field value separator', 'directories'),
                    'value' => $settings['_separator'],
                ];
            }
        }
        return $ret;
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings){}
    
    public function fieldRendererSupports(Entity\Model\Bundle $bundle, IField $field)
    {
        return true;
    }
        
    public function fieldRendererSupportsAmp(Entity\Model\Bundle $bundle)
    {
        return false;
    }

    protected function _getLinkTargetOptions($includeSelf = false)
    {
        $ret = [
            '' => __('Current window', 'directories'),
            '_blank' => __('New window', 'directories'),
        ];
        if ($includeSelf) $ret['_self'] = $ret[''];
        return $ret;
    }
    
    protected function _getLinkRelAttrOptions()
    {
        return [
            'nofollow' => __('Add "nofollow"', 'directories'),
            'external' => __('Add "external"', 'directories'),
            'ugc' => __('Add "ugc"', 'directories'),
            'sponsored' => __('Add "sponsored"', 'directories'),
        ];
    }
    
    protected function _getImageSizeOptions()
    {
        return [
            'thumbnail' => __('Thumbnail', 'directories'),
            'thumbnail_scaled' => __('Thumbnail (scaled)', 'directories'),
            'medium' => __('Medium size', 'directories'),
            'large' => __('Large size', 'directories'),
            'full' => __('Original size', 'directories'),
        ];
    }
}