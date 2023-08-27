<?php
namespace SabaiApps\Directories\Component\Location;

interface IAutocompleteApis
{
    public function locationGetAutocompleteApiNames();
    public function locationGetAutocompleteApi($name);
}