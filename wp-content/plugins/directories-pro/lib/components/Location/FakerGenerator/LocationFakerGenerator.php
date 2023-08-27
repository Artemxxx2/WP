<?php
namespace SabaiApps\Directories\Component\Location\FakerGenerator;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Faker;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;

class LocationFakerGenerator extends Faker\Generator\AbstractGenerator
{
    protected $_viewport;
    
    protected function _fakerGeneratorInfo()
    {
        switch ($this->_name) {
            case 'location_address':
                return array(
                    'field_types' => array($this->_name),
                    'default_settings' => array(
                        'type' => 'random',
                        'probability' => 100,
                        'num' => 1000,
                        'max' => 5,
                        'location' => null,
                        'geocode' => true,
                    ),
                );
        }
    }

    public function fakerGeneratorSupports(Entity\Model\Bundle $bundle, Field\IField $field)
    {
        switch ($this->_name) {
            case 'location_address':
                return (bool)$this->_application->Entity_Bundle('location_location', $bundle->component, $bundle->group);
        }
        return parent::fakerGeneratorSupports($bundle, $field);
    }

    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        switch ($this->_name) {
            case 'location_address':
                $bundle = $this->_application->Entity_Bundle('location_location', $field->Bundle->component, $field->Bundle->group);
                return array(
                    'probability' => $this->_getProbabilitySettingForm($settings['probability']),
                    'type' => array(
                        '#type' => 'select',
                        '#title' => $bundle->getLabel(),
                        '#options' => array(
                            'entries' => __('Select manually', 'directories-pro'),
                            'new' => _x('Enter new location', 'faker', 'directories-pro'),
                            'random' => __('Random', 'directories-pro'),
                        ),
                        '#default_value' => $settings['type'],
                    ),
                    'location' => array(
                        '#type' => 'textfield',
                        '#description' => __('Enter a location used to generate lat/lng coordinates, e.g. USA, California, Tokyo, etc.', 'directories-pro'),
                        '#default_value' => $settings['location'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'new'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'new'; },
                    ),
                    'entries' => array(
                        '#type' => 'autocomplete',
                        '#default_options_callback' => [[$this, '_getDefaultOptions'], [$bundle->name]],
                        '#select2' => true,
                        '#select2_ajax' => true,
                        '#select2_item_text_key' => 'title',
                        '#select2_ajax_url' => $this->_application->MainUrl('/_drts/entity/' . $bundle->type . '/query', array('bundle' => $bundle->name, Request::PARAM_CONTENT_TYPE => 'json'), '', '&'),
                        '#multiple' => true,
                        '#states' => array(
                            'visible' => array(
                                sprintf('[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'entries'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'entries'; },
                    ),
                    'num' => array(
                        '#type' => 'slider',
                        '#title' => sprintf(_x('Max number of random %s to fetch from the database', 'faker', 'directories-pro'), strtolower($bundle->getLabel()), $bundle->getLabel()),
                        '#min_value' => 1,
                        '#max_value' => 1000,
                        '#step' => 10,
                        '#default_value' => $settings['num'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'random'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'random'; },
                    ),
                    'geocode' => array(
                        '#title' => _x('Reverse geocode lat/lng coordinates generated for each item', 'faker', 'directories-pro'),
                        '#type' => 'checkbox',
                        '#default_value' => !empty($settings['geocode']),
                    ),
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
               );
        }
    }
    
    public function _getDefaultOptions($defaultValue, array &$options, $bundleName)
    {
        foreach ($this->_application->Entity_Types_impl('taxonomy')->entityTypeEntitiesByIds($defaultValue, $bundleName) as $entity) {
            $options[$entity->getId()] = $this->_application->Entity_Title($entity);
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {
        switch ($this->_name) {
            case 'location_address':
                if (mt_rand(0, 100) > $settings['probability']) return;

                if ($settings['type'] !== 'new') {
                    if (!$taxonomy_bundle = $this->_application->Entity_Bundle('location_location', $field->Bundle->component, $field->Bundle->group)) return false;
                    
                    if (!$entity_type_impl = $this->_application->Entity_Types_impl($taxonomy_bundle->entitytype_name, true)) return false;
                    
                    if (!isset($this->_ids[$taxonomy_bundle->name])) {
                        if ($settings['type'] === 'entries') {
                            $this->_ids[$taxonomy_bundle->name] = $settings['entries'];
                        } else {
                            $this->_ids[$taxonomy_bundle->name] = $entity_type_impl->entityTypeRandomEntityIds($taxonomy_bundle->name, $settings['num']);
                        }
                        $this->_idCount[$taxonomy_bundle->name] = count($this->_ids[$taxonomy_bundle->name]);
                    }
                    if (!$this->_idCount[$taxonomy_bundle->name]) return false;
                    
                    $id = $this->_ids[$taxonomy_bundle->name][mt_rand(0, $this->_idCount[$taxonomy_bundle->name] - 1)];
                    if (!$term = $this->_application->Entity_Entity($taxonomy_bundle->entitytype_name, $id)) return;
                    
                    $location = [];
                    foreach ($entity_type_impl->entityTypeParentEntities($term, $term->getBundleName()) as $parent_term) {
                        $location[] = $parent_term->getTitle();
                    }
                    $location[] = $term->getTitle();
                    $location = implode(' ', $location);
                } else {
                    $location = $settings['location'];
                }
                if (empty($location)) return false;
                
                if (!isset($this->_viewport[$location])) {
                    try {
                        $geocoded = $this->_application->Location_Api_geocode($location, false);
                        if (empty($geocoded['viewport'])) {
                            if (!empty($geocoded['lat'])
                                && !empty($geocoded['lng'])
                            ) {
                                $geocoded['viewport'] = $this->_application->Map_Api_viewport($geocoded['lat'], $geocoded['lng']);
                            }
                        }
                        $this->_viewport[$location] = $geocoded['viewport'];
                    } catch (Exception\IException $e) {
                        $this->_application->logError($e);
                        $this->_viewport[$location] = false;
                    }
                }
                if (!$this->_viewport[$location]) return false;
                
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                $faker = $this->_getFaker();
                $viewport = $this->_viewport[$location];
                for ($i = 0; $i < $count; ++$i) {
                    $ret[$i] = array(
                        'lat' => $faker->randomFloat(6, $viewport[0], $viewport[2]),
                        'lng' => $faker->randomFloat(6, $viewport[1], $viewport[3]),
                    );
                    if ($settings['geocode']) {
                        try {
                            $geocoded = $this->_application->Location_Api_reverseGeocode([$ret[$i]['lat'], $ret[$i]['lng']], false);
                            $ret[$i] += array(
                                'address' => $geocoded['address'],
                                'street' => $geocoded['street'],
                                'city' => $geocoded['city'],
                                'province' => $geocoded['province'],
                                'zip' => $geocoded['zip'],
                                'country' => $geocoded['country'],
                                'term_id' => isset($id) ? $id : null,
                                'timezone' => $this->_application->Location_Api_timezone([$ret[$i]['lat'], $ret[$i]['lng']]),
                            );      
                            continue;
                        } catch (Exception\IException $e) {
                            $this->_application->logError($e);
                        }
                    }
                    $ret[$i] += array(
                        'address' => $faker->address(),
                        'street' => $faker->streetAddress(),
                        'city' => $faker->city(),
                        'province' => method_exists($faker, 'state') ? $faker->state() : '',
                        'zip' => $faker->postcode(),
                        'country' => $faker->country(),
                        'term_id' => isset($id) ? $id : null,
                        'timezone' => $faker->timezone(),
                    ); 
                }
                return $ret;
        }
    }
}