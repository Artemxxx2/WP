<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

trait QueryableNumberTrait
{
    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        return [
            'example' => '1,10',
            'tip' => __('Enter a single number for exact match, two numbers separated with a comma for range search.', 'directories'),
        ];
    }

    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        $params = $this->_queryableParams($paramStr, false);
        switch (count($params)) {
            case 0:
                break;
            case 1:
                if (strlen($params[0])) {
                    $query->fieldIs($fieldName, $params[0]);
                }
                break;
            default:
                if (strlen($params[0])) {
                    $query->fieldIsOrGreaterThan($fieldName, $params[0]);
                }
                if (strlen($params[1])) {
                    $query->fieldIsOrSmallerThan($fieldName, $params[1]);
                }
        }
    }
}