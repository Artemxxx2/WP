<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class SubmissionHelper
{
    public function bundles(Application $application, $bundleType)
    {
        $bundles = $application->Entity_Bundles_addable($bundleType);
        foreach (array_keys($bundles) as $bundle_name) {
            if ($bundle = $application->Entity_Bundle($bundle_name)) {
                if (empty($bundle->info['parent'])
                    && isset($bundle->info['frontendsubmit_enable'])
                    && empty($bundle->info['frontendsubmit_enable'])
                ) {
                    unset($bundles[$bundle_name]);
                }
            } else {
                unset($bundles[$bundle_name]);
            }
        }
        asort($bundles);
        return $application->Filter('frontendsubmit_submittable_bundles', $bundles, [$bundleType]);
    }

    public function isGuestInfoRequired(Application $application, array &$guestInfoSubmitted = [])
    {
        $config = $application->getComponent('FrontendSubmit')->getConfig('guest');

        // Guest name
        if (!empty($config['collect_name'])
            || !isset($config['collect_name']) // compat with <1.2.58
        ) {
            if (!isset($guestInfoSubmitted['name'])
                || !strlen($guestInfoSubmitted['name'] = trim($guestInfoSubmitted['name']))
            ) {
                if (!empty($config['require_name']) || !isset($config['require_name'])) return true;
            }
        }
        // Guest e-mail address
        if (!empty($config['collect_email'])) {
            if (!isset($guestInfoSubmitted['email'])
                || !strlen($guestInfoSubmitted['email'] = trim($guestInfoSubmitted['email']))
            ) {
                if (!empty($config['require_email'])) return true;
            } else {
                // Validate
                try {
                    $guestInfoSubmitted['email'] = $application->Form_Validate_email($guestInfoSubmitted['email'], !empty($config['check_mx']), !empty($config['check_exists']));
                } catch (Exception\IException $e) {
                    return true;
                }
            }
        }
        // Guest website URL
        if (!empty($config['collect_url'])) {
            if (!isset($guestInfoSubmitted['url'])
                || !strlen($guestInfoSubmitted['url'] = trim($guestInfoSubmitted['url']))
            ) {
                if (!empty($config['require_url'])) return true;
            } else {
                // Validate
                try {
                    $guestInfoSubmitted['url'] = $application->Form_Validate_url($guestInfoSubmitted['url']);
                } catch (Exception\IException $e) {
                    return true;
                }
            }
        }

        return false;
    }

    public function addEntityLink(Application $application, $bundle, array $options = null)
    {
        if ((!$bundle = $application->Entity_Bundle($bundle))
            || !empty($bundle->info['parent'])
            || $bundle->entitytype_name !== 'post'
        ) throw new Exception\RuntimeException('Invalid bundle.');

        $params = ['bundle' => $bundle->name];
        $slug = '/' . $application->FrontendSubmit_AddEntitySlug($bundle);
        if ($application->getUser()->isAnonymous()
            && ((!$submittable_bundles = $application->FrontendSubmit_Submission_bundles($bundle->type))
                || !isset($submittable_bundles[$bundle->name])
                || $application->FrontendSubmit_Submission_isGuestInfoRequired()
            )
        ) {
            $params['redirect_action'] = 'add';
            $params['redirect_bundle'] = $bundle->name;
            $url = $application->LoginUrl((string)$application->Url($slug, $params));
        } else {
            $url = $application->Url($slug, $params);
        }
        $label = isset($options['label']) ? $options['label'] : $bundle->getLabel('add');
        $link_options = $attr = [];
        if (!isset($options['button'])) return $application->LinkTo($label, $url, $link_options, $attr);

        $application->getPlatform()->loadDefaultAssets(false, true);
        $link_options['icon'] = empty($options['icon']) ? 'fa-solid fa-pen' : $options['icon'];
        $attr['class'] = $application->System_Util_btnClass($options['button'] + ['color' => 'primary', 'size' => 'lg'], '', true);

        return $application->LinkTo($label, $url, $link_options, $attr);
    }
}