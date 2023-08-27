<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Restrictor;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\User\AbstractIdentity;

abstract class AbstractRestrictor implements IRestrictor
{
    protected $_application, $_name, $_info, $_limitMax, $_limitStep;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
        if ($limit_max = (int)$application->Filter('frontendsubmit_restrictor_limit_max', null)) {
            $this->_limitMax = $limit_max;
            if ($this->_limitMax >= 200) {
                $this->_limitStep = 10;
            } elseif ($this->_limitMax >= 100) {
                $this->_limitStep = 5;
            }
        } else {
            $this->_limitMax = 50;
            $this->_limitStep = 1;
        }
    }

    public function frontendsubmitRestrictorInfo($key = null)
    {
        if (!isset($this->_info)) $this->_info = (array)$this->_frontendsubmitRestrictorInfo();

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function frontendsubmitRestrictorEnabled()
    {
        return true;
    }

    public function frontendsubmitRestrictorSettingsForm(array $bundles, array $settings, array $parents = [])
    {
        $form = [
            'limit' => [
                '#title' => __('Max number of submissions allowed', 'directories-frontend'),
                '#class' => 'drts-form-label-lg',
                '#weight' => 1,
            ],
            'other' => [
                '#title' => __('Other Settings', 'directories-frontend'),
                '#class' => 'drts-form-label-lg',
                '#weight' => 99,
                'exclude_trash' => [
                    '#type' => 'checkbox',
                    '#title' => __('Exclude items in trash', 'directories-frontend'),
                    '#weight' => 99,
                    '#default_value' => !isset($settings['other']['exclude_trash']) || !empty($settings['other']['exclude_trash']),
                    '#horizontal' => true,
                ],
            ],
        ];
        $form['limit'] += $this->_frontendsubmitRestrictorLimitSettingsForm(
            $bundles,
            isset($settings['limit']) ? $settings['limit'] : [],
            array_merge($parents, ['limit'])
        );

        return $form;
    }

    public function frontendsubmitRestrictorIsAllowed(Bundle $bundle, array $settings, $identity, $parentEntityId = null)
    {
        if (empty($settings['limit'])) return true; // no restriction settings

        if (!$identity instanceof AbstractIdentity
            || $identity->isAnonymous()
        ) {
            $user_id = 0;
        } else {
            // Do not restrict administrators
            if ($this->_application->getPlatform()->isAdministrator($identity->id)) return true;

            $user_id = $identity->id;
        }

        $limit = $this->_frontendsubmitRestrictorLimit($bundle, $settings['limit'], $user_id);
        if ($limit === -1) return true; // no limit
        if (empty($limit)) return false; // no submission allowed

        $query = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)
            ->fieldIs('author', $user_id);
        if (!empty($bundle->info['parent'])) {
            if (empty($parentEntityId)) {
                throw new Exception\RuntimeException('Child entity requires a parent entity ID for checking submit restriction.');
            }
            $query->fieldIs('parent', $parentEntityId);
        }
        $status = ['publish', 'pending', 'draft'];
        if (empty($settings['other']['exclude_trash'])) {
            $status[] = 'trash';
        }
        $query->fieldIsIn('status', $this->_application->Entity_Status($bundle->entitytype_name, $status));

        if (empty($user_id)) {
            $email = $identity instanceof AbstractIdentity ? $identity->email : $identity;
            if ((!$email = trim($email))
                || !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                $this->_application->logError('Submission restriction for guest users require an e-mail address.');
                return true;
            }

            $query->fieldIs('frontendsubmit_guest', $email, 'email');
        }

        $count = $query->count();

        return $count < $limit; // allow if below limit
    }

    abstract protected function _frontendsubmitRestrictorInfo();
    abstract protected function _frontendsubmitRestrictorLimitSettingsForm(array $bundles, array $settings, array $parents = []);
    abstract protected function _frontendsubmitRestrictorLimit(Bundle $bundle, array $settings, $userId);
}