<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;

interface IConditionable
{
    public function fieldFilterConditionableInfo(IField $field);
    public function fieldFilterConditionableRule(IField $field, $filterName, array $settings, $compare, $value = null, $name = '');
}

