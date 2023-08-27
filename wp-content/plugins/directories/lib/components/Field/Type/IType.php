<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

interface IType
{
    public function fieldTypeInfo($key = null);
    public function fieldTypeSettingsForm($fieldType, Bundle $bundle, array $settings, array $parents = [], array $rootParents = []);
    public function fieldTypeSchema();
    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = []);
    public function fieldTypeOnLoad(IField $field, array &$values, IEntity $entity, array $allValues);
    public function fieldTypeDefaultValueForm($fieldType, Bundle $bundle, array $settings, array $parents = []);
}