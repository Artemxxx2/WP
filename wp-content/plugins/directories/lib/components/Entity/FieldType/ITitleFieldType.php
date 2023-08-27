<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

interface ITitleFieldType
{
    public function entityFieldTypeGetTitle(IField $field, IEntity $entity);
}