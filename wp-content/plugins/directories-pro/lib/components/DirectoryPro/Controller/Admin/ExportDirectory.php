<?php
namespace SabaiApps\Directories\Component\DirectoryPro\Controller\Admin;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;

class ExportDirectory extends Form\AbstractMultiStepController
{
    protected function _getSteps(Context $context, array &$formStorage)
    {
        return array('export' => []);
    }
    
    public function _getFormForStepExport(Context $context, array &$formStorage)
    {
        $context->addTemplate('system_progress');
        $context->download = true;
        $this->_ajaxSubmit = true;
        $this->_ajaxOnSubmit = $this->System_Progress_formSubmitJs('directory_export');
        $this->_ajaxOnSuccess = $this->System_Progress_formSuccessDownloadJs();
        $this->_ajaxOnSuccessRedirect = $this->_ajaxOnErrorRedirect = false;
        $this->_ajaxLoadingImage = $this->_ajaxModalHideOnSuccess = false; // prevent modal content/window from being cleared/closed
        $this->_submitButtons[] = [
            '#btn_label' => __('Export', 'directories-pro'),
            '#btn_color' => 'primary',
            '#btn_size' => 'lg',
            '#attributes' => ['data-modal-title' => 'false'],
        ];
        
        return [
            'filename' => [
                '#title' => __('File name', 'directories-pro'),
                '#type' => 'textfield',
                '#default_value' => $context->directory->name,
                '#field_suffix' => '.json',
                '#regex' => '/^[a-zA-Z0-9-_]+$/',
                '#required' => true,
                '#horizontal' => true,
            ],
            'pretty_print' => [
                '#title' => __('Pretty-print JSON', 'directories-pro'),
                '#type' => 'checkbox',
                '#horizontal' => true,
            ],
        ];
    }
    
    public function _submitFormForStepExport(Context $context, Form\Form $form)
    {
        try {
            $this->ValidateDirectory($this->getComponent('System')->getTmpDir(), true);
        } catch (\Exception $e) {
            throw new Exception\RuntimeException($e->getMessage());
        }
        
        $files = [];
        
        $progress = $this->System_Progress('directory_export')->start(null, __('Exporting... %3$s', 'directories-pro'));
        
        $file = rtrim($this->getComponent('System')->getTmpDir(), '/') . '/' . $form->values['filename'] . '.json';        
        if (!$file
            || false === ($fp = fopen($file, 'w+'))
        ) {
            throw new Exception\RuntimeException(sprintf('Failed opening file %s with write permission', $file));
        }
        
        // Export directory
        $directory = [
            'name' => $context->directory->name,
            'type' => $context->directory->type,
            'data' => $context->directory->data,
            'bundles' => [],
        ];
        foreach ($this->Entity_Bundles(null, 'Directory', $context->directory->name) as $bundle) {
            $directory['bundles'][$bundle->type]['info'] = $bundle->info;
            foreach ($this->DirectoryPro_ExportBundle($bundle) as $key => $arr) {
                $directory['bundles'][$bundle->type][$key] = $arr['data'];
            }
        }
        
        // Write to JSON file
        $json_encode_option = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        fwrite($fp, $this->JsonEncode(
            $this->Filter('directory_admin_export_directory', $directory, [$context->directory]),
            !empty($form->values['pretty_print']) ? $json_encode_option | JSON_PRETTY_PRINT : $json_encode_option
        ));
        fclose($fp);
        $files[] = $file;
        
        $progress->set($file);
        
        $progress->done();

        $form->storage['files'] = $files;
    }

    protected function _complete(Context $context, array $formStorage)
    {
        $success = $error = [];
        if (empty($formStorage['files'])) {
            $error[] = $this->H(__('Export failed.', 'directories-pro'));
        } else {
            $success[] = $this->H(__('Exported successfully.', 'directories-pro'));
            $download_file = basename($formStorage['files'][0]);
            if (count($formStorage['files']) > 1
                && class_exists('\ZipArchive', false)
            ) {
                $zip = new \ZipArchive();
                $zip_file = basename($formStorage['files'][0], '.csv') . '.zip';
                if (true !== $result = $zip->open(rtrim(dirname($formStorage['files'][0]), '/') . '/' . $zip_file, \ZipArchive::CREATE)) {
                    $error[] = 'Failed creating zip archive. Error: ' . $this->H($result);
                } else {
                    foreach ($formStorage['files'] as $file) {
                        $zip->addFile($file, basename($file));
                    }
                    $zip->close();
                    $download_file = $zip_file; // let user download zip file
                }
            }
        }
        
        $context->setSuccess(null, [
            'download_file' => isset($download_file) ? $download_file : null,
            'success' => $success,
            'error' => $error,
        ]);
    }
}