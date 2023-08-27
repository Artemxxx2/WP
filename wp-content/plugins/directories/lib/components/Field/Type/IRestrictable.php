<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;

interface IRestrictable
{
    public function fieldRestrictableOptions(IField $field);
    public function fieldRestrictableRestrict(IField $field, $value);
}