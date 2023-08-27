<?php
namespace SabaiApps\Directories\Component\Voting\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class TypesHelper
{
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$types = $application->getPlatform()->getCache('voting_types'))
        ) {
            $types = [];
            foreach ($application->InstalledComponentsByInterface('Voting\ITypes') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->votingGetTypeNames() as $type) {
                    if (!$application->getComponent($component_name)->votingGetType($type)) continue;
                    
                    $types[$type] = $component_name;
                }
            }
            $application->getPlatform()->setCache($types, 'voting_types', 0);
        }
        return $types;
    }
    
    private $_impls = [];
    
    public function impl(Application $application, $type, $returnFalse = false, $useCache = true)
    {
        if (!isset($this->_impls[$type])) {
            $types = $this->help($application, $useCache);
            if (!isset($types[$type])
                || !$application->isComponentLoaded($types[$type])
            ) {
                if ($returnFalse) {
                    $application->logError(sprintf('Invalid vote type: %s', $type));
                    return false;
                }
                throw new Exception\UnexpectedValueException(sprintf('Invalid vote type: %s', $type));
            }
            $this->_impls[$type] = $application->getComponent($types[$type])->votingGetType($type);
        }

        return $this->_impls[$type];
    }
}