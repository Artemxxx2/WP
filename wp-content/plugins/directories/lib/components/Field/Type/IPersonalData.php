<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

interface IPersonalData
{
    public function fieldPersonalDataExport(IField $field, IEntity $entity);
    public function fieldPersonalDataErase(IField $field, IEntity $entity);
}
