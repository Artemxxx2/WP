<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

interface ILabellable
{
    public function fieldLabellableLabels(IField $field, IEntity $entity);
}