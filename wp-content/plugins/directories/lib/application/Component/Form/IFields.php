<?php
namespace SabaiApps\Directories\Component\Form;

interface IFields
{
    public function formGetFieldTypes();
    public function formGetField($type);
}