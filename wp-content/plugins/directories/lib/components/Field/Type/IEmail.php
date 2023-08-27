<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

interface IEmail
{
    public function fieldEmailAddress(IField $field, IEntity $entity, $single = true);
}