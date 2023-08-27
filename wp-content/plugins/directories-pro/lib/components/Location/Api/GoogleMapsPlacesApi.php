<?php
namespace SabaiApps\Directories\Component\Location\Api;

class GoogleMapsPlacesApi extends AbstractGoogleMapsApi implements IPlacesApi
{
    protected function _doGetInfo()
    {
        return [
            'label' => __('Google Maps Places', 'directories-pro'),
        ];
    }

    public function locationApiLoad(array $settings)
    {
        $this->_load('places');
    }

    public function locationApiGetPlaceRating($placeId, array $settings)
    {
        $url = $this->_application->Map_GoogleMapsApi_url('/place/details/json', [
            'key' => $settings['api']['key'],
            'timestamp' => time(),
            'place_id' => $placeId,
        ]);
        $result = $this->_application->Map_GoogleMapsApi_request($url)->result;
        return [
            'rating' => $result->rating,
            'count' => $result->user_ratings_total,
        ];
    }
}