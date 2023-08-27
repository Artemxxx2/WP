<?php
namespace SabaiApps\Directories\Component\Location\Helper;

use SabaiApps\Directories\Application;

class IsSearchRequestedHelper
{    
    public function help(Application $application)
    {
        if (!$application->isComponentLoaded('Search')
            || (!$params = $application->Search_Form_params())
            || !isset($params['search_location_address'])
            || !is_array($params['search_location_address'])
        ) return;
        
        $search_value = $params['search_location_address'];
        if (!empty($search_value['term_id'])
            && !empty($search_value['taxonomy'])
        ) {
            return (int)$search_value['term_id'];
        } else {
            if (isset($search_value['text']) && strlen($search_value['text'])) {
                // specific area on map specified, so do not show the filter form
                return true;
            }
        }
    }
}