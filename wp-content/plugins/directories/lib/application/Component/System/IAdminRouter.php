<?php
namespace SabaiApps\Directories\Component\System;

use SabaiApps\Directories\Context;

interface IAdminRouter
{
    public function systemAdminRoutes();
    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route);
    public function systemAdminRouteTitle(Context $context, $path, $title, array $route);
}