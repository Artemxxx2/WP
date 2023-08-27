<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class GuestAuthorCookieHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, $lifetime = 8640000 /* 100 days */)
    {
        if ($entity->getAuthorId()) return; // not an anynoymous post
        
        $application->Entity_Field_load($entity);
        if ((!$guest_info = $entity->getSingleFieldValue('frontendsubmit_guest'))
            || empty($guest_info['guid'])
        ) return; // invalid guest author info
        
        $cookie = $application->System_Cookie('drts_frontendsubmit_guids');
        if (is_string($cookie)
            && strlen($cookie)
            && ($guids = explode(',', $cookie))
        ) {
            if (false !== $key = array_search($guest_info['guid'], $guids)) {
                // remove from array so that the guid is always appended
                unset($guids[$key]);
            }
        } else {
            $guids = [];
        }
        $guids[] = $guest_info['guid'];
        if (count($guids) > 10) {
            $guids = array_slice($guids, -10, 10); // maximum of 10 guest posts
        }
        $application->System_Cookie('drts_frontendsubmit_guids', implode(',', $guids), time() + $lifetime, true);
    }
    
    public function guids(Application $application)
    {
        if (($cookie = $application->System_Cookie('drts_frontendsubmit_guids'))
            && ($guids = explode(',', $cookie))
        ) {
            return $guids;
        }
    }
}