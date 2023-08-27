<?php
namespace SabaiApps\Directories\Component\Dashboard\Controller;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;

class DeletePost extends Form\Controller
{    
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {        
        if (!isset($formStorage['from_dashboard'])) {
            $formStorage['from_dashboard'] = $context->getRequest()->asBool('from_dashboard');
        }
        $this->_submitButtons['submit'] = array(
            '#btn_label' => __('Delete', 'directories-frontend'),
            '#btn_color' => 'danger',
            '#attributes' => array('class' => 'drts-entity-btn-trash-' . str_replace('_', '-', $context->entity->getBundleType())),
        );
        if ($formStorage['from_dashboard']) {
            $this->_ajaxOnSuccessDelete = 'tr.drts-display[data-entity-id=\'' . $context->entity->getId() . '\']';
        }
        
        return array(
            '#enable_storage' => true,
            '#action' => $this->getComponent('Dashboard')->getPostsPanelUrl($context->entity, '/posts/' . $context->entity->getId() . '/delete', [], true),
            '#entity' => $context->entity,
            '#header' => array(
                sprintf(
                    '<div class="%1$salert %1$salert-warning" style="margin-bottom:0;">%2$s</div>',
                    DRTS_BS_PREFIX,
                    $this->H(__('Are you sure you want to delete this post?', 'directories-frontend'))
                )
            ),
        );
    }

    public function submitForm(Form\Form $form, Context $context)
    {   
        $this->Entity_Types_impl($context->entity->getType())
            ->entityTypeTrashEntities([$context->entity->getId() => $context->entity], $form->values);
        
        $context->setSuccess($this->_getSuccessUrl($context, $form->storage));
        if (empty($form->storage['from_dashboard'])) {
            $context->addFlash(__('Your item has been deleted successfully.', 'directories-frontend'));
        }

        $this->Action('dashboard_delete_post_success', [$context->entity]);
    }
    
    protected function _getSuccessUrl(Context $context, array $formStorage)
    {
        if (empty($formStorage['from_dashboard'])) {
            if (!$bundle = $this->Entity_Bundle($context->entity)) return;


            if ($url = $this->Filter('dashboard_delete_post_success_url', null, [$context->entity])) {
                return $url;
            }

            if (!empty($bundle->info['parent'])) {
                 // Redirect to parent entity page
                if ($parent_entity = $this->Entity_ParentEntity($context->entity)) {
                    return $this->Entity_PermalinkUrl($parent_entity);
                }
            }
            return $this->Url($bundle->getPath());
        }
        
        return $this->getComponent('Dashboard')->getPostsPanelUrl($context->entity);
    }
}
