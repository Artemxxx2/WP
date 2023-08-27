<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;

interface ISortable
{
    public function fieldSortableOptions(IField $field);
    public function fieldSortableSort(Query $query, $fieldName, array $args = null);
}