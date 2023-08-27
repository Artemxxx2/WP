<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Field;

class SchemaOrgHelper
{
    protected static $_props = [
        'name' => [],
        'description' => [],
        'sameAs' => [],
        'url' => [],
        'image' => [],
        'aggregateRating' => ['CreativeWork', 'Event', 'Organization', 'Place', 'Product', 'Brand', 'Service', 'Offer'],
        'address' => ['Organization', 'Person', 'Place'],
        'telephone' => ['Organization', 'Person', 'Place'],
        'faxNumber' => ['Organization', 'Person', 'Place'],
        'email' => ['Organization', 'Person'],
        'openingHoursSpecification' => ['LocalBusiness'],
        'paymentAccepted' => ['LocalBusiness'],
        'priceRange' => ['LocalBusiness'],
        'price' => ['Offer'],
        'priceCurrency' => ['Offer'],
        'geo' => ['Place', 'LocalBusiness'],
        'itemReviewed' => ['Review'],
        'reviewBody' => ['Review'],
        'reviewRating' => ['Review'],
        'genre' => ['CreativeWork'],
        'downvoteCount' => ['Question', 'Comment', 'Answer'],
        'upvoteCount' => ['Question', 'Comment', 'Answer'],
        'parentItem' => ['Comment', 'Answer'],
        'acceptedAnswer' => ['Question'],
        'answerCount' => ['Question'],
        'author' => ['CreativeWork'],
        'commentCount' => ['CreativeWork'],
        'dateCreated' => ['CreativeWork'],
        'dateModified' => ['CreativeWork'],
        'datePublished' => ['CreativeWork'],
        'datePosted' => ['JobPosting'],
        'baseSalary' => ['JobPosting'],
        'validThrough' => ['JobPosting'],
        'title' => ['JobPosting'],
        'hiringOrganization' => ['JobPosting'],
        'jobLocation' => ['JobPosting'],
        'employmentType' => ['JobPosting'],
        'keywords' => ['CreativeWork'],
        'text' => ['CreativeWork'],
        'startDate' => ['Event'],
        'endDate' => ['Event'],
        'location' => ['Event'],
        'duration' => ['Event', 'Movie'],
        'category' => ['Product', 'Service', 'Offer'],
        'jobTitle' => ['Person'],
        'isbn' => ['Book'],
        'menu' => ['FoodEstablishment'],
        'acceptsReservations' => ['FoodEstablishment'],
        'servesCuisine' => ['FoodEstablishment'],
        'logo' => ['Brand', 'Organization', 'Place', 'Product'],
        'thumbnail' => ['ImageObject'],
        'contentUrl' => ['MediaObject'],
        'contentSize' => ['MediaObject'],
        'width' => ['MediaObject'],
        'height' => ['MediaObject'],
        'expires' => ['CreativeWork'],
        'headline' => ['CreativeWork'],
        'articleBody' => ['Article'],
        'articleSection' => ['Article'],
        'sku' => ['Product', 'Offer'],
    ];

    public function help(Application $application, Entity\Type\IEntity $entity, array $settings)
    {
        $html = $this->render($application, $entity, $settings);
        $application->getPlatform()->addHead($html, 'entity_schemaorg_jsonld');
    }

    public function render(Application $application, Entity\Type\IEntity $entity, array $settings)
    {
        $json = $this->json($application, $entity, $settings['type'], $settings['properties']);
        return '<script type="application/ld+json">' . $application->JsonEncode($json) . '</script>';
    }
    
    public function json(Application $application, Entity\Type\IEntity $entity, $type, array $properties)
    {
        $json = [
            '@context' => 'http://schema.org',
            '@type' => $type,
        ];
        foreach ($properties as $prop=> $field_name) {
            if (!$field_name
                || (!$field = $application->Entity_Field($entity, $field_name))
                || (!$field_type = $application->Field_Type($field->getFieldType(), true))
                || (!$property = $field_type->fieldSchemaRenderProperty($field, $prop, $entity))
            ) continue;
            
            $json[$prop] = is_array($property) && count($property) === 1 ? $property[0] : $property;
        }
        
        return $application->Filter('entity_schemaorg_jsonld', $json, [$entity, $type, $properties]);
    }

    public function props(Application $application)
    {
        return self::$_props;
    }
    
