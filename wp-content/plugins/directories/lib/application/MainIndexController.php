<?php
namespace SabaiApps\Directories;

class MainIndexController extends Controller
{
    private static $_done = false;

    protected function _doExecute(Context $context)
    {
        // Prevent recursive routing
        if (!self::$_done) {
            self::$_done = true;
        }
    }
}