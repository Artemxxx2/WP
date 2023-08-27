<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;

abstract class AbstractSubmitEntity extends Form\AbstractMultiStepController
{
    protected function _getSubmitEntityForm(Context $context, array &$formStorage, $entityOrBundle, $btnLabel = null, $wrap = null)
    {
        $context->addTemplate('entity_form');
        $btn = is_array($btnLabel) ? $btnLabel : array('#btn_label' => $btnLabel);
        $this->_submitButtons[$context->currentStep] = $btn + array(
            '#btn_color' => 'primary',
            '#btn_size' => 'lg',
            '#attributes' => ['data-modal-title' => 'false'], // prevent modal title from changing
        );

        return $this->Entity_Form($entityOrBundle, $this->_getSubmitEntityFormOptions($context, $formStorage, $wrap));
    }

    protected function _getSubmitEntityFormOptions(Context $context, array &$formStorage, $wrap = null)
    {
        return [
            'values' => $this->_getSubimttedValues($context, $formStorage),
            'pre_render_display' => true,
            'wrap' => isset($wrap) ? $wrap : 'drts',
        ];
    }

    protected function _getBundle(Context $context, array $formStorage)
    {
        return $this->Entity_Bundle($context->entity->getBundleName(), null, '', true);
    }
}
