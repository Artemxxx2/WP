<?php
namespace SabaiApps\Directories\Component\Location\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception\RuntimeException;

class QueryApi extends Controller
{
    protected function _doExecute(Context $context)
    {
        if (!$context->getRequest()->isXhr()
            || (!$action = trim($context->getRequest()->asStr('action')))
            || !method_exists($this, $method = '_' . $action)
        ) {
            $context->setError();
            return;
        }

        try {
            $context->addTemplate('system_list')->setAttributes(['list' => $this->$method($context)]);
        } catch (\Exception $e) {
            $context->setError($e->getMessage());
        }
    }

    protected function _timezone(Context $context)
    {
        if ((!$latlng = trim($context->getRequest()->asStr('latlng')))
            || (!$latlng = explode(',', $latlng))
            || count($latlng) !== 2
        ) {
            throw new RuntimeException('Invalid parameters');
        }

        return $this->Location_Api_timezone($latlng);
    }

    protected function _placeRating(Context $context)
    {
        if (!$place_id = trim($context->getRequest()->asStr('placeId'))) {
            throw new RuntimeException('Invalid parameters');
        }

        return $this->Location_Api_placeRating($place_id, 'location_googlemaps');
    }
}