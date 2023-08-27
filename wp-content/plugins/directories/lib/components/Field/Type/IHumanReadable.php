<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

interface IHumanReadable
{
    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null);
}