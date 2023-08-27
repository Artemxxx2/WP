<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\IField;

interface ILinkable
{
    public function fieldLinkableUrl(IField $field, IEntity $entity, $single = true);
}