<?php
namespace SabaiApps\Directories\Component\Map\FieldType;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity;

interface ICoordinates
{
    public function mapCoordinates(IField $field, Entity\Type\IEntity $entity);
}