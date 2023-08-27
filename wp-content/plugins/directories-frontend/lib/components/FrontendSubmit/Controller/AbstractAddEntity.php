<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Exception;

abstract class AbstractAddEntity extends AbstractSubmitEntity
{
    protected function _getSteps(Context $context, array &$formStorage)
    {
        // Save reference field name and ID to storage for later use if requested
        if (($entity_reference_field = $context->getRequest()->asStr('entity_reference_field'))
            && ($entity_reference_id = $context->getRequest()->asStr('entity_reference_id'))
        ) {
            $formStorage['entity_reference_field'] = $entity_reference_field;
            $formStorage['entity_reference_id'] = $entity_reference_id;
        }

        return array(
            'add' => array('order' => 10),
        );
    }

    protected function _getPageTitle(Context $context, array $formStorage){}

    public function _getFormForStepAdd(Context $context, array &$formStorage)
    {
        $bundle = $this->_getBundle($context, $formStorage);

        // Check if submission is restricted
        if (!$this->_isSubmitAllowed($context, $formStorage, $bundle)) {
            $error_message = sprintf(
                __('You have already reached the maximum number of %1$s allowed.', 'directories-frontend'),
                $bundle->getLabel()
            );
            $context->setError(
                $this->Filter('frontendsubmit_submit_restriction_error_message', $error_message, [$bundle]),
                ($url = $context->getErrorUrl()) ? $url : $bundle->getPath()
            );
            return false;
        }

        // Assign custom page title if any
        $context->setTitle(($title = $this->_getPageTitle($context, $formStorage)) ? $title : null);

        if (!$entity_or_bundle = $this->_getEntity($context, $formStorage)) {
            $entity_or_bundle = $bundle;
        }
        $form = $this->_getSubmitEntityForm(
            $context,
            $formStorage,
            $entity_or_bundle,
            $this->_getSubmitButtonForStepAdd($context, $formStorage)
        );

        // Remove reference type field that will be populated automatically during save
        if (!empty($formStorage['entity_reference_field'])
            && isset($form['drts'][$formStorage['entity_reference_field']])
        ) {
            unset($form['drts'][$formStorage['entity_reference_field']]);
        }

        return $form;
    }

    public function _submitFormForStepAdd(Context $context, Form $form)
    {
        $values = $this->_getEntityValues($context, $form);
        $bundle = $this->_getBundle($context, $form->storage);

        // Set referencing entity if any
        if (!empty($form->storage['entity_reference_field'])
            && $this->Entity_Field($bundle, $form->storage['entity_reference_field']) // Make sure field belongs to current bundle
        ) {
            $values[$form->storage['entity_reference_field']] = $form->storage['entity_reference_id'];
        }

        // Set max num items if any
        $extra_args = [];
        $extra_args['entity_field_max_num_items'] = $form->settings['#entity_field_max_num_items'];

        // Save
        if (!$entity = $this->_getEntity($context, $form->storage)) {
            $bundle = $this->_getBundle($context, $form->storage);
            // Create entity and save entity id into session for later use
            $entity = $this->Entity_Save($bundle->name, array('status' => $this->_getEntityStatus($context, $form, $bundle)) + $values, $extra_args);
            $form->storage['entity_id'] = $entity->getId();
        } else {
            $entity = $this->Entity_Save($entity, $values, $extra_args);
        }
        $form->settings['#entity'] = $entity;
    }

    protected function _getSubmitButtonForStepAdd(Context $context, array &$formStorage){}

    protected function _getEntityStatus(Context $context, Form $form, Bundle $bundle)
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

    protected function _getEntityValues(Context $context, Form $form)
    {
        $values = $form->values['drts'];
        if ($this->getUser()->isAnonymous()
            && $this->getComponent('FrontendSubmit')->isCollectGuestInfo()
        ) {
            if (!empty($form->storage['_guest'])) {
                $guest_info = $form->storage['_guest'];
                $values['frontendsubmit_guest'] = [
                    'name' => isset($guest_info['name']) ? $guest_info['name'] : '',
                    'email' => isset($guest_info['email']) ? $guest_info['email'] : '',
                    'url' => isset($guest_info['url']) ? $guest_info['url'] : '',
                ];
            }
        }

        return $values;
    }

    protected function _complete(Context $context, array $formStorage)
    {
        $entity = $this->_getEntity($context, $formStorage);

        // Set cookie to track guest user
        if ($this->getUser()->isAnonymous()) {
            $this->FrontendSubmit_GuestAuthorCookie($entity);
        }

        // Set success redirection URL
        $success_url = $this->Filter(
            'frontendsubmit_add_entity_success_url',
            $orig_success_url = $this->_getSuccessUrl($context, $formStorage, $entity),
            [$entity]
        );
        $context->setSuccess($success_url);

        // Only add flash when redirecting to original success URL
        if ($orig_success_url === $success_url) {
            $success_msg = $this->Filter(
                'frontendsubmit_add_entity_success_msg',
                $this->H(__('Your item has been submitted successfully.', 'directories-frontend')),
                [$entity]
            );
            $context->addFlash($success_msg, 'success', 60000);

            // Add extra flash if entity is not published
            if (!$entity->isPublished()) {
                $info_msg = $this->Filter(
                    'frontendsubmit_add_entity_success_msg2',
                    $this->H(__('We will review your submission and publish it on this site when it is approved.', 'directories-frontend')),
                    [$entity]
                );
                $context->addFlash($info_msg, 'info', 60000);
            }
        }
    }

