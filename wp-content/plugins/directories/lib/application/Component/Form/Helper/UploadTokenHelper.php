<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;

class UploadTokenHelper
{    
    public function help(Application $application, $formBuildId, $formFieldId, array $token = null)
    {
        if (!$storage = $application->getComponent('Form')->getFormStorage($formBuildId)) {
            $storage = [];
        }
        if (!isset($storage['form_upload_tokens'])) {
            $storage['form_upload_tokens'] = [];
        }
        if (isset($token)) {
            $token += array(
                'max_num_files' => 0,
                'upload_settings' => [],
            );
            $storage['form_upload_tokens'][$formFieldId] = $token;
            $application->getComponent('Form')->setFormStorage($formBuildId, $storage);
        } else {
            $token = isset($storage['form_upload_tokens'][$formFieldId]) ? $storage['form_upload_tokens'][$formFieldId] : null;
        }
        
        return $token;
    }
}