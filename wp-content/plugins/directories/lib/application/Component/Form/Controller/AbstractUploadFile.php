<?php
namespace SabaiApps\Directories\Component\Form\Controller;

use SabaiApps\Directories\Context;

abstract class AbstractUploadFile extends UploadFile
{
    protected function _doExecute(Context $context)
    {
        $context->addTemplate('form_uploadfile2');
        parent::_doExecute($context);
        if ($context->isError()) {
            $context->error = $context->setView()->getErrorMessage();
        }
    }

    protected function _render(Context $context, array $file, array $token)
    {
        try {
            $context->files = array($this->_saveFile($file, $token));
        } catch (\Exception $e) {
            $context->error = $e->getMessage();
        }
    }
    
    abstract protected function _saveFile(array $file, array $token);
}