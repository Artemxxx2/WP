<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;

interface ICopiable
{
    public function fieldCopyValues(IField $field, array $values, array &$allValue, $lang = null);
}