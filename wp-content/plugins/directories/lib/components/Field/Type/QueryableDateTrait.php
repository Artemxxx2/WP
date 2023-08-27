<?php

namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

trait QueryableDateTrait
{
    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        return [
            'example' => '12/31/2017,28-3-99,now',
            'tip' => __('Enter a single date string for exact date match, two date strings separated with a comma for date range search.', 'directories'),
        ];
    }

    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        $params = $this->_queryableParams($paramStr, false);
        switch (count($params)) {
            case 0:
                break;
            case 1:
                if (strlen($params[0])
                    && ($time = strtotime($params[0]))
                ) {
                    $query->fieldIs($fieldName, $this->_application->getPlatform()->getSiteToSystemTime($time));
                }
                break;
            default:
                if (strlen($params[0])
                    && ($time = strtotime($params[0]))
                ) {
                    $query->fieldIsOrGreaterThan($fieldName, $this->_application->getPlatform()->getSiteToSystemTime($time));
                }
                if (strlen($params[1])
                    && ($time = strtotime($params[1]))
                ) {
                    $query->fieldIsOrSmallerThan($fieldName, $this->_application->getPlatform()->getSiteToSystemTime($time));
                }
        }
    }
}