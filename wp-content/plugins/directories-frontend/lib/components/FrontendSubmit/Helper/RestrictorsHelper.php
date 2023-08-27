<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\FrontendSubmit\Restrictor\IRestrictor;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\User\RegisteredIdentity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class RestrictorsHelper
{
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$restrictors = $application->getPlatform()->getCache('frontendsubmit_restrictors'))
        ) {
            $restrictors = [];
            foreach ($application->InstalledComponentsByInterface('FrontendSubmit\IRestrictors') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;

                foreach ($application->getComponent($component_name)->frontendsubmitGetRestrictorNames() as $restrictor_name) {
                    if (!$restrictor = $application->getComponent($component_name)->frontendsubmitGetRestrictor($restrictor_name)) continue;

                    $restrictors[$restrictor_name] = $component_name;
                }
            }
            $application->getPlatform()->setCache($restrictors, 'frontendsubmit_restrictors');
        }

        return $restrictors;
    }

    private $_impls = [];

    /**
     * Gets an implementation of IRestrictor interface for a given restrictor name
     * @param Application $application
     * @param string $restrictor
     * @param bool $returnFalse
     * @return IRestrictor
     * @throws Exception\IException
     */
    public function impl(Application $application, $restrictor, $returnFalse = false)
    {
        if (!isset($this->_impls[$restrictor])) {
            if ((!$restrictors = $application->FrontendSubmit_Restrictors())
                || !isset($restrictors[$restrictor])
                || !$application->isComponentLoaded($restrictors[$restrictor])
            ) {
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid restrictor: %s', $restrictor));
            }
            $this->_impls[$restrictor] = $application->getComponent($restrictors[$restrictor])->frontendsubmitGetRestrictor($restrictor);
        }

        return $this->_impls[$restrictor];
    }

    /**
     * @param Application $application
     * @param Bundle $bundle
     * @param RegisteredIdentity|string $identity
     * @param int|null $parentEntityId
     * @return bool
     * @throws Exception\IException
     */
    public function isAllowed(Application $application, Bundle $bundle, $identity, $parentEntityId = null)
    {
        $result = true;
        $restrict_config = $application->getComponent('FrontendSubmit')->getConfig('restrict');
        if (!empty($restrict_config['type'])) {
            $restrictor = $this->impl($application, $restrict_config['type']);
            $settings = isset($restrict_config['settings'][$restrict_config['type']]) ? (array)$restrict_config['settings'][$restrict_config['type']] : [];
            $settings += (array)$restrictor->frontendsubmitRestrictorInfo('default_settings');
            if ($restrictor->frontendsubmitRestrictorEnabled()
                && !$restrictor->frontendsubmitRestrictorIsAllowed($bundle, $settings, $identity, $parentEntityId)
            ) {
                $result = false;
            }
        }

        return $result;
    }
}