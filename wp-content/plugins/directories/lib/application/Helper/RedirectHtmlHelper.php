<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class RedirectHtmlHelper
{
    public function help(Application $application, $url, $html = '', $delay = 2000)
    {
        printf(
            '<script type="text/javascript">document.addEventListener("DOMContentLoaded", function(e) {
    setTimeout(function () {
        window.location.replace("%2$s");
    }, %3$d);
});</script>
%4$s
<div class="drts-redirect-btn">
    <p>%5$s</p>
    <div>
        <a href="%2$s" class="%1$sbtn %1$sbtn-secondary">%6$s</a>
    </div>
</div>',
            DRTS_BS_PREFIX,
            $url,
            $delay,
            strlen($html) ? '<div class="drts-redirect-msg">' . $html . '</div>' : '',
            $application->H(__('Redirecting... If you are not redirected automatically, please click the button below to continue.', 'directories')),
            $application->H(__('Continue &raquo;', 'directories'))
        );
    }
}
