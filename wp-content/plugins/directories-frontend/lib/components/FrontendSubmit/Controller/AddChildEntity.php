<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class AddChildEntity extends AbstractAddEntity
{
    protected function _getSteps(Context $context, array &$formStorage)
    {
        $context->setErrorUrl($this->Entity_Url($context->entity));

        if ($this->getUser()->isAnonymous()) {
            if (!$this->Entity_IsRoutable($context->child_bundle, 'add', $context->entity)
                || $this->_isGuestRedirectRequired($context, $formStorage)
            ) {
                return $this->_redirectGuest($context, $formStorage, $context->child_bundle);
            }
        } else {
            if (!$this->Entity_IsRoutable($context->child_bundle, 'add', $context->entity)) return;
        }

        return parent::_getSteps($context, $formStorage);
    }

    public function _getFormForStepAdd(Context $context, array &$formStorage)
    {
        $this->_cancelUrl = $this->Entity_Url($context->entity);
        $form = parent::_getFormForStepAdd($context, $formStorage);
        $form['#action'] = $this->_getAddEntityUrl($context);

        return $form;
    }

    protected function _getAddEntityUrl(Context $context, array $params = [])
    {
        return $this->Entity_Url(
            $context->entity, '/' . $context->child_bundle->info['slug'] . (empty($context->child_bundle->info['public']) ? '_add' : '/add'),
            $params
        );
    }

    protected function _getBundle(Context $context, array $formStorage)
    {
        return $context->child_bundle;
    }

    protected function _getEntityValues(Context $context, Form\Form $form)
    {
        $values = parent::_getEntityValues($context, $form);
        unset($values['slug']); // this comes from the URL path
        unset($values[$context->entity->getType() . '_parent']);
        $values['parent'] = $context->entity->getId();

        return $values;
    }

    protected function _getParentEntity(Context $context, array $formStorage)
    {
        return $context->entity;
    }
}
