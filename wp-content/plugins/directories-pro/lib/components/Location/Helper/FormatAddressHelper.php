<?php
namespace SabaiApps\Directories\Component\Location\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class FormatAddressHelper
{
    protected static $_tags = [
        'street' => 'street',
        'street2' => 'street2',
        'city' => 'city',
        'province' => 'province',
        'zip' => 'zip',
        'country' => 'country',
        'country_code' => 'country_code',
        'address' => 'full_address',
        'lat' => 'latitude',
        'lng' => 'longitude',
        'timezone' => 'timezone',
    ];

    public function help(Application $application, array $value, $format, array $locationHierarchy = null, Entity\Type\IEntity $entity = null, array $locationTerms = null)
    {
        $replace = ['<br>' => "\n", '<br />' => "\n"];
        foreach (self::$_tags as $column => $tag) {
            $replace['{' . $tag . '}'] = isset($value[$column]) && strlen($value[$column]) ? $application->H($value[$column]) : '';
        }
        if (strlen($replace['{country}']) === 2) {
            $replace['{country_code}'] = $replace['{country}'];
            if (strpos($format, '{country}')) {
                // Get full country name
                $replace['{country}'] = $application->System_Countries($replace['{country_code}']);
            }
        }
        if (!empty($locationHierarchy)
            && !empty($value['term_id'])
            && ($terms = isset($locationTerms) ? $locationTerms : (isset($entity) ? $entity->getFieldValue('location_location') : []))
        ) {
            foreach ($terms as $term) {
                if ($term->getId() === $value['term_id']) {
                    $location_titles = (array)$term->getCustomProperty('parent_titles');
                    $location_titles[$term->getId()] = $term->getTitle();
                    foreach (array_keys($locationHierarchy) as $key) {
                        $replace['{' . $key . '}'] = $application->H((string)array_shift($location_titles));
                    }
                }
            }
        }
        // Replace tags
        $formatted = strtr($format, $replace);
        // Replace multiple columns with single column
        $formatted = preg_replace('/,+/', ',', $formatted);
        // Replace columns with spaces in between
        $formatted = preg_replace('/,\s*,/', ',', $formatted);
        // Remove starting/trailing spaces/commas/linebreaks
        $formatted = trim($formatted, " ,\n");
        // Replace back new lines with <br />
        $formatted = str_replace("\n", '<br />', $formatted);
        // Replace multiple spaces with single space, must be called last since \s matched linebreaks
        $formatted = preg_replace('/\s+/', ' ', $formatted);

        return $formatted;
    }

    public function tags(Application $application, Entity\Model\Bundle $bundle, array $locationHierarchy = null)
    {
        $tags = [];
        foreach (array_values(self::$_tags) as $tag) {
            $tags[$tag] = '{' . $tag . '}';
        }
        if (!$application->Map_Api()) {
            unset($tags['full_address']);
        }
        if (isset($locationHierarchy)) {
            foreach (array_keys($locationHierarchy) as $key) {
                $tags[$key] = '{' . $key . '}';
            }
        }
        return array_values($tags);
    }
}