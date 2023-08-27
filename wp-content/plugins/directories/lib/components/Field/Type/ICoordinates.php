<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

interface ICoordinates
{
    public function fieldLatitude(IField $field, IEntity $entity);
    public function fieldLongitude(IField $field, IEntity $entity);
}