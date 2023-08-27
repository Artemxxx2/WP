<?php
namespace SabaiApps\Directories\Component\Location\Api;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class GeoNamesTimezoneApi implements ITimezoneApi
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
        $this->_info = [
            'label' => __('GeoNames Web Service Timezone', 'directories-pro'),
            'default_settings' => [
                'username' => null,
            ],
        ];
    }

    public function locationApiInfo($key = null)
    {
        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function locationApiLoad(array $settings)
    {
        $this->_application->getPlatform()
            ->addJsFile(
                'location-geonames-timezone.min.js',
                'drts-location-geonames-timezone',
                'drts-location-api',
                'directories-pro'
            )
            ->addJsInline('drts-location-geonames-timezone', sprintf(
                'var DRTS_Location_geonamesUsername = \'%s\';',
                $this->_application->H($settings['username'])
            ));
    }

    public function locationApiSettingsForm(array $settings, array $parents)
    {
        return [
            '#title' => __('GeoNames Web Service Settings', 'directories-pro'),
            '#class' => 'drts-form-label-lg',
            '#states' => [
                'visible' => [
                    '[name="Map[lib][location_timezone]"]' => ['type' => 'value', 'value' => 'location_geonames'],
                ],
            ],
            'username' => [
                '#type' => 'textfield',
                '#title' => __('GeoNames user name', 'directories-pro'),
                '#description' => sprintf(
                    $this->_application->H(__('Visit the following page to create a GeoNames user account: %s', 'directories-pro')),
                    '<a href="http://www.geonames.org/login" target="_blank" rel="nofollow noopener">http://www.geonames.org/login</a>'
                ),
                '#description_no_escape' => true,
                '#default_value' => $settings['username'],
                '#horizontal' => true,
                '#required' => function($form) {
                    return $form->getValue(['Map', 'lib', 'location_timezone']) === $this->_name;
                },
            ],
        ];
    }

    public function locationApiGetTimezone(array $latlng, array $settings)
    {
        $url = (string)$this->_application->Url([
            'script_url' => 'http://api.geonames.org/timezoneJSON',
            'params' => [
                'lat' => $latlng[0],
                'lng' => $latlng[1],
                'username' => $settings['username'],
            ],
            'separator' => '&',
        ]);
        $results = $this->_sendRequest($url);
        return isset($results['timezoneId']) ? $results['timezoneId'] : '';
    }

    protected function _sendRequest($url, $assoc = true)
    {
        $result = $this->_application->getPlatform()->remoteGet($url);
        if (null === $result = json_decode($result, $assoc)) {
            throw new Exception\RuntimeException('Failed parsing result returned from URL: ' . $url);
        }
        return $result;
    }
}