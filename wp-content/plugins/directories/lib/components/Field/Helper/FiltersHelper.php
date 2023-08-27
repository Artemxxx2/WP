<?php
namespace SabaiApps\Directories\Component\Field\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class FiltersHelper
{
    /**
     * Returns all available field filters
     * @param Application $application
     */
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$filters = $application->getPlatform()->getCache('field_filters'))
        ) {
            $filters = [];
            foreach ($application->InstalledComponentsByInterface('Field\IFilters') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->fieldGetFilterNames() as $filter_name) {
                    if (!$application->getComponent($component_name)->fieldGetFilter($filter_name)) {
                        continue;
                    }
                    $filters[$filter_name] = $component_name;
                }
            }
            $filters = $application->Filter('field_filters', $filters);
            $application->getPlatform()->setCache($filters, 'field_filters', 0);
        }

        return $filters;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of SabaiApps\Directories\Component\Field\Filter\IFilter interface for a given filter type
     * @param Application $application
     * @param string $filter
     */
    public function impl(Application $application, $filter, $returnFalse = false)
    {
        if (!isset($this->_impls[$filter])) {
            $filters = $this->help($application);
            // Valid filter type?
            if (!isset($filters[$filter])
                || (!$application->isComponentLoaded($filters[$filter]))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid filter type: %s', $filter));
            }
            $this->_impls[$filter] = $application->getComponent($filters[$filter])->fieldGetFilter($filter);
        }

        return $this->_impls[$filter];
    }
}