<?php
namespace SabaiApps\Directories\Component\WordPressContent\FrontendSubmitRestrictor;

use SabaiApps\Directories\Component\FrontendSubmit\Restrictor\AbstractRestrictor;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Exception\RuntimeException;
use SabaiApps\Directories\Component\Form;

class RoleFrontendSubmitRestrictor extends AbstractRestrictor
{
    protected function _frontendsubmitRestrictorInfo()
    {
        return [
            'label' => __('Restrict by WordPress user role', 'directories'),
            'default_settings' => [],
        ];
    }

    protected function _frontendsubmitRestrictorLimitSettingsForm(array $bundles, array $settings, array $parents = [])
    {
        $form = [
            '#element_validate' => [[__CLASS__, '_validateLimit']],
        ];
        $roles = $this->_application->getPlatform()->getUserRoles();
        $roles['_guest_'] = '— ' . __('Guest', 'directories') . ' —';
        $admin_roles = array_keys($this->_application->getPlatform()->getAdministratorRoles());
        foreach (array_keys($roles) as $role_name) {
            // Skip if admin role
            if (in_array($role_name, $admin_roles)) continue;

            if ($role_name !== '_guest_'
                && !get_role($role_name)
            ) continue;

            $form[$role_name] = [
                '#title' => $roles[$role_name],
                '#horizontal' => true,
            ];

            foreach ($bundles as $bundle_name => $bundle_label) {
                // Check submission perm
                $perm = 'entity_create_' . $bundle_name;
                if ($role_name !== '_guest_') {
                    if (!get_role($role_name)->has_cap('drts_' . $perm)) continue;
                } else {
                    if (!$this->_application->getPlatform()->guestHasPermission($perm)) continue;
                }

                $form[$role_name][$bundle_name] = [
                    '#type' => 'slider',
                    '#title' => $bundle_label,
                    '#default_value' => empty($settings[$role_name][$bundle_name]) ? 0 : $settings[$role_name][$bundle_name],
                    '#min_value' => 0,
                    '#max_value' => $this->_limitMax,
                    '#step' => $this->_limitStep,
                    '#min_text' => __('Unlimited', 'directories'),
                    '#integer' => true,
                    '#horizontal' => true,
                ];
            }
        }

        return $form;
    }

    public static function _validateLimit(Form\Form $form, &$value, $element)
    {
        foreach (array_keys($value) as $role) {
            if (empty($value[$role])) unset($value[$role]);
        }
    }

    protected function _frontendsubmitRestrictorLimit(Bundle $bundle, array $settings, $userId)
    {
        // Get user roles
        if (empty($userId)) {
            $roles = ['_guest_'];
        } else {
            if (!$user_data = get_userdata($userId)) {
                throw new RuntimeException('Failed fetching user data for user ID:' . $userId);
            }

            $roles = $user_data->roles;
        }
        // Get maximum limit
        $limit = -1; // start with no limit
        foreach ($roles as $role) {
            if (!empty($settings[$role][$bundle->name])
                && $settings[$role][$bundle->name] > $limit
            ) {
                $limit = $settings[$role][$bundle->name];
            }
        }
        return $limit;
    }
}