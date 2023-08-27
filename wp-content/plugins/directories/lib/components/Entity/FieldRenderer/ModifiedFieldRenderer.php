<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class ModifiedFieldRenderer extends PublishedFieldRenderer
{
    protected function _getTimestamp(Field\IField $field, array &$settings, Entity\Type\IEntity $entity)
    {
        return $entity->getModified();
    }
}