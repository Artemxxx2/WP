<?php
namespace SabaiApps\Directories\Component\Location\Api;

interface IPlacesApi extends IApi
{
    public function locationApiGetPlaceRating($placeId, array $settings);
}