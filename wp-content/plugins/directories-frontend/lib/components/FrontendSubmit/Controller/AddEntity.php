<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;

class AddEntity extends AbstractAddEntity
{
    protected function _getSteps(Context $context, array &$formStorage)
    {
        // Fetch submittable bundle options
        $submittable_bundles = $this->FrontendSubmit_Submission_bundles($context->bundle_type);

        if ($this->getUser()->isAnonymous()) {
            if (empty($submittable_bundles)
                || $this->_isGuestRedirectRequired($context, $formStorage)
            ) {
                $redirect_bundle = null;
                if (!empty($submittable_bundles)) {
                    $redirect_bundle = $context->bundle_type;
                }
                return $this->_redirectGuest($context, $formStorage, $redirect_bundle);
            }
        } else {
            if (empty($submittable_bundles)) {
                $context->setError(__('There is no submittable content.', 'drts'));
                return;
            }
        }

        $steps = parent::_getSteps($context, $formStorage);
        if (($bundle_name = $this->_hideSelectBundle($context, $formStorage))
            && ($bundle = $this->Entity_Bundle($bundle_name))
        ) {
            $formStorage['values']['select_bundle']['bundle'] = $bundle_name;
            $context->popInfo();
            $context->setInfo($bundle->getLabel('add'));
            return $steps;
        }
        return $steps + array(
            'select_bundle' => array('order' => 5),
        );
    }

    protected function _hideSelectBundle(Context $context, array &$formStorage)
    {
        if (!isset($formStorage['hide_select_bundle'])) {
            $bundles = $this->FrontendSubmit_Submission_bundles($context->bundle_type);
            if ($bundle = $context->getRequest()->asStr('bundle')) {
                if (isset($bundles[$bundle])) {
                    $formStorage['hide_select_bundle'] = $bundle;
                } else {
                    // Requested bundle is not submittable, so do not hide select bundle form
                    $formStorage['hide_select_bundle'] = false;
                }
            } else {
                $formStorage['hide_select_bundle'] = count($bundles) === 1 ? current(array_keys($bundles)) : false;
            }
        }
        return $formStorage['hide_select_bundle'];
    }

    public function _getFormForStepSelectBundle(Context $context, array &$formStorage)
    {
        return array(
            'bundle' => array(
                '#title' => __('Select content type', 'directories-frontend'),
                '#type' => 'radios',
                '#options' => $this->FrontendSubmit_Submission_bundles($context->bundle_type),
                '#required' => true,
                '#default_value_auto' => true,
            ),
        );
    }

    /*
     * @return SabaiApps\Directories\Component\Entity\Model\Bundle
     */
    protected function _getBundle(Context $context, array $formStorage)
    {
        if (!empty($formStorage['values']['select_bundle']['bundle'])) {
            return $this->Entity_Bundle($formStorage['values']['select_bundle']['bundle'], null, '', true);
        }

        throw new Exception\RuntimeException('No bundle selected!');
    }

    protected function _getPageTitle(Context $context, array $formStorage)
    {
        $bundle = $this->_getBundle($context, $formStorage);

        return sprintf(__('%s: %s'), $bundle->getGroupLabel(), $bundle->getLabel('add'));
    }

    protected function _getRedirectGuestUrlParams(Context $context, array $formStorage)
    {
        $ret = parent::_getRedirectGuestUrlParams($context, $formStorage);
        $ret[] = 'bundle';

        return $ret;
    }
}
