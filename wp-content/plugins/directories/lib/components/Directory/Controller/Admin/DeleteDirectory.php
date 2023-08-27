<?php
namespace SabaiApps\Directories\Component\Directory\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class DeleteDirectory extends Form\Controller
{    
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {        
        $this->_submitButtons['submit'] = array(
            '#btn_label' => __('Delete Directory', 'directories'),
            '#btn_color' => 'danger',
            '#btn_size' => 'lg',
        );
        if ($this->Directory_Types_impl($context->directory->type)->directoryInfo('multiple') !== false) {
            $this->_ajaxOnSuccessDelete = 'form.drts-directory-admin-directories tr[data-row-id=\'' . $this->H($context->directory->name) . '\']';
        }
        $form = [
            '#header' => [
                sprintf(
                    '<div class="%1$salert %1$salert-warning">%2$s</div>',
                    DRTS_BS_PREFIX,
                    $this->H(__('Are you sure you want to delete this directory?', 'directories'))
                )
            ],
        ];
        if ($context->directory->type !== 'user') {
            $form['delete_content'] = [
                '#type' => 'checkbox',
                '#title' => __('Delete directory content', 'directories'),
                '#horizontal' => true,
            ];
        } else {
            $form['delete_content'] = [
                '#type' => 'hidden',
                '#default_value' => 1,
            ];
        }

        return $form;
    }
    
    public function submitForm(Form\Form $form, Context $context)
    {
        $context->directory->markRemoved()->commit();
        $this->getComponent('Entity')->deleteEntityBundles('Directory', $context->directory->name, !empty($form->values['delete_content']));
        $context->setSuccess('/directories');
        
        // Clear available widgets cache
        $this->getPlatform()->deleteCache('system_widgets');
        
        $this->Action('directory_admin_directory_deleted', array($context->directory, $form->values));
        
        // Run upgrade process to notify directory slugs have been updated
        $this->System_Component_upgradeAll(array('Directory', 'FrontendSubmit'));
    }
}