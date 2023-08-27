<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class CheckboxesField extends RadiosField
{
    protected $_type = 'checkbox';

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        parent::formFieldSubmit($value, $data, $form);

        if (!empty($data['#max_selection']) && count($value) > $data['#max_selection']) {
            $form->setError(sprintf(__('Maximum of %d selections allowed.', 'directories'), $data['#max_selection']), $data);
        }
    }
}