<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class GravatarUrlHelper
{
    public function help(Application $application, $email, $size = 96, $default = 'mm', $rating = null, $secure = false)
    {       
        $url = sprintf(
            '%s://www.gravatar.com/avatar/%s?s=%d&d=%s',
            $secure ? 'https' : 'http',
            md5(strtolower($email)),
            $size,
            urlencode($default)
        );
        if (isset($rating)) $url .= '&r=' . urlencode($rating);

        return $url;
    }
}