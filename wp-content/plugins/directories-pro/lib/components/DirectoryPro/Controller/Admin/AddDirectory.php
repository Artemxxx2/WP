<?php
namespace SabaiApps\Directories\Component\DirectoryPro\Controller\Admin;

use SabaiApps\Directories\Component\Directory;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;

class AddDirectory extends Directory\Controller\Admin\AddDirectory
{
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {
        $form = parent::_doGetFormSettings($context, $formStorage);
        $form['general']['method'] = array(
            '#weight' => -1,
            '#title' => 'Choose a method',
            '#type' => 'radios',
            '#options' => array(
                'configure' => 'Configure a new directory',
                'import' => 'Import from a file',
            ),
            '#options_description' => array(
                'configure' => 'Enter configuration details of your directory.',
                'import' => 'Load configuration details from an exported JSON file.',
            ),
            '#default_value' => 'configure',
            '#horizontal' => true,
        );
        $form['settings']['#states'] = array(
            'visible' => array(
                '[name="method"]' => array('value' => 'configure'),
            ),
        );
        foreach (array('label', 'name', 'type') as $key) {
            $form['general'][$key]['#required'] = array(__CLASS__, '_isDirectoryInfoRequired');
            $form['general'][$key]['#states']['visible'] = array(
                '[name="method"]' => array('value' => 'configure'),
            );
        }
        $form['general']['import'] = array(
            '#tree' => false,
            '#states' => array(
                'visible' => array(
                    '[name="method"]' => array('value' => 'import'),
                ),
            ),
            'json' => array(
                '#type' => 'file',
                '#title' => __('JSON configuration file', 'directories-pro'),
                '#upload_dir' => $this->getComponent('System')->getTmpDir(),
                '#allowed_extensions' => array('json'),
                '#required' => array(__CLASS__, '_isJsonRequired'),
                '#multiple' => false,
                '#horizontal' => true,
            ),
            'custom_label' => array(
                '#type' => 'textfield',
                '#title' => __('Directory label', 'directories-pro'),
                '#description' => __('Enter a new label for the directory to import. Leave the field blank to use the label defined in the JSON configuration file.'),
                '#max_length' => 255,
                '#horizontal' => true,
            ),
            'custom_name' => array(
                '#type' => 'textfield',
                '#title' => __('Directory name', 'directories-pro'),
                '#description' => __('Enter a new machine readable name for the directory to import. Leave the field blank to use the name defined in the JSON configuration file.'),
                '#max_length' => 12,
                '#regex' => '/^[a-z0-9_]+$/',
                '#horizontal' => true,
            ),
        );

        return $form;
    }

    public static function _isDirectoryInfoRequired(Form\Form $form)
    {
        return $form->getValue('method') === 'configure';
    }

    public static function _isJsonRequired(Form\Form $form)
    {
        return $form->getValue('method') === 'import';
    }

    public function _validateName(Form\Form $form, &$value, $element)
    {
        if ($form->values['method'] !== 'configure') return;

        parent::_validateName($form, $value, $element);
    }

    protected function _createDirectory(Form\Form $form)
    {
        switch ($form->values['method']) {
            case 'configure':
                return parent::_createDirectory($form);
            case 'import':
                $values = $form->values;
                if (empty($values['json']['saved_file_path'])
                    || false === ($json = file_get_contents($values['json']['saved_file_path']))
                ) {
                    $form->setError(__('JSON configuration file was not uploaded.', 'directories-pro'));
                    return;
                }
                if (!$decoded = json_decode($json, true)) {
                    $form->setError(__('Invalid JSON data', 'directories-pro'), 'json');
                    return;
                }
                if (isset($values['custom_name']) && strlen($values['custom_name'])) {
                    $decoded['name'] = $values['custom_name'];
                    $element = 'custom_name';
                } else {
                    $element = 'json';
                }
                parent::_validateName($form, $decoded['name'], $element);
                if ($form->hasError()) return;

                if (isset($values['custom_label'])
                    && strlen($values['custom_label'])
                ) {
                    $decoded['data']['label'] = $values['custom_label'];
                }
                $form->values['data'] = $decoded;
                $directory = $this->getModel('Directory', 'Directory')->create();
                $directory->name = $decoded['name'];
                $directory->type = $decoded['type'];
                $directory->data = $decoded['data'];
                return $directory->markNew()->commit();
            default:
                throw new Exception\RuntimeException('Invalid method requested for adding a directory.');
        }
    }

    protected function _getBundles(Form\Form $form, Directory\Model\Directory $directory)
    {
        $bundles = parent::_getBundles($form, $directory);
        if ($form->values['method'] === 'import') {
            $data = $form->values['data'];
            $_bundles = [];
            foreach (array_keys($data['bundles']) as $bundle_type) {
                if (empty($data['bundles'][$bundle_type]['info'])) continue;

                $_bundles[$bundle_type] = $data['bundles'][$bundle_type]['info'];
                $_bundles[$bundle_type] += $data['bundles'][$bundle_type];
                if (isset($bundles[$bundle_type])) {
                    $_bundles[$bundle_type] += $bundles[$bundle_type];
                }
            }
            $bundles = $_bundles;
        }
        return $bundles;
    }
}
