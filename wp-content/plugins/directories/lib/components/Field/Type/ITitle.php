<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;

interface ITitle
{
    public function fieldTitle(IField $field, array $values);
}