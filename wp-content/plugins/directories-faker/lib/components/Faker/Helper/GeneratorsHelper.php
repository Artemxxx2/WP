<?php
namespace SabaiApps\Directories\Component\Faker\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class GeneratorsHelper
{
    /**
     * Returns all available field generators
     * @param  $application
     */
    public function help(Application $application, $byFieldType = false, $useCache = true)
    {
        $cache_id = $byFieldType ? 'faker_generators_by_field_type' : 'faker_generators';
        if (!$useCache
            || (!$ret = $application->getPlatform()->getCache($cache_id))
        ) {
            $generators = $generators_by_field_type = [];
            foreach ($application->InstalledComponentsByInterface('Faker\IGenerators') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->fakerGetGeneratorNames() as $generator_name) {
                    if (!$generator = $application->getComponent($component_name)->fakerGetGenerator($generator_name)) {
                        continue;
                    }
                    
                    $generators[$generator_name] = $component_name;
                    foreach ((array)$generator->fakerGeneratorInfo('field_types') as $field_type) {
                        $generators_by_field_type[$field_type][] = $generator_name;
                    }
                }
            }
            $application->getPlatform()->setCache($generators, 'faker_generators')
                ->setCache($generators_by_field_type, 'faker_generators_by_field_type');
            
            $ret = $byFieldType ? $generators_by_field_type : $generators;
        }

        return $ret;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of Faker\IGenerator interface for a given generator type
     * @param Application $application
     * @param string $generator
     * @return SabaiApps\Directories\Component\Faker\Generator\IGenerator
     */
    public function impl(Application $application, $generator, $returnFalse = false)
    {
        if (!isset($this->_impls[$generator])) {
            $generators = $this->help($application);
            // Valid generator type?
            if (!isset($generators[$generator])
                || (!$application->isComponentLoaded($generators[$generator]))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid generator: %s', $generator));
            }
            $this->_impls[$generator] = $application->getComponent($generators[$generator])->fakerGetGenerator($generator);
        }

        return $this->_impls[$generator];
    }
}