    public function settingsForm(Application $application, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $types = $application->Filter('entity_schemaorg_types', [
            'CreativeWork' => [
                'Book',
                'Game',
                'Movie',
                'Website',
                'Review',
                'Recipe',
                'Comment',
                'Question',
                'Answer',
                'MediaObject' => [
                    'ImageObject'
                ],
                'Article',
                'Course',
            ],
            'Event',
            'Intangible' => [
                'Brand',
                'Service',
                'Offer',
                'JobPosting',
            ],
            //'MedicalEntity',
            'Organization' => [
                'Corporation',
                'LocalBusiness' => [
                    'FoodEstablishment' => [
                        'Restaurant',
                    ],
                    'Store',
                ],
                'NGO',
                'OnlineBusiness',
            ],
            'Person',
            'Place',
            'Product'
        ]);

        $parent_types = $descendant_types = [];
        $type_options = $this->_getSchemaTypeOptions($types, $descendant_types, $parent_types, ['' => '— ' . __('Select schema type', 'directories') . ' —']);
        $form = [
            '#element_validate' => [function(Form\Form $form, &$value, $element) use ($parent_types) {
                if (!empty($value['type'])
                    && !empty($value['properties'])
                ) {
                    $props = self::$_props;
                    foreach (array_keys($value['properties']) as $prop) {
                        if (!empty($props[$prop])) {
                            if (in_array($value['type'], $props[$prop])) {
                                continue; // property belongs to this type
                            }
                            if (!empty($parent_types[$value['type']])) {
                                foreach ($parent_types[$value['type']] as $parent) {
                                    if (in_array($parent, $props[$prop])) {
                                        continue 2; // property belongs to parent type
                                    }
                                }
                            }
                            // This prop does not belong to this type, remove it
                            unset($value['properties'][$prop]);
                        }
                    }
                }
            }],
            'type' => [
                '#title' => __('Schema.org JSON-LD', 'directories'),
                '#type' => 'select',
                '#options' => $type_options,
                '#default_value' => isset($settings['type']) ? $settings['type'] : null,
                '#horizontal' => true,
                '#empty_value' => '',   
            ],
            'properties' => [
                '#horizontal' => true,
                '#title' => ' ',
                '#element_validate' => [function(Form\Form $form, &$value) use ($parents) {
                    $value = $form->getValue(array_merge($parents, ['type'])) ? array_filter($value) : null;
                }],
                '#states' => [
                    'invisible' => [
                        $type_selector = sprintf('select[name="%s[type]"]', $application->Form_FieldName($parents)) => ['value' => ''],
                    ]
                ],
            ],
        ];

        foreach ($application->Entity_Field($bundle->name) as $field) {
            if ((!$field_type = $application->Field_Type($field->getFieldType(), true))
                || !$field_type instanceof Field\Type\ISchemable
            ) continue;
            
            foreach ($field_type->fieldSchemaProperties() as $property) {
                if (!isset(self::$_props[$property])) continue;
                
                if (!isset($form['properties'][$property])) {
                    $form['properties'][$property] = [
                        '#type' => 'select',
                        '#title' => $property,
                        '#options' => [
                            '' => '— ' . __('Select field', 'directories') . ' —',
                        ],
                        '#default_value' => isset($settings['properties'][$property]) ? $settings['properties'][$property] : null,
                        '#horizontal' => true,
                    ];
                    if (!empty(self::$_props[$property])) {
                        // Make property visible to certain schema types
                        $prop_types = self::$_props[$property];
                        foreach (self::$_props[$property] as $prop_type) {
                            if (!empty($descendant_types[$prop_type])) {
                                foreach ($descendant_types[$prop_type] as $descendant) {
                                    $prop_types[] = $descendant;
                                }
                            }
                        }
                        $form['properties'][$property]['#states']['visible'] = [
                            $type_selector => ['type' => 'one', 'value' => array_unique($prop_types)],
                        ];
                    }
                }
                $form['properties'][$property]['#options'][$field->getFieldName()] = $field->getFieldLabel() . ' - ' . $field->getFieldName();
            }
        }
        
        return $form;
    }
    
    protected function _getSchemaTypeOptions($types, array &$descendants, array &$parents, array $options = [], $prefix = '--', array $_parents = [])
    {
        foreach ($types as $key => $type) {
            if (is_array($type)) {
                $parents[$key] = $_parents;
                $options[$key] = str_repeat($prefix, count($_parents)) . $key;
                foreach ($_parents as $parent) {
                    if (!isset($descendants[$parent])) $descendants[$parent] = [];
                    $descendants[$parent][] = $key;
                }
                $__parents = $_parents;
                $__parents[] = $key;
                $options = $this->_getSchemaTypeOptions($type, $descendants, $parents, $options, $prefix, $__parents);
            } else {
                $parents[$type] = $_parents;
                $options[$type] = str_repeat($prefix, count($_parents)) . $type;
                foreach ($_parents as $parent) {
                    if (!isset($descendants[$parent])) $descendants[$parent] = [];
                    $descendants[$parent][] = $type;
                }
            }
        }
        
        return $options;
    }
}