<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Component\Form\FormComponent;

class BuildHelper
{
    static protected $_forms = [];
    
    public function help(Application $application, array $settings, $useCache = true, array $values = null, array $errors = [])
    {
        if (!isset($settings['#build_id'])) {
            $settings['#build_id'] = md5(uniqid(mt_rand(), true));
            $settings['#is_rebuild'] = false;
        } else { 
            // Is the form already built and cached?
            if ($settings['#build_id'] !== false && isset(self::$_forms[$settings['#build_id']])) {
                // Return cached form if rebuild is not necessary
                if ($useCache) return self::$_forms[$settings['#build_id']];
            }
            $settings['#is_rebuild'] = isset($settings['#is_rebuild']);
        }
        // Set id if not already set
        if (!isset($settings['#id'])) {
            $settings['#id'] = $this->id($application, $settings);
        }
        
        $settings['#method'] = isset($settings['#method']) && strtolower($settings['#method']) === 'get' ? 'get' : 'post';

        if ($settings['#build_id'] !== false
            && ($settings['#method'] !== 'get' || !empty($settings['#enable_storage']))
        ) { 
            // Embed build ID in hidden field
            $settings[FormComponent::FORM_BUILD_ID_NAME] = array(
                '#type' => 'hidden',
                '#value' => $settings['#build_id']
            );
        }

        // Initialize form storage
        $storage = [];
        if (!empty($settings['#enable_storage'])
            && $settings['#build_id'] !== false
        ) {
            if (isset($settings['#initial_storage'])) $storage = $settings['#initial_storage'];

            $application->getComponent('Form')->setFormStorage(
                $settings['#build_id'],
                $storage,
                !empty($settings['#storage_as_option'])
            );
        }

        // Allow other plugins to modify form settings and storage
        $application->Action(
            'form_build_form',
            array(&$settings, &$storage)
        );
        // Call with inherited form names
        if (!empty($settings['#inherits'])) {
            foreach (array_reverse($settings['#inherits']) as $inherited_form_name) {
                $application->Action(
                    'form_build_' . $inherited_form_name,
                    array(&$settings, &$storage)
                );
            }
        }
        // Call with the name of current form
        if (!empty($settings['#name'])) {
            $application->Action(
                'form_build_' . $settings['#name'],
                array(&$settings, &$storage)
            );
        }

        $form = new Form($application, $settings, $storage, $errors);
        $form->build($values);

        if ($settings['#build_id'] !== false) {
            // Add built form to cache
            self::$_forms[$settings['#build_id']] = $form;
        }

        return $form;
    }
    
    public function id(Application $application, array $settings)
    {
        return 'drts-form-' . ($settings['#build_id'] !== false ? $settings['#build_id'] : md5(uniqid(mt_rand(), true)));
    }
}