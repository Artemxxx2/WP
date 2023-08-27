<?php
namespace SabaiApps\Directories\Component\Dashboard\Controller;

use SabaiApps\Directories\Component\FrontendSubmit;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;

class SubmitPost extends FrontendSubmit\Controller\AbstractSubmitEntity
{   
    protected function _getSteps(Context $context, array &$formStorage)
    {
        if (!isset($formStorage['from_dashboard'])) {
            $formStorage['from_dashboard'] = $context->getRequest()->asBool('from_dashboard');
        }
        return array('submit' => array('order' => 3));
    }
    
    public function _getFormForStepSubmit(Context $context, array &$formStorage)
    {
        $form = [];
        $form['#header'][] = '<div class="drts-bs-alert drts-bs-alert-info">' . $this->H(__('Press the button below to submit for review.', 'directories-frontend')) . '</div>';
        
        return $form;
    }
    
    public function _submitFormForStepSubmit(Context $context, Form\Form $form)
    {
        $bundle = $this->_getBundle($context, $form->storage);
        $status = $this->_getEntityStatus($context, $form, $bundle);
        $this->_updateSubmittedEntity($context, $status);
    }

    protected function _updateSubmittedEntity(Context $context, $status, array $values = [])
    {
        if (!$context->entity->isPublished()) { // keep published
            $_values = $values;
            if ($status !== $context->entity->getStatus()) {
                $_values['status'] = $status;
            }
            if (!empty($_values)) {
                $context->entity = $this->Entity_Save($context->entity, $_values);
            }
        }
        // Update status of translated posts
        foreach ($this->Entity_Translations($context->entity, false) as $_entity) {
            if (!$_entity->isPublished()) { // keep published
                $_values = $values;
                if ($status !== $_entity->getStatus()) {
                    $_values['status'] = $status;
                }
                if (!empty($_values)) {
                    $this->Entity_Save($_entity, $_values);
                }
            }
        }
    }
    
    protected function _getEntityStatus(Context $context, Form\Form $form, Entity\Model\Bundle $bundle)
    {
        if (!empty($bundle->info['public'])
            && !$this->HasPermission('entity_publish_' . $bundle->name)
        ) {
            $status = 'pending';
        } else {
            $status = 'publish';
        }
        return $this->Entity_Status($bundle->entitytype_name, $status);
    }
    
    protected function _complete(Context $context, array $formStorage)
    {
        $context->setSuccess($this->_getSuccessUrl($context, $formStorage));
        if (!$context->entity->isPublished()) {
            $context->addFlash(__('Your item has been submitted successfully. We will review your submission and publish it when it is approved.', 'directories-frontend'));
        } else {
            $context->addFlash(__('Your item has been submitted and published successfully.', 'directories-frontend'));
        }
    }
    
    protected function _getSuccessUrl(Context $context, array $formStorage)
    {
        if (empty($formStorage['from_dashboard'])) {
            if ($url = $this->Filter('dashboard_submit_post_success_url', null, [$context->entity])) {
                return $url;
            }

            return $this->Entity_PermalinkUrl($context->entity);
        }
        
        return $this->getComponent('Dashboard')->getPostsPanelUrl($context->entity);
    }
}