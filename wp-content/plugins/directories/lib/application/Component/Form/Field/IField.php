<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

interface IField
{
    public function formFieldInit($name, array &$data, Form $form);
    public function formFieldIsSubmittable(array $data, Form $form);
    public function formFieldSubmit(&$value, array &$data, Form $form);
    public function formFieldCleanup(array &$data, Form $form);
    public function formFieldRender(array &$data, Form $form);
}