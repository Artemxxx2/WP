<?php
namespace SabaiApps\Directories\Component\Voting\Controller\Admin;

use SabaiApps\Directories\Component\Form\Controller;
use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Context;

class ClearVotes extends Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $this->_submitButtons[] = [
            '#btn_label' => __('Clear All', 'directories'),
            '#btn_color' => 'danger',
            '#btn_size' => 'lg',
        ];
        $this->_ajaxOnSuccessDelete = '.drts-voting-' . $context->field_name . '-' . $context->entity->getId();

        return [
            '#header' => [
                sprintf(
                    '<div class="%1$salert %1$salert-warning">%2$s</div>',
                    DRTS_BS_PREFIX,
                    $this->H(__('Are you sure?', 'directories'))
                )
            ],
            'entity_id' => [
                '#type' => 'hidden',
                '#default_value' => $context->entity->getId(),
            ],
            'field_name' => [
                '#type' => 'hidden',
                '#default_value' => $context->field_name,
            ],
        ];
    }

    public function submitForm(Form $form, Context $context)
    {
        $this->getModel('Vote', 'Voting')
            ->fieldName_is($context->field_name)
            ->entityId_is($context->entity->getId())
            ->delete();
        $this->Entity_Save($context->entity, [$context->field_name => false]);
        $context->addFlash(__('All cleared!', 'directories'));
    }
}