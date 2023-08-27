<?php
namespace SabaiApps\Directories\Component\Form\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class UploadFile extends Controller
{
    protected function _doExecute(Context $context)
    {
        if (!$context->getRequest()->isPostMethod()
            || (!$_file = @$_FILES['drts_form_upload'])
            || (!$form_build_id = $context->getRequest()->asStr('drts_form_build_id'))
            || (!$form_file_field_id = $context->getRequest()->asStr('drts_form_file_field_id'))
            || (!$token = $this->Form_UploadToken($form_build_id, $form_file_field_id))
        ) {
            $context->setBadRequestError();
            return;
        }
        
        if (!$storage = $this->getComponent('Form')->getFormStorage($form_build_id)) {
            $storage = [];
        }
        if (!isset($storage['form_upload_files'][$form_file_field_id])) {
            $storage['form_upload_files'][$form_file_field_id] = [];
        }
        
        if (!empty($token['max_num_files'])) {
            if (count($storage['form_upload_files'][$form_file_field_id]) > $token['max_num_files'] * 2) {
                $context->setError(__('You have already uploaded enough files!', 'directories'));
                return;
            }
        }
        
        try {
            $file = $this->Upload([
                'name' => $_file['name'],
                'type' => $_file['type'],
                'size' => $_file['size'],
                'tmp_name' => $_file['tmp_name'],
                'error' => $_file['error'],
            ], $token['upload_settings']);
        } catch (\Exception $e){
            $context->setError($e->getMessage());
            @unlink($_file['tmp_name']);
            return;
        }
        
        // Add file uploaded with this token
        $storage['form_upload_files'][$form_file_field_id][$file['saved_file_name']] = $file;
        $this->getComponent('Form')->setFormStorage($form_build_id, $storage);
        
        $this->_render($context, $file, $token);
        
        @unlink($_file['tmp_name']);
    }
    
    protected function _render(Context $context, array $file, array $token)
    {
        // Render response
        $context->addTemplate('system_list')->setAttributes(array('list' => array($file)));
    }
}