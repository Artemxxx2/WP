<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class AddMoreField extends ItemField
{    
    public function formFieldInit($name, array &$data, Form $form)
    {
        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
        
        return parent::formFieldInit($name, $data, $form);
    }
    
    public function formFieldRender(array &$data, Form $form)
    {
        $data['#markup'] = sprintf(
            '<button type="button" class="%1$sbtn %1$sbtn-outline-secondary %1$sbtn-sm drts-form-field-add" data-field-max-num="%2$d" data-field-next-index="%3$d"><i class="fas fa-plus fa-fw"></i> %4$s</button>',
            DRTS_BS_PREFIX,
            empty($data['#max_num']) ? 0 : $data['#max_num'],
            empty($data['#next_index']) ? 1 : $data['#next_index'],
            $this->_application->H(__('Add More', 'directories'))
        );
        parent::formFieldRender($data, $form);
    }
    
    public function preRenderCallback($form)
    {        
        $this->_application->getPlatform()->addJsFile('form-field-addmore.min.js', 'drts-form-field-addmore', array('drts-form'));
        $form->settings['#js_ready'][] = sprintf(
            'DRTS.Form.field.addmore("#%s");',
            $form->settings['#id']
        );
    }
}