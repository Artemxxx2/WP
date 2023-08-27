<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;

class StatisticsHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, $useCache = true)
    {
        if (!$useCache
            || (!$stats = $application->getPlatform()->getCache('display_statistics_' . $bundle->type))
        ) {
            $stats = [];
            foreach ($application->InstalledComponentsByInterface('Display\IStatistics') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ((array)$application->getComponent($component_name)->displayGetStatisticNames($bundle) as $stat_name) {
                    if (!$application->getComponent($component_name)->displayGetStatistic($stat_name)) {
                        continue;
                    }
                    $stats[$stat_name] = $component_name;
                }
            }
            $statistics = $application->Filter('display_statistics', $stats, array($bundle));
            $application->getPlatform()->setCache($statistics, 'display_statistics_' . $bundle->type, 0);
        }

        return $stats;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of SabaiApps\Directories\Component\Display\Statistic\IStatistic interface for a given stat name
     * @param Application $application
     * @param string $stat
     */
    public function impl(Application $application, Entity\Model\Bundle $bundle, $stat, $returnFalse = false)
    {
        if (!isset($this->_impls[$stat])) {            
            if ((!$stats = $this->help($application, $bundle))
                || !isset($stats[$stat])
                || !$application->isComponentLoaded($stats[$stat])
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid statistic: %s', $stat));
            }
            $this->_impls[$stat] = $application->getComponent($stats[$stat])->displayGetStatistic($stat);
        }

        return $this->_impls[$stat];
    }
}