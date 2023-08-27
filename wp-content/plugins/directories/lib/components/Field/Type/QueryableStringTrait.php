<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

trait QueryableStringTrait
{
    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        return array(
            'example' => 'xxx',
            'tip' => __('Enter "%xxx" for starts-with match, "xxx%" for ends-with match, "%xxx%" for partial match, otherwise exact match.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        $starts = $ends = $contains = false;
        if (strpos($paramStr, '%') === 0) {
            $starts = true;
            $paramStr = substr($paramStr, 1);
        }
        if (substr($paramStr, -1) === '%') {
            $paramStr = substr($paramStr, 0, -1);
            if ($starts) {
                $starts = false;
                $contains = true;
            } else {
                $ends = true;
            }
        }
        if ($starts) {
            $query->fieldStartsWith($fieldName, $paramStr);
        } elseif ($ends) {
            $query->fieldEndsWith($fieldName, $paramStr);
        } elseif ($contains) {
            $query->fieldContains($fieldName, $paramStr);
        } else {
            $query->fieldIs($fieldName, $paramStr);
        }
    }
}