    protected function _getSuccessUrl($context, $formStorage, IEntity $entity)
    {
        // Redirect to URL of referenced entity if any
        if (!empty($formStorage['entity_reference_id'])
            && ($referenced_entity = $this->Entity_Entity($entity->getType(), $formStorage['entity_reference_id']))
        ) {
            return $this->Entity_PermalinkUrl($referenced_entity);
        }

        if (!$bundle = $this->Entity_Bundle($entity)) {
            return $this->getPlatform()->getSiteUrl(); // failed fetching bundle, redirect to site URL
        }

        // If not published, redirect to URL of parent entity if any or to dashboard page if logged in, otherwise to main index page
        if (!$entity->isPublished()
            || empty($bundle->info['public'])
        ) {
            if (!empty($bundle->info['parent'])) {
                if ($parent_entity = $this->_getParentEntity($context, $formStorage)) {
                    return $this->Entity_PermalinkUrl($parent_entity); // redirect to parent entity
                }
            }

            if ($this->getUser()->isAnonymous()) {
                return $this->Url($bundle->getPath()); // redirect to main index
            }
            return $this->Url('/' . $this->getComponent('Dashboard')->getSlug('dashboard')); // redirect to dashboard
        }
        return $this->Entity_PermalinkUrl($entity);
    }

    protected function _getEntity(Context $context, array $formStorage)
    {
        return (!empty($formStorage['entity_id'])
            && ($entity = $this->Entity_Entity($this->_getBundle($context, $formStorage)->entitytype_name, $formStorage['entity_id']))
        ) ? $entity : null;
    }

    protected function _isGuestInfoRequired(Context $context, array &$formStorage)
    {
        if ((!$guest_info = $context->getRequest()->get('_guest')) // Always check for new guest info
            || !is_array($guest_info)
        ) {
            if (isset($formStorage['_guest'])) return false; // Already processed

            $guest_info = [];
        }

        if ($this->callHelper('FrontendSubmit_Submission_isGuestInfoRequired', [&$guest_info])) return true;

        $formStorage['_guest'] = $guest_info;
        return false;
    }

    protected function _isGuestRedirectRequired(Context $context, array &$formStorage)
    {
        if ($this->_isGuestInfoRequired($context, $formStorage)) return true;

        if (isset($formStorage['_guest'])) return false; // came by clicking "Continue as Guest"

        $ret = $this->getComponent('FrontendSubmit')->isLoginFormEnabled()
            || $this->getComponent('FrontendSubmit')->isRegisterFormEnabled();

        return $this->Filter('frontendsubmit_redirect_guest', $ret, [$context, $formStorage]);
    }

    protected function _getRedirectGuestUrlParams(Context $context, array $formStorage)
    {
        return [];
    }

    protected function _redirectGuest(Context $context, array $formStorage, $bundle = null)
    {
        if (!$this->getUser()->isAnonymous()) return;

        $params = [];
        // Add extra params if any
        if ($param_names = $this->_getRedirectGuestUrlParams($context, $formStorage)) {
            foreach ($param_names as $param) {
                if (isset($_GET[$param])) {
                    $params[$param] = $_GET[$param];
                }
            }
        }
        if (isset($bundle)) {
            $params['redirect_action'] = 'add';
            if ($bundle instanceof Bundle) {
                $params['redirect_bundle'] = $bundle->name;
                if (!empty($bundle->info['parent'])
                    && ($parent_entity = $this->_getParentEntity($context, $formStorage))
                ) {
                    $params['redirect_entity'] = $parent_entity->getId();
                }
            } else {
                $params['redirect_bundle_type'] = $bundle;
            }
        }
        $context->setUnauthorizedError($this->_getAddEntityUrl($context, $params));
    }

    protected function _getAddEntityUrl(Context $context, array $params = [])
    {
        return $this->Url((string)$context->getRoute(), $params, '', '&');
    }

    protected function _isSubmitAllowed(Context $context, array $formStorage, Bundle $bundle)
    {
        if ($this->getUser()->isAnonymous()) {
            $identity = isset($formStorage['_guest']['email']) ? $formStorage['_guest']['email'] : '';
        } else {
            $identity = $this->getUser()->getIdentity();
        }
        $parent_entity_id = ($parent_entity = $this->_getParentEntity($context, $formStorage)) ? $parent_entity->getId() : null;
        return $this->FrontendSubmit_Restrictors_isAllowed($bundle, $identity, $parent_entity_id);
    }

    /**
     * @param Context $context
     * @param array $formStorage
     * @return IEntity
     */
    protected function _getParentEntity(Context $context, array $formStorage){}
}