<?php
namespace SabaiApps\Directories\Component\DirectoryPro\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class AddCustomTaxonomy extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {
        $this->_ajaxOnSuccess = sprintf(
            'function (result, target, trigger) {
    if (target.attr("id") === "drts-modal") {
        target.find(".drts-modal-close").click();
    } else {
        target.hide();
    }
    DRTS.DirectoryPro.onAddCustomTaxonomy("#%1$s", result);
}',
            $context->getRequest()->asStr('field_id')
        );
        $this->_ajaxOnSuccessRedirect = $this->_ajaxOnErrorRedirect = false;
        $this->_submitButtons[] = [
            '#btn_label' => __('Add Custom Taxonomy', 'directories-pro'),
            '#btn_color' => 'primary',
            '#btn_size' => 'lg',
        ];
        return [
            'label' => [
                '#type' => 'textfield',
                '#title' => __('Label', 'directories-pro'),
                '#horizontal' => true,
                '#placeholder' => __('(e.g. Features)'),
                '#required' => true,
            ],
            'label_singular' => [
                '#type' => 'textfield',
                '#title' => __('Singular label', 'directories-pro'),
                '#horizontal' => true,
                '#placeholder' => __('(e.g. Feature)'),
                '#required' => true,
            ],
            'name' => [
                '#type' => 'textfield',
                '#title' => _x('Name', 'taxonomy name', 'directories-pro'),
                '#horizontal' => true,
                '#placeholder' => __('(e.g. feature)'),
                '#required' => true,
                '#regex' => '/^[a-zA-Z0-9]([a-zA-Z0-9_]*[a-zA-Z0-9])?$/',
                '#max_length' => 15,
                '#regex_error_message' => __('Taxonomy name must start with an alphabet, may contain alphanumeric and underscore characters only, and may not end with an underscore.', 'directories-pro'),
                '#element_validate' => [function(Form\Form $form, &$value, $element) {
                    if (strpos($value, '__') !== false) {
                        $form->setError(__('Taxonomy name may not contain two consecutive underscores.', 'directories-pro'), $element);
                        return;
                    }
                    $value = strtolower($value);
                    if (($custom_taxonomies = $this->getComponent('DirectoryPro')->getConfig('custom_taxonomies'))
                        && isset($custom_taxonomies[$value])
                    ) {
                        $form->setError('Taxonomy name already exists.', $element);
                        return;
                    }
                }],
            ],
            'icon' => [
                '#type' => 'iconpicker',
                '#title' => __('Icon', 'directories-pro'),
                '#horizontal' => true,
                '#required' => true,
            ],
            'hierarchical' => [
                '#type' => 'checkbox',
                '#title' => __('Hierarchical', 'directories-pro'),
                '#horizontal' => true,
            ],
        ];
    }

    public function submitForm(Form\Form $form, Context $context)
    {
        $custom_taxonomy = $this->DirectoryPro_CustomTaxonomies_add(
            $form->values['name'],
            $form->values['label'],
            $form->values['label_singular'],
            $form->values['icon'],
            !empty($form->values['hierarchical'])
        );
        $context->setSuccess(null, $custom_taxonomy);
    }
}