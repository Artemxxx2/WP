<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class ImageFieldType extends FileFieldType implements
    Field\Type\IImage,
    Field\Type\IQueryable,
    Field\Type\IOpenGraph,
    Field\Type\IHumanReadable,
    Field\Type\ISchemable,
    Field\Type\ISortable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => _x('Image', 'field type', 'directories'),
            'default_renderer' => 'wp_gallery',
            'icon' => 'far fa-image',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $form = parent::fieldTypeSettingsForm($fieldType, $bundle, $settings, $parents, $rootParents);
        unset($form['allowed_extensions']);
        $form['allowed_files']['#type'] = 'hidden';
        $form['allowed_files']['#default_value'] = 'image';

        return $form;
    }
    
    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity, array $allValues)
    {
        // Cache attachment URLs/Title to prevent WP from issuing queries when displaying
        $sizes = ['full', 'large', 'medium', 'drts_thumbnail', 'drts_thumbnail_scaled', 'drts_icon', 'drts_icon_lg', 'drts_icon_xl'];
        foreach (array_keys($values) as $k) {
            foreach ($sizes as $size) {
                if (!$img = wp_get_attachment_image_src($values[$k]['attachment_id'], $size)) continue;
                
                $values[$k]['url'][$size] = $img[0];
            }
            // Title
            $values[$k]['title'] = get_the_title($values[$k]['attachment_id']);
            // Alt text
            $values[$k]['alt'] = get_post_meta($values[$k]['attachment_id'], '_wp_attachment_image_alt', true);
        }
    }
    
    public function fieldImageGetUrl($value, $size)
    {
        if (strpos($size, 'thumbnail') === 0) {
            $size = 'drts_' . $size;
            if (!isset($value['url'][$size])) {
                $size = 'drts_thumbnail';
            }
        }
        
        return isset($value['url'][$size]) ? $value['url'][$size] : null;
    }
    
    public function fieldImageGetFullUrl($value)
    {
        return isset($value['url']['full']) ? $value['url']['full'] : null;
    }
    
    public function fieldImageGetIconUrl($value, $size = null)
    {
        $_size = 'drts_icon';
        if ($size === 'lg' || $size === 'xl') {
            $_size .= '_' . $size;
        }
        return isset($value['url'][$_size]) ? $value['url'][$_size] : null;
    }
    
    public function fieldImageGetTitle($value)
    {
        return $value['title'];
    }
    
    public function fieldImageGetAlt($value)
    {
        return $value['alt'];
    }
    
    public function fieldQueryableInfo(Field\IField $field, $inAdmin = false)
    {
        return array(
            'example' => __('1 or 0', 'directories'),
            'tip' => __('Enter 1 for items with an image, 0 for items without any image.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle)
    {
        if ((bool)$paramStr) {
            $query->fieldIsNotNull($fieldName, 'attachment_id');
        } else {
            $query->fieldIsNull($fieldName, 'attachment_id');
        }
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('og:image');
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {      
        if (!$value = $entity->getFieldValue($field->getFieldName())) return;
        
        $ret = [];
        foreach ($value as $_value) {
            if ($img = wp_get_attachment_image_src($_value['attachment_id'], 'large')) {
                $ret[] = $img[0];
            }
        }
        return $ret;
    }
    
    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $ret = [];
        foreach ($values as $value) {
            if ($img = wp_get_attachment_image_src($value['attachment_id'], 'large')) {
                $ret[] = $img[0];
            }
        }
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }
    
    public function fieldSchemaProperties()
    {
        return array('thumbnail', 'contentUrl', 'image');
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        switch ($property) {
            case 'thumbnail':
                if ((!$value = $entity->getSingleFieldValue($field->getFieldName()))
                    || (!$url = $this->fieldImageGetUrl($value, 'thumbnail'))
                ) return;

                return [[
                    '@type' => 'ImageObject',
                    'contentUrl' => $url,
                    'name' => $this->fieldImageGetTitle($value),
                ]];
            case 'contentUrl':
            case 'image':
                if ((!$value = $entity->getSingleFieldValue($field->getFieldName()))
                    || (!$url = $this->fieldImageGetFullUrl($value))
                ) {
                    if (!$url = $this->_application->System_NoImage_url(null, $entity)) return;
                }
                return $url;
        }
    }

    public function fieldSortableOptions(Field\IField $field)
    {

    }

    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, 'DESC', 'attachment_id', null, 0);
    }
}