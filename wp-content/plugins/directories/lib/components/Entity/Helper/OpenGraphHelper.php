<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Field;

class OpenGraphHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, array $settings)
    {
        $meta = [];
        if (isset($settings['fb_app_id'])
            && strlen($settings['fb_app_id'])
        ) {
            $meta['fb:app_id'] = $settings['fb_app_id'];
        }
        $meta += [
            'og:site_name' => $application->SiteInfo('name'),
            'og:type' => $settings['type'],
            'og:url' => $application->Entity_PermalinkUrl($entity),
        ];
        foreach ($settings['properties'] as $prop=> $field_name) {
            if (!$field_name
                || (!$field = $application->Entity_Field($entity, $field_name))
                || (!$field_type = $application->Field_Type($field->getFieldType(), true))
            ) continue;
            
            $property = str_replace('__', ':', $prop);
            
            if (!$rendered = $field_type->fieldOpenGraphRenderProperty($field, $property, $entity)) continue;
            
            $meta[$property] = $rendered;
        }
        // Let other components/scripts modify meta data
        $meta = $application->Filter('entity_opengraph_meta', $meta, array($entity, $settings));
        
        // Add meta tags to header
        $html = [];
        foreach ($meta as $prop => $_meta) {
            if (!is_array($_meta)) {
                $html[] = '<meta property="' . $prop . '" content="' . $application->H($_meta) . '" />';
            } else {
                foreach ($_meta as $_prop => $_content) {
                    if (is_array($_content)) {
                        foreach ($_content as $_prop => $__content) {
                            $html[] = '<meta property="' . $_prop . '" content="' . $application->H($__content) . '" />';
                        }
                    } else {
                        if (is_int($_prop)) {
                            $_prop = $prop;
                        }
                        $html[] = '<meta property="' . $_prop . '" content="' . $application->H($_content) . '" />';
                    }
                }
            }
        }
        $application->getPlatform()->addHead(implode(PHP_EOL, $html), 'entity_opengraph_meta');
        
        $GLOBALS['drts_entity_opengraph_type'] = ($pos = strpos($settings['type'], '.')) ? substr($settings['type'], 0, $pos) : $settings['type'];
        
        if ($application->getPlatform()->getName() === 'WordPress') {
            // Add namespace prefix
            add_filter('language_attributes', array($this, 'languageAttributesFilter'), 99999);
            // Remove jetpack OG meta tags
            add_filter('jetpack_enable_open_graph', '__return_false');
            // Remove WP SEO plugin OG meta tags
            add_action('wpseo_head', array($this, 'removeWpseo'), 1);
            add_filter('wpseo_twitter_image', '__return_false', 99999);
            // Remove All-in-one SEO Pack plugin OG meta tags
            add_filter('aiosp_opengraph_meta', array($this, 'filterAiosp'), 99999, 3);
            // Remove SEO Ultimate plugin meta tags
            remove_all_actions('su_head');
        } 
    }
    
    public function settingsForm(Application $application, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $types = $application->Filter('entity_opengraph_types', array(
            'article',
            'books.book',
            'business.business',
            'music.album',
            'music.song',
            'place',
            'product',
            'profile',
            'video.movie',
            'video.other',
            'video.tv_show',
            'website',
        ));
        $props = $application->Filter('entity_opengraph_props', array(
            'og:title' => [],
            'og:image' => [],
            'og:url' => [],
            'og:description' => [],
            'og:audio' => [],
            'og:video' => [],
            'article:author' => array('article'),
            'article:published_time' => array('article'),
            'article:expiration_time' => ['article'],
            'books:author' => array('books.book'),
            'books:isbn' => array('books.book'),
            'books:page_count' => array('books.book'),
            'books:rating' => array('books.book'),
            'books:release_date' => array('books.book'),
            'books:sample' => array('books.book'),
            'business:contact_data' => array('business.business'),
            'business:hours' => array('business.business'),
            'music:musician' => array('music.album', 'music.song'),
            'music:release_date' => array('music.album', 'music.song'),
            'music:duration' => array('music.song'),
            'music:isrc' => array('music.isrc'),
            'place:location' => array('place'),
            'product:isbn' => array('product'),
            'product:price' => array('product'),
            'product:product_link' => array('product'),
            'profile:username' => array('profile'),
            'video:duration' => array('video.movie', 'video.other', 'video.tv_show'),
            'video:release_date' => array('video.movie', 'video.other', 'video.tv_show'),
        ));
        $form = array(
            'type' => array(
                '#title' => __('Open Graph meta tags', 'directories'),
                '#type' => 'select',
                '#options' => array('' => '— ' . __('Select object type (og:type)', 'directories') . ' —') + array_combine($types, $types),
                '#default_value' => isset($settings['type']) ? $settings['type'] : null,
                '#horizontal' => true,
                '#empty_value' => '',
            ),
            'properties' => array(
                '#title' => ' ',
                '#default_value' => isset($settings['properties']) ? $settings['properties'] : null,
                '#horizontal' => true,
                '#states' => array(
                    'invisible' => array(
                        $type_selector = sprintf('select[name="%s[type]"]', $application->Form_FieldName($parents)) => ['value' => ''],
                    )
                ),
                '#element_validate' => [function(Form\Form $form, &$value) use ($parents) {
                    if (!$form->getValue(array_merge($parents, ['type']))) $value = null;
                }],
            ),
            'fb_app_id' => [
                '#type' => 'textfield',
                '#states' => [
                    'invisible_or' => [
                        $type_selector => ['value' => ''],
                    ],
                ],
                '#field_prefix' => __('Facebook App ID', 'directories'),
                '#placeholder' => '123456789012345',
                '#default_value' => isset($settings['fb_app_id']) ? $settings['fb_app_id'] : null,
                '#horizontal' => true,
                '#integer' => true,
                '#min_length' => 15,
                '#max_length' => 15,
            ],
        );

        foreach ($application->Entity_Field($bundle->name) as $field) {
            if ((!$field_type = $application->Field_Type($field->getFieldType(), true))
                || !$field_type instanceof Field\Type\IOpenGraph
            ) continue;
            
            foreach ($field_type->fieldOpenGraphProperties() as $property) {
                if (!isset($props[$property])) continue;
                
                $property_key = str_replace(':', '__', $property);
                if (!isset($form['properties'][$property_key])) {
                    $form['properties'][$property_key] = array(
                        '#type' => 'select',
                        '#title' => $property,
                        '#options' => array(
                            '' => '— ' . __('Select field', 'directories') . ' —',
                        ),
                        '#default_value' => isset($settings['properties'][$property_key]) ? $settings['properties'][$property_key] : null,
                        '#horizontal' => true,
                    );
                    if (!empty($props[$property])) {
                        // Make property visible to certain schema types
                        $form['properties'][$property_key]['#states']['visible'] = array(
                            $type_selector => array('value' => $props[$property]),
                        );
                    } else {
                        $form['properties'][$property_key]['#states']['invisible'] = array(
                            $type_selector => array('value' => ''),
                        );
                    }
                }
                $form['properties'][$property_key]['#options'][$field->getFieldName()] = $field->getFieldLabel() . ' - ' . $field->getFieldName();
            }
        }
        
        return $form;
    }
    
    public function removeWpseo()
    {
        remove_all_actions('wpseo_opengraph');
    }
    
    public function filterAiosp($value, $media, $key)
    {
        if ($media === 'facebook') return false;
        
        if ($media === 'twitter') {
            return in_array($key, array('card', 'site')) ? $value : false;
        }
        
        return $value;
    }
    
    public function languageAttributesFilter($attr)
    {
        $ns = array(
            'og: http://ogp.me/ns#',
            //'fb: http://ogp.me/ns/fb#',
            $GLOBALS['drts_entity_opengraph_type'] . ': http://ogp.me/ns/' . $GLOBALS['drts_entity_opengraph_type'] . '#',
        );
        if (false !== stripos($attr, ' prefix=')) {
            $search = '/ prefix=([\'"])(.+?)\1/i';
            $replace = ' prefix="$2 ' . implode(' ', $ns) . '"';
            $attr = preg_replace($search, $replace, $attr);
        } else {
            $attr .= ' prefix="' . implode(' ', $ns) . '"';
	}

        return $attr;
    }
    
    
}