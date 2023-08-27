<?php
namespace SabaiApps\Directories\Component\CSV;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Application;

class CSVComponent extends AbstractComponent implements
    System\IAdminRouter,
    IExporters,
    IImporters
{
    const VERSION = '1.3.108', PACKAGE = 'directories';
    
    public static function description()
    {
        return 'Lets you import and export content to and from CSV files.';
    }

    public function hasVarDir()
    {
        return true;
    }
    
    public function systemAdminRoutes()
    {
        $routes = [];
        foreach (array_keys($this->_application->Entity_BundleTypes()) as $bundle_type) {
            if ((!$admin_path = $this->_application->Entity_BundleTypeInfo($bundle_type, 'admin_path'))
                || isset($routes[$admin_path . '/import']) // path added already
            ) continue;
            
            $routes += array(
                $admin_path . '/import' => array(
                    'controller' => 'Import',
                    'title_callback' => true,
                    'callback_path' => 'import',
                ),
                $admin_path . '/export' => array(
                    'controller' => 'Export',
                    'title_callback' => true,
                    'callback_path' => 'export',
                ),
            );
        }
        return $routes;
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route){}

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'import':
                return $this->_application->Filter(
                    'csv_admin_import_title',
                    __('Import', 'directories'),
                    array($context, $context->bundle, $titleType)
                );
            case 'export':
                return $this->_application->Filter(
                    'csv_admin_export_title',
                    __('Export', 'directories'),
                    array($context, $context->bundle, $titleType)
                );
        }
    }
    
    protected function _isCsvEnabled($bundle)
    {
        return !$this->_application->Entity_BundleTypeInfo($bundle->type, 'csv_disable');
    }
    
    public function csvGetExporterNames()
    {
        $ret = array(
            'entity_id', 'entity_slug', 'entity_published', 'entity_author', 'entity_title', 'entity_parent',
            'entity_reference', 'entity_featured', 'entity_activity', 'entity_child_count',
            'entity_term_parent', 'entity_terms', 'entity_term_content_count',
            'field_string', 'field_text', 'field_boolean', 'field_number', 'field_choice', 'field_email', 'field_phone', 'field_price',
            'field_user', 'field_url', 'field_video', 'field_range', 'field_date', 'field_time', 'field_color', 'field_icon', 'field_name',
            'map_map'
        );
        if ($this->_application->isComponentLoaded('WordPress')) {
            $ret[] = 'wp_image';
            $ret[] = 'wp_file';
            $ret[] = 'wp_post_content';
            $ret[] = 'wp_term_description';
            $ret[] = 'wp_post_status';
            $ret[] = 'wp_post_parent';
        }
        if ($this->_application->isComponentLoaded('Voting')) {
            $ret[] = 'voting_vote';
        }
        if ($this->_application->isComponentLoaded('File')) {
            $ret[] = 'file_image';
            $ret[] = 'file_file';
        }
        if ($this->_application->isComponentLoaded('Social')) {
            $ret[] = 'social_accounts';
        }

        return $ret;
    }
    
    public function csvGetExporter($name)
    {
        if (strpos($name, 'entity_') === 0) {
            return new Exporter\EntityExporter($this->_application, $name);
        }
        if (strpos($name, 'field_') === 0) {
            return new Exporter\FieldExporter($this->_application, $name);
        }
        if (strpos($name, 'wp_') === 0) {
            return new Exporter\WPExporter($this->_application, $name);
        }
        if (strpos($name, 'voting_') === 0) {
            return new Exporter\VotingExporter($this->_application, $name);
        }
        if (strpos($name, 'file_') === 0) {
            return new Exporter\FileExporter($this->_application, $name);
        }
        if (strpos($name, 'social_') === 0) {
            return new Exporter\SocialExporter($this->_application, $name);
        }
        if (strpos($name, 'map_') === 0) {
            return new Exporter\MapExporter($this->_application, $name);
        }
    }
    
    public function csvGetImporterNames()
    {
        $ret = array(
            'entity_id', 'entity_slug', 'entity_published', 'entity_author', 'entity_title', 'entity_parent',
            'entity_reference', 'entity_featured', 'entity_activity', 'entity_child_count',
            'entity_term_parent', 'entity_terms', 'entity_term_content_count',
            'field_string', 'field_text', 'field_boolean', 'field_number', 'field_choice', 'field_email', 'field_phone', 'field_price',
            'field_user', 'field_url', 'field_video', 'field_range', 'field_date', 'field_time', 'field_color', 'field_icon', 'field_name',
            'map_map'
        );
        if ($this->_application->isComponentLoaded('WordPress')) {
            $ret[] = 'wp_image';
            $ret[] = 'wp_file';
            $ret[] = 'wp_post_content';
            $ret[] = 'wp_term_description';
            $ret[] = 'wp_post_status';
            $ret[] = 'wp_post_parent';
        }
        if ($this->_application->isComponentLoaded('Voting')) {
            $ret[] = 'voting_vote';
        }
        if ($this->_application->isComponentLoaded('File')) {
            $ret[] = 'file_image';
            $ret[] = 'file_file';
        }
        if ($this->_application->isComponentLoaded('Social')) {
            $ret[] = 'social_accounts';
        }

        return $ret;
    }
    
    public function csvGetImporter($name)
    {
        if (strpos($name, 'entity_') === 0) {
            return new Importer\EntityImporter($this->_application, $name);
        }
        if (strpos($name, 'field_') === 0) {
            return new Importer\FieldImporter($this->_application, $name);
        }
        if (strpos($name, 'wp_') === 0) {
            return new Importer\WPImporter($this->_application, $name);
        }
        if (strpos($name, 'voting_') === 0) {
            return new Importer\VotingImporter($this->_application, $name);
        }
        if (strpos($name, 'file_') === 0) {
            return new Importer\FileImporter($this->_application, $name);
        }
        if (strpos($name, 'social_') === 0) {
            return new Importer\SocialImporter($this->_application, $name);
        }
        if (strpos($name, 'map_') === 0) {
            return new Importer\MapImporter($this->_application, $name);
        }
    }
}