<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

interface IQueryable
{
    public function fieldQueryableInfo(IField $field, $inAdmin = false);
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle);
}