<?php
namespace SabaiApps\Directories\Component\WordPressContent\FrontendSubmitRestrictor;

use SabaiApps\Directories\Component\FrontendSubmit\Restrictor\AbstractRestrictor;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class WooCommerceMembershipsFrontendSubmitRestrictor extends AbstractRestrictor
{
    protected function _frontendsubmitRestrictorInfo()
    {
        return [
            'label' => __('Restrict by WooCommerce Memberships membership plan', 'directories'),
            'default_settings' => [],
        ];
    }

    public function frontendsubmitRestrictorEnabled()
    {
        return function_exists('wc_memberships');
    }

    protected function _frontendsubmitRestrictorLimitSettingsForm(array $bundles, array $settings, array $parents = [])
    {
        $form = $memberships = [];
        foreach (wc_memberships_get_membership_plans() as $membership) {
            $memberships[$membership->get_slug()] = $membership->get_name();
        }
        $memberships[0] = '— ' . __('No membership', 'directories') . ' —';
        foreach (array_keys($memberships) as $membership_name) {
            $form[$membership_name] = [
                '#title' => $memberships[$membership_name],
                '#horizontal' => true,
            ];
            foreach ($bundles as $bundle_name => $bundle_label) {
                $form[$membership_name][$bundle_name] = [
                    '#title' => $bundle_label,
                    '#horizontal' => true,
                    '#type' => 'slider',
                    '#default_value' => isset($settings[$membership_name][$bundle_name]) ? $settings[$membership_name][$bundle_name] : -1,
                    '#min_value' => -1,
                    '#max_value' => $this->_limitMax,
                    '#step' => $this->_limitStep,
                    '#min_text' => __('Unlimited', 'directories'),
                    '#integer' => true,
                ];
            }
        }

        return $form;
    }

    protected function _frontendsubmitRestrictorLimit(Bundle $bundle, array $settings, $userId)
    {
        // Get user memberships
        if (empty($userId)
            || (!$memberships = wc_memberships_get_user_active_memberships())
        ) {
            // Anonymous or no membership
            return isset($settings[0][$bundle->name]) ? $settings[0][$bundle->name] : -1;
        }

        // Get maximum limit
        $limit = -1; // start with no limit
        foreach ($memberships as $membership) {
            $membership_name = $membership->get_plan()->get_slug();
            $membership_limit = isset($settings[$membership_name][$bundle->name]) ? $settings[$membership_name][$bundle->name] : -1;

            // Bail out if one of the assigned memberships has no restriction enabled.
            if ($membership_limit === -1) {
                $limit = -1;
                break;
            }

            if ($membership_limit > $limit) {
                $limit = $membership_limit;
            }
        }
        return $limit;
    }
}