<?php
namespace SabaiApps\Directories\Component\Map\Helper;

use SabaiApps\Directories\Application;

class GdprHelper
{
    public function isConsentRequired(Application $application)
    {
        if (!$application->getComponent('Map')->getConfig('map', 'require_consent')) return false;

        if (!$this->_isConsentRequired($application)) return false;

        return $application->Filter('map_gdpr_consent_required', true);
    }

    protected function _isConsentRequired(Application $application)
    {
        if ($application->getUser()->isAnonymous()) {
            return !(bool)$application->System_Cookie('map_gdpr_accept');
        }

        if ($application->getPlatform()->getEntityMeta('user', $application->getUser()->id, 'map_gdpr_accept')) {
            return false;
        }
        if ((bool)$application->System_Cookie('map_gdpr_accept')) {
            $application->getPlatform()->setEntityMeta('user', $application->getUser()->id, 'map_gdpr_accept', true);
            return false;
        }

        return true;
    }

    public function consentForm(Application $application)
    {
        $pp = $application->Map_Api()->mapApiInfo('privacy_policy');
        return sprintf(
            '<div class="%1$salert %1$salert-warning">
    <div class="%1$smb-2">%2$s</div>
    <div class="%1$smb-2">%3$s</div>
    <button type="button" class="%1$smt-3 %1$sbtn %1$sbtn-block %1$sbtn-secondary" onclick="DRTS.setCookie(\'%4$s\', true, 7, true);">%5$s</button>
</div>',
            DRTS_BS_PREFIX,
            $application->H(__('To protect your personal data, your connection to the embedded map has been blocked.', 'directories')),
            $application->Htmlize(sprintf(
                __('Click the <strong>%s</strong> button below to load the map. By loading the map you accept the privacy policy of %s.', 'directories'),
                $btn_label = $application->H(__('Load map', 'directories')),
                '<a href="' . $pp['url'] . '" target = "_blank" rel = "nofollow">' . $application->H($pp['provider']) . '</a>'
            ), true),
            $application->System_Cookie_name('map_gdpr_accept'),
            $btn_label
        );
    }
}