<?php
namespace SabaiApps\Directories\Component\Dashboard\Controller;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Context;

class EditPost extends Form\Controller
{    
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {
        if (!isset($formStorage['from_dashboard'])) {
            $formStorage['from_dashboard'] = $context->getRequest()->asBool('from_dashboard');
        }
        $this->_cancelUrl = $this->_getSuccessUrl($context, $formStorage);
        $this->_submitButtons['submit'] = array(
            '#btn_label' => __('Save Changes', 'directories-frontend'),
            '#btn_color' => 'primary',
            '#btn_size' => 'lg',
            '#attributes' => ['data-modal-title' => ''], // prevents modal title from changing on submit error
        );
        if ($formStorage['from_dashboard']) {
            $this->_ajaxOnSuccessRedirect = false;
            $this->_ajaxOnSuccess = 'function (result, target, trigger) {
    if (target.attr("id") === "drts-modal") {
        target.find(".drts-modal-close").click();
    } else {
        target.hide();
    }
    var oldRow = $("tr.drts-display--dashboard-row[data-entity-id=\'' . $context->entity->getId() . '\']"),
        newRow = $(result.post).attr("id", oldRow.attr("id")).find("td > .drts-display-element-header").remove().end();
    oldRow.replaceWith(newRow);
    if ($.effects && $.effects.effect.highlight) {
        newRow.find("> td").effect("highlight", {}, 1000);
    }
}';

        }
        $context->addTemplate('entity_form');
        
        return [
            '#enable_storage' => true,
            '#action' => $this->getComponent('Dashboard')->getPostsPanelUrl($context->entity, '/posts/' . $context->entity->getId(), [], true),
        ] + $this->Entity_Form($context->entity, array(
            'values' => $context->getRequest()->getParams(),
            'pre_render_display' => true,
            'wrap' => 'drts',
        ));
    }

    public function submitForm(Form\Form $form, Context $context)
    {
        $was_published = $context->entity->isPublished();
        $entity = $this->_saveEntity($context->entity, $form);
        $attr = [
            'post' => $this->_application->Display_Render(
                $entity,
                'dashboard_row',
                $entity,
                [
                    'tag' => 'tr',
                    'element_tag' => 'td',
                    'render_empty' => true,
                    'pre_render' => true,
                ]
            ),
        ];
        
        $context->setSuccess($this->_getSuccessUrl($context, $form->storage), $attr)
            ->addFlash(__('Your item has been updated successfully.', 'directories-frontend'), 'success', 60000);

        if ($was_published
            && !$entity->isPublished()
        ) {
            $info_msg = $this->Filter(
                'dashboard_edit_post_success_msg2',
                $this->H(__('We will review your submission and publish it on this site when it is approved.', 'directories-frontend')),
                [$entity]
            );
            $context->addFlash($info_msg, 'info', 60000);
        }

        $this->Action('dashboard_edit_post_success', [$entity]);
        
        return $entity;
    }
    
    protected function _saveEntity(Entity\Type\IEntity $entity, Form\Form $form, array $extraArgs = [])
    {
        $values = $form->values['drts'];
        
        // Make sure the parent entity can not be changed
        unset($values['entity_parent'], $values['parent']);

        $extra_args = [
            'entity_field_max_num_items' => $form->settings['#entity_field_max_num_items'],
            'dashboard_edit_post' => true,
        ];
        
        return $this->Entity_Save($entity, $values, $extra_args);
    }
    
    protected function _getSuccessUrl(Context $context, array $formStorage)
    {
        if (empty($formStorage['from_dashboard'])) {
            if ($url = $this->Filter('dashboard_edit_post_success_url', null, [$context->entity])) {
                return $url;
            }

            return $this->Entity_PermalinkUrl($context->entity);
        }
        
        return $this->getComponent('Dashboard')->getPostsPanelUrl($context->entity);
    }
}