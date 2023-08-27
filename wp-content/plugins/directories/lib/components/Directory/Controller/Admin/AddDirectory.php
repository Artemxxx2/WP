<?php
namespace SabaiApps\Directories\Component\Directory\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Directory\Model\Directory;

class AddDirectory extends Form\Controller
{    
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {
        $context->addTemplate('system_progress');
        $this->_ajaxSubmit = true;
        $this->_ajaxOnSubmit = $this->System_Progress_formSubmitJs('directory_add');
        $this->_ajaxLoadingImage = false;
        $this->_submitButtons[] = array(
            '#btn_label' => __('Add Directory', 'directories'),
            '#btn_color' => 'success',
            '#attributes' => array('data-modal-title' => 'false'),
            '#btn_size' => 'lg',
        );
        $form = array(
            '#enable_storage' => true,
            'general' => array(
                '#weight' => 2,
                '#tree' => false,
                'label' => array(
                    '#type' => 'textfield',
                    '#title' => __('Directory label', 'directories'),
                    '#description' => __('Enter a label used for administration purpose only.', 'directories'),
                    '#max_length' => 255,
                    '#horizontal' => true,
                    '#weight' => 2,
                    '#states' => array(
                        'placeholder' => array(
                            '[name="type"]' => array('type' => 'selected', 'value' => true),
                        ),
                    ),
                    '#data' => [],
                ),
                'name' => array(
                    '#type' => 'textfield',
                    '#title' => __('Directory name', 'directories'),
                    '#description' => $message = __('Enter a machine readable name which may not be changed later. Only lowercase alphanumeric characters and underscores are allowed and may not start or end with an underscore.', 'directories'),
                    '#max_length' => 12,
                    '#regex' => '/^[a-z0-9]([a-z0-9_]*[a-z0-9])?$/',
                    '#regex_error_message' => $message,
                    '#horizontal' => true,
                    '#states' => array(
                        'slugify' => array(
                            'input[name="label"]' => array('type' => 'filled', 'value' => true),
                        ),
                    ),
                    '#element_validate' => array(array($this, '_validateName')),
                    '#weight' => 3,
                ),
                'icon' => array(
                    '#type' => 'iconpicker',
                    '#title' => __('Directory icon', 'directories'),
                    '#iconset' => 'dashicons',
                    '#horizontal' => true,
                    '#default_value' => 'dashicons dashicons-admin-post',
                    '#weight' => 10,
                    '#required' => true,
                ),
                'type' => array(
                    '#title' => __('Directory type', 'directories'),
                    '#description' => __('Select the type of directory. This may not be changed later.', 'directories'),
                    '#default_value' => 'directory',
                    '#type' => 'select',
                    '#horizontal' => true,
                    '#options' => [],
                    '#weight' => 1,
                    '#empty_value' => '',
                ),
            ),
            'settings' => array(
                '#tree' => true,
                '#weight' => 20,
            ),
        );
        foreach (array_keys($this->Directory_Types()) as $directory_type_name) {
            if (!$directory_type = $this->Directory_Types_impl($directory_type_name, true)) continue;

            $form['general']['type']['#options'][$directory_type_name] = $directory_type->directoryInfo('label');

            // Disable selecting this directory type if only one allowed and one already exists.
            if ($directory_type->directoryInfo('multiple') === false
                && $this->getModel('Directory', 'Directory')->type_is($directory_type_name)->count()
            ) {
                $form['general']['type']['#options_disabled'][] = $directory_type_name;
            }

            $form['general']['label']['#attributes']['data-placeholder-' . str_replace('_', '-', $directory_type_name)] = $directory_type->directoryInfo('label');

            // Add directory type specific settings
            $form['settings'][$directory_type_name] = $this->Directory_Types_settingsForm(
                $directory_type_name,
                [],
                array('settings', $directory_type_name),
                $this->_getSubimttedValues($context, $formStorage)
            );
            $form['settings'][$directory_type_name]['#states'] = array(
                'visible' => array(
                    '[name="type"]' => array('value' => $directory_type_name),
                ),
            );

            // Do not allow setting directory name if has a system name
            if ($directory_type->directoryInfo('name')) {
                $form['name']['#states']['invisible_or']['[name="type"]']['value'][] = $directory_type_name;
            }
        }
        // Hide directory type selection if only single option available
        if (count($form['general']['type']['#options']) === 1) {
            $form['general']['type']['#type'] = 'hidden';
            $form['general']['type']['#default_value'] = current(array_keys($form['general']['type']['#options']));
        }
        
        return $form;
    }
    
