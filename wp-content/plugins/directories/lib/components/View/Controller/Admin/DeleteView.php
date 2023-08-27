<?php
namespace SabaiApps\Directories\Component\View\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class DeleteView extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        if ($context->view->name === $this->getComponent($context->bundle->component)->getConfig('view', $context->bundle->type, 'name')) {
            $this->_submitable = false;
            return array(
                '#header' => array(
                    sprintf(
                        '<div class="%1$salert %1$salert-warning">%2$s</div>',
                        DRTS_BS_PREFIX,
                        $this->H(__('Default view may not be deleted.', 'directories'))
                    )
                ),
            );
        }
        
        $this->_cancelUrl = dirname(dirname($context->getRoute()));
        $this->_submitButtons['submit'] = [
            '#btn_label' => __('Delete View', 'directories'),
            '#btn_color' => 'danger',
            '#btn_size' => 'lg',
        ];
        // Highlight and remove row on success
        $this->_ajaxOnSuccessDelete = 'form.drts-view-admin-views tr[data-row-id=\'' . $context->view->id . '\']';
        
        return array(
            '#header' => array(
                sprintf(
                    '<div class="%1$salert %1$salert-warning">%2$s</div>',
                    DRTS_BS_PREFIX,
                    $this->H(__('Are you sure you want to delete this view?', 'directories'))
                )
            ),
        );
    }
    
    public function submitForm(Form\Form $form, Context $context)
    {
        $context->view->markRemoved()->commit();
        $context->setSuccess();
    }
}