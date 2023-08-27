<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

interface IConditionable
{
    public function fieldConditionableInfo(IField $field, $isServerSide = false);
    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '');
    public function fieldConditionableMatch(IField $field, array $rule, array $values, IEntity $entity);
}