    public function _validateName(Form\Form $form, &$value, $element)
    {           
        $value = trim(trim($value), '_');
        if (!strlen($value)) {
            $form->setError(__('Directory name may not be empty.', 'directories'), $element);
            return;
        }
        if (in_array(strtolower($value), array('drts', 'add', 'settings', 'system'))
            || $this->getModel('Directory', 'Directory')->name_is($value)->count()
            || !$this->Filter('directory_validate_name', true, [$value])
        ) {
            $form->setError(__('The name may not be used or is already taken.', 'directories'), $element);
        }
    }
    
    public function submitForm(Form\Form $form, Context $context)
    {
        $values = $form->values;
        
        $progress = $this->System_Progress('directory_add')->start(3, __('Adding directory (%1$d/%2$d) ... %3$s', 'directories'));
        
        if (!$directory = $this->_createDirectory($form)) {
            $progress->done();
            return;
        }
        
        $progress->set('Directory initialized');
                
        if ($bundles = $this->_getBundles($form, $directory)) {        
            $this->getComponent('Entity')->createEntityBundles('Directory', $bundles, $directory->name);
            unset($bundles);
        }
        
        $progress->set('Content types initialized');
        
        $this->Action('directory_admin_directory_added', array($directory, $values));
        
        // Run upgrade process to notify directory slugs have been updated
        $this->System_Component_upgradeAll(array_keys($this->System_Slugs()));

        // Reload all routes
        $this->getComponent('System')->reloadAllRoutes();
        
        $progress->set('Components reloaded');
        
        // Clear available widgets cache
        $this->getPlatform()->deleteCache('system_widgets');
        
        $progress->done();
        
        $context->setSuccess('/directories/' . $directory->name)
            ->addFlash(__('Directory created.', 'directories'));
    }
    
    protected function _createDirectory(Form\Form $form)
    {
        $values = $form->values;
        if (!$directory_impl = $this->Directory_Types_impl($values['type'], true)) return;

        // Abort if directory type allows one directory only and one already exists.
        if ($directory_impl->directoryInfo('multiple') === false
            && $this->getModel('Directory', 'Directory')->type_is($values['type'])->count()
        ) return;

        $settings = empty($values['settings'][$values['type']]) ? [] : $values['settings'][$values['type']];
        $directory = $this->getModel('Directory', 'Directory')->create();
        $directory->name = ($name = $directory_impl->directoryInfo('name')) ? $name : $values['name'];
        $directory->type = $values['type'];
        $directory->data = array('label' => $values['label'], 'icon' => $values['icon'], 'settings' => $settings);
        $directory->markNew()->commit();
        
        return $directory;
    }
    
    protected function _getBundles(Form\Form $form, Directory $directory)
    {
        // Create bundles for each content type in the directory
        $bundles = [];
        $directory_type = $this->Directory_Types_impl($directory->type);
        $settings = $directory->data['settings'];
        foreach ($directory_type->directoryInfo('content_types') as $content_type) {
            if (!$content_type_info = $this->_application->Filter(
                'directory_content_type_info',
                $directory_type->directoryContentTypeInfo($content_type),
                array($content_type, isset($settings[$content_type]) ? $settings[$content_type] : [])
            )) continue;
            
            // Convert content type info to bundle type info
            $bundles[$directory->type . '__' . $content_type] = $this->Directory_Types_entityBundleTypeInfo($directory->type, $content_type_info);
        }
        return $bundles;
    }
}
