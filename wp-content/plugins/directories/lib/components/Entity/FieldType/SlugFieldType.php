<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class SlugFieldType extends Field\Type\AbstractType implements
    Field\Type\IQueryable,
    Field\Type\IHumanReadable
{
    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Slug', 'directories'),
            'creatable' => false,
        ];
    }

    public function fieldQueryableInfo(Field\IField $field, $inAdmin = false)
    {
        if ($inAdmin) {
            $tip = __('Enter slugs separated with commas. Prefix with "-" to exclude, e.g. art,business,-computers.', 'directories');
            $example = 'art,business,-computers';
        } else {
            $tip = __('Enter slugs or "_current_" (for current post/term if any) separated with commas. Prefix with "-" to exclude, e.g. art,business,-_current_.', 'directories');
            $example = 'art,business,-computers,_current_';
        }
        return [
            'example' => $example,
            'tip' => $tip,
        ];
    }

    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle)
    {
        if (!$slugs = $this->_queryableParams($paramStr)) return;

        $include = $exclude = [];
        foreach ($slugs as $slug) {
            if (in_array($slug, ['_current_', '-_current_'])) {
                if ($entity = $this->_getCurrentEntity()) {
                    if ($slug === '-_current_') {
                        $exclude[] = $entity->getSlug();
                    } else {
                        $include[] = $entity->getSlug();
                    }
                }
            } else {
                if (strpos($slug, '-') === 0) {
                    $exclude[] = substr($slug, 1);
                } else {
                    $include[] = $slug;
                }
            }
        }
        if (!empty($include)) {
            $query->fieldIsIn($fieldName, $include);
        }
        if (!empty($exclude)) {
            $query->fieldIsNotIn($fieldName, $exclude);
        }
    }

    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        return $entity->getSlug();
    }
}