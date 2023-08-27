<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;

class TokenHelper
{
    public function help(Application $application){} // we do not use help method so that sub helper methods can be overridden
    
    public function create(Application $application, $tokenId, $tokenLifetime = 1800, $reobtainable = false)
    {
        return \SabaiApps\Framework\Token::create($tokenId, $tokenLifetime, $reobtainable)->getValue();
    }
    
    public function html(Application $application, $tokenId, $tokenLifetime = 1800, $reobtainable = false, $tokenName = Request::PARAM_TOKEN)
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s" id="%s" />',
            $application->H($tokenName),
            $application->Form_Token_create($tokenId, $tokenLifetime, $reobtainable),
            'drts-' . strtolower(str_replace(array('_', ' '), '-', $application->H($tokenId))) . '-token'
        );
    }
    
    public function validate(Application $application, $tokenValue, $tokenId, $reuseable = false)
    {
        return \SabaiApps\Framework\Token::validate($tokenValue, $tokenId, $reuseable);
    }
}