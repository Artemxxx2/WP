<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;

interface IColumnable
{
    public function fieldColumnableInfo(IField $field);
    public function fieldColumnableColumn(IField $field, $value, $column = '');
}