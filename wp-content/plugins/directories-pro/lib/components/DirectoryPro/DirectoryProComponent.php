<?php
namespace SabaiApps\Directories\Component\DirectoryPro;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class DirectoryProComponent extends AbstractComponent implements
    Field\IRenderers,
    System\IAdminRouter,
    System\IWidgets,
    Display\ILabels,
    Field\IWidgets,
    Field\ITypes,
    Entity\IBundleTypes
{
    const VERSION = '1.3.108', PACKAGE = 'directories-pro';
    
    public static function interfaces()
    {
        return ['Payment\IFeatures'];
    }
    
    public static function description()
    {
        return 'Adds features to build a local business directory.';
    }
    
    public function onCorePlatformWordPressInit()
    {
        if ($this->_application->getPlatform()->getName() === 'WordPress') {
            new WordPressHomePage($this->_application);
        }
    }
    
    public function systemAdminRoutes()
    {
        return [
            '/directories/add' => [
                'controller' => 'AddDirectory',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'add_directory',
                'callback_component' => 'Directory',
                'type' => Application::ROUTE_MENU,
                'priority' => 5,
            ],
            '/directories/:directory_name/export' => [
                'controller' => 'ExportDirectory',
                'title_callback' => true,
                'callback_path' => 'export_directory',
            ],
            '/directories/:directory_name/content_types/:bundle_name/export_bundle' => [
                'controller' => 'ExportBundle',
                'title_callback' => true,
                'callback_path' => 'export_bundle',
            ],
            '/_drts/directorypro/add_custom_taxonomy' => [
                'controller' => 'AddCustomTaxonomy',
                'callback_path' => 'add_custom_taxonomy',
            ],
        ];
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route){}

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'export_directory':
            case 'export_bundle':
                return __('Export', 'directories-pro');
        }
    }
    
    public function systemGetWidgetNames()
    {
        $has_directory = false;
        foreach ($this->_application->Entity_Bundles(null, 'Directory') as $bundle) {
            if (!empty($bundle->info['public'])
                && empty($bundle->info['is_taxonomy'])
                && empty($bundle->info['parent'])
            ) {
                $has_directory = true;
                break;
            }
        }
        return $has_directory ? ['directory_filters'] : [];
    }
    
    public function systemGetWidget($name)
    {
        if ($name === 'directory_filters') {
            return new SystemWidget\FiltersSystemWidget($this->_application, $name);
        }
    }

    public function fieldGetTypeNames()
    {
        return ['directory_opening_hours'];
    }

    public function fieldGetType($name)
    {
        switch ($name) {
            case 'directory_opening_hours':
                return new FieldType\OpeningHoursFieldType($this->_application, $name);
        }
    }

    public function fieldGetRendererNames()
    {
        return ['directory_opening_hours', 'directory_screenshot'];
    }
    
    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'directory_opening_hours':
                return new FieldRenderer\OpeningHoursFieldRenderer($this->_application, $name);
            case 'directory_screenshot':
                return new FieldRenderer\ScreenshotFieldRenderer($this->_application, $name);
        }
    }

    public function fieldGetWidgetNames()
    {
        return ['directory_opening_hours'];
    }

    public function fieldGetWidget($name)
    {
        switch ($name) {
            case 'directory_opening_hours':
                return new FieldWidget\OpeningHoursFieldWidget($this->_application, $name);
        }
    }

    public function displayGetLabelNames(Entity\Model\Bundle $bundle)
    {
        if (!$this->_application->Entity_Field_options($bundle, ['type' => 'directory_opening_hours'])) return [];

        return ['directory_open_now'];
    }
    
    public function displayGetLabel($name)
    {
        switch ($name) {
            case 'directory_open_now':
                return new DisplayLabel\OpenNowDisplayLabel($this->_application, $name);
        }
    }
    
    public function paymentGetFeatureNames()
    {
        return ['directory_photos'];
    }
    
    public function paymentGetFeature($name)
    {
        switch ($name) {
            case 'directory_photos':
                return new PaymentFeature\PhotosPaymentFeature($this->_application, $name);
        }
    }
    
    public function onDirectoryTypesFilter(&$types)
    {
        $types['directory'] = $this->_name;
    }
    
    public function directoryGetType($name)
    {
        return new DirectoryType\DirectoryType($this->_application, $name);
    }
    
    public function onEntityFieldValuesLoaded($entity, $bundle, $cache)
    {
        if (!$cache
            || $bundle->type !== 'directory__listing'
            || !$this->_application->isComponentLoaded('Payment')
            || empty($bundle->info['payment_enable'])
            || (!$directory_photos = $entity->getFieldValue('directory_photos'))
        ) return;
        
        $features = $this->_application->Payment_Plan_features($entity);

        if (!empty($features[0]['directory_photos']['unlimited'])) return;
                    
        if (!isset($features[0]['directory_photos']['num'])) {
            $max_num_allowed = 5;
        } else {
            $max_num_allowed = empty($features[0]['directory_photos']['num']) ? 0 : $features[0]['directory_photos']['num'];
        }
        if (!empty($features[1]['directory_photos']['num'])) { // any additional num of photos allowed?
            $max_num_allowed += $features[1]['directory_photos']['num'];
        }
        
        $current_num = count($directory_photos);
        if ($current_num <= $max_num_allowed) return;
                    
        $entity->setFieldValue('directory_photos', array_slice($directory_photos, 0, $max_num_allowed));
    }
    
    public function onDirectoryAdminDirectoryLinksFilter(&$links, $directory)
    {
        $links['settings']['link'][98] = '';
        $links['settings']['link'][99] = $this->_application->LinkTo(
            $title = __('Export', 'directories-pro'),
            $this->_application->Url('/directories/' . $directory->name . '/export'),
            ['btn' => true, 'container' => 'modal', 'modalSize' => 'xl'],
            [
                'data-modal-title' => $title . ' - ' . $directory->getLabel(),
                'rel' => 'sabaitooltip',
            ]
        );
    }
    
    public function onDirectoryAdminDirectoryMenusFilter(&$menus, $directory)
    {
        $menus['export'] = [
            'title' => $title = __('Export', 'directories-pro'),
            'url' => '/directories/' . $directory->name . '/export',
            'data' => array(
                'link_options' => ['container' => 'modal', 'modalSize' => 'xl'],
                'link_attr' => ['data-modal-title' => $title . ' - ' . $directory->getLabel()],
            ),
            'page' => true,
        ];
    }

    public function onCsvImportFilesFilter(&$files, $bundle)
    {
        if ($bundle->type === 'directory_category') {
            $files[__DIR__ . '/csv/categories.csv'] = __('Demo categories', 'directories-pro');
        } elseif ($bundle->type === 'location_location') {
            $files[__DIR__ . '/csv/locations.csv'] = __('Demo locations (USA states and cities)', 'directories-pro');
        } elseif ($bundle->type === 'directory_tag') {
            $files[__DIR__ . '/csv/tags.csv'] = __('Demo tags', 'directories-pro');
        }
    }

    public function onCsvImportSettingsFormFilter(&$form, $bundle, $csvFile)
    {
        if (isset($form['importers']['location_photo'])
            && $csvFile['type'] === 'existing'
            && $bundle->type === 'location_location'
            && $csvFile['existing'] === __DIR__ . '/csv/locations.csv'
        ) {
            $form['importers']['location_photo']['location']['#default_value'] = 'url';
        }
    }

    public function upgrade($current, $newVersion, System\Progress $progress = null)
    {
        parent::upgrade($current, $newVersion, $progress);
        if (version_compare($current->version, '1.2.70-dev.0', '<')) {
            $db = $this->_application->getDB();
            $db->begin();
            try {
                $db->exec(sprintf(
                    'UPDATE %1$sentity_fieldconfig SET fieldconfig_type = %2$s WHERE fieldconfig_type = %3$s AND fieldconfig_name = %4$s',
                    $db->getResourcePrefix(),
                    $db->escapeString('directory_opening_hours'),
                    $db->escapeString('time'),
                    $db->escapeString('field_opening_hours')
                ));
                $db->exec(sprintf(
                    'UPDATE %1$sdisplay_element SET element_name = %2$s WHERE element_name = %3$s AND element_data LIKE %4$s',
                    $db->getResourcePrefix(),
                    $db->escapeString('entity_form_directory_opening_hours'),
                    $db->escapeString('entity_form_time'),
                    $db->escapeString('%"field_name";s:19:"field_opening_hours"%')
                ));
            } catch (\Exception $e) {
                $db->rollback();
                $this->_application->logError('Failed updating database to v1.2.70. Error: ' . $e->getMessage());
                return $this;
            }
            $directories = [];
            foreach ($this->_application->Entity_Bundles() as $bundle) {
                if (!empty($bundle->info['is_taxonomy'])
                    || (!$field = $this->_application->Entity_Field($bundle, 'field_opening_hours'))
                ) continue;

                $field->setFieldWidget('directory_opening_hours')->commit();

                if (!isset($directories[$bundle->group])) {
                    if (!$directory = $this->_application->getModel('Directory', 'Directory')->fetchById($bundle->group)) continue;

                    $directories[$bundle->group] = $directory;
                }
            }
            if (!empty($directories)) {
                $this->_application->getPlatform()->clearCache();
                foreach ($directories as $directory) {
                    \SabaiApps\Directories\Component\Directory\Controller\Admin\EditDirectory::updateBundles($this->_application, $directory);
                }
            }
        }
        return $this;
    }

    public function getCustomTaxonomies()
    {
        return isset($this->_config['custom_taxonomies']) ? $this->_config['custom_taxonomies'] : [];
    }

    public function entityGetBundleTypeNames()
    {
        $ret = [];
        foreach (array_keys($this->getCustomTaxonomies()) as $name) {
            $ret[] = 'directory_custom_tax_' . $name;
        }

        return $ret;
    }

    public function entityGetBundleType($name)
    {
        switch ($name) {
            default:
                if (strpos($name, 'directory_custom_tax_') === 0) {
                    $slug = substr($name, strlen('directory_custom_tax_'));
                    $custom_taxonomies = $this->getCustomTaxonomies();
                    if (!empty($custom_taxonomies[$slug])) {
                        return new EntityBundleType\CustomTaxonomyEntityBundleType($this->_application, $name, $slug, $custom_taxonomies[$slug]);
                    }
                    return;
                }
                return new EntityBundleType\DirectoryEntityBundleType($this->_application, $name);
        }
    }

    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {
        if ($directoryType !== 'directory'
            || $info['entity_type'] !== 'post'
            || !empty($info['parent'])
            || (!$custom_taxonomies = $this->getCustomTaxonomies())
        ) return;

        $options = [];
        foreach (array_keys($custom_taxonomies) as $custom_taxonomy) {
            $options[$custom_taxonomy] = $custom_taxonomies[$custom_taxonomy]['label'];
        }
        ksort($options);
        $form['directory_custom_taxonomies'] = [
            '#title' => __('Enable custom taxonomies', 'directories-pro'),
            '#type' => 'checkboxes',
            '#options' => $options,
            '#default_value' => empty($settings['directory_custom_taxonomies']) ? null : $settings['directory_custom_taxonomies'],
            '#horizontal' => true,
            '#columns' => 3,
        ];
    }

    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {
        if (!empty($info['is_taxonomy'])
            || !empty($info['parent'])
            || empty($settings['directory_custom_taxonomies'])
        ) {
            unset($info['directory_custom_taxonomies']);
            return;
        }

        $custom_taxonomies = [];
        foreach (array_keys($this->getCustomTaxonomies()) as $custom_taxonomy) {
            if (in_array($custom_taxonomy, $settings['directory_custom_taxonomies'])) {
                $custom_taxonomies[$custom_taxonomy] = true;
            }
        }
        $info['directory_custom_taxonomies'] = $custom_taxonomies;
    }

    public function onEntityBundlesInfoFilter(&$bundles, $componentName, $group)
    {
        foreach (array_keys($this->getCustomTaxonomies()) as $custom_taxonomy) {
            $entity_bundle_type = 'directory_custom_tax_' . $custom_taxonomy;
            foreach (array_keys($bundles) as $bundle_type) {
                $info =& $bundles[$bundle_type];

                if (empty($info['directory_custom_taxonomies'][$custom_taxonomy])
                    || !empty($info['is_taxonomy'])
                    || !empty($info['parent'])
                ) continue;

                try {
                    $entity_bundle_type_info = $this->entityGetBundleType($entity_bundle_type)->entityBundleTypeInfo();
                } catch (Exception\IException $e) {
                    $this->_application->logError($e->getMessage());
                    continue;
                }

                // Associate bundle
                if (!isset($info['taxonomies'][$entity_bundle_type]) // may already be set if updating or importing
                    || !is_array($info['taxonomies'][$entity_bundle_type]) // not an array if updating
                ) {
                    $info['taxonomies'][$entity_bundle_type] = ['weight' => 99];
                }

                // Add bundle
                if (!isset($bundles[$entity_bundle_type])) { // may already set if updating
                    $bundles[$entity_bundle_type] = [];
                }
                $bundles[$entity_bundle_type] += $entity_bundle_type_info;

                continue 2; // there should be only one bundle enabled
            }

            // No bundle enabled found, so make sure the bundle is not assigned
            unset($bundles[$entity_bundle_type]);
        }
    }

    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'directory_custom_taxonomies';
    }

    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        $form['fields'][$this->_name] = [
            '#tab' => 'Directory',
            '#title' => __('Taxonomy Settings', 'directories-pro'),
            '#weight' => 30,
            'custom_taxonomies' => [
                '#title' => __('Custom Taxonomies', 'directories-pro'),
                '#horizontal' => true,
            ] + $this->_application->DirectoryPro_CustomTaxonomies_settingsForm(empty($this->_config['custom_taxonomies']) ? [] : $this->_config['custom_taxonomies']),
        ];
    }
}
