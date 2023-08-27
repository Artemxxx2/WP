<?php
namespace SabaiApps\Directories\Component\System;

use SabaiApps\Directories\Context;

interface IMainRouter
{
    public function systemMainRoutes($lang = null);
    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route);
    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route);
}