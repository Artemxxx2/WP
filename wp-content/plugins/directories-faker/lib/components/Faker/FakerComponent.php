<?php
namespace SabaiApps\Directories\Component\Faker;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Context;

class FakerComponent extends AbstractComponent implements System\IAdminRouter, IGenerators
{
    const VERSION = '1.3.108', PACKAGE = 'directories-faker';
    
    public static function description()
    {
        return 'Generates dummy content items.';
    }
    
    public function systemAdminRoutes()
    {
        $routes = [];
        foreach (array_keys($this->_application->Entity_BundleTypes()) as $bundle_type) {
            if ((!$admin_path = $this->_application->Entity_BundleTypeInfo($bundle_type, 'admin_path'))
                || isset($routes[$admin_path . '/generate']) // path added already
            ) continue;
            
            $routes += array(
                $admin_path . '/generate' => array(
                    'controller' => 'Generate',
                    'title_callback' => true,
                    'callback_path' => 'generate',
                ),
            );
        }
        return $routes;
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route){}

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'generate':
                return $this->_application->Filter(
                    'faker_admin_generate_title',
                    _x('Generate', 'generate content', 'directories-faker'),
                    array($context, $context->bundle, $titleType)
                );
        }
    }
    
    protected function _isFakerEnabled($bundle)
    {
        return !$this->_application->Entity_BundleTypeInfo($bundle, 'faker_disable');
    }
    
    public function fakerGetGeneratorNames()
    {
        $ret = array(
            'entity_title', 'entity_published', 'entity_activity', 'entity_featured', 'entity_author', 'entity_parent',
            'entity_term_parent', 'entity_terms',
            'field_string', 'field_text', 'field_boolean', 'field_number', 'field_email', 'field_phone', 'field_url',
            'field_range', 'field_choice', 'field_user', 'field_video', 'field_date', 'field_time', 'field_color', 'field_name',
            'companyname', 'picsum_photo', 'uifaces_photo', 'generated_photos_photo'
        );
        if ($this->_application->isComponentLoaded('WordPress')) {
            $ret[] = 'wp_image';
            $ret[] = 'wp_file';
            $ret[] = 'wp_post_content';
            $ret[] = 'wp_term_description';
            $ret[] = 'wp_post_parent';
        }
        if ($this->_application->isComponentLoaded('Voting')) {
            $ret[] = 'voting_vote';
        }
        if ($this->_application->isComponentLoaded('Payment')) {
            $ret[] = 'payment_plan';
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
    
    public function fakerGetGenerator($name)
    {
        if (strpos($name, 'entity_') === 0) {
            return new Generator\EntityGenerator($this->_application, $name);
        }
        if (strpos($name, 'field_') === 0) {
            return new Generator\FieldGenerator($this->_application, $name);
        }
        if ($name === 'companyname') {
            return new Generator\CompanyNameGenerator($this->_application, $name);
        }
        if ($name === 'picsum_photo') {
            return new Generator\PicsumPhotoGenerator($this->_application, $name);
        }
        if ($name === 'uifaces_photo') {
            return new Generator\UIFacesPhotoGenerator($this->_application, $name);
        }
        if ($name === 'generated_photos_photo') {
            return new Generator\GeneratedPhotosPhotoGenerator($this->_application, $name);
        }
        if (strpos($name, 'wp_') === 0) {
            return new Generator\WPGenerator($this->_application, $name);
        }
        if (strpos($name, 'file_') === 0) {
            return new Generator\FileGenerator($this->_application, $name);
        }
        if (strpos($name, 'payment_') === 0) {
            return new Generator\PaymentGenerator($this->_application, $name);
        }
        if ($name === 'voting_vote') {
            return new Generator\VotingGenerator($this->_application, $name);
        }
        if ($name === 'social_accounts') {
            return new Generator\SocialGenerator($this->_application, $name);
        }
    }
}