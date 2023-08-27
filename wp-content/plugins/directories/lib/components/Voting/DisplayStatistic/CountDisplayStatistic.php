<?php
namespace SabaiApps\Directories\Component\Voting\DisplayStatistic;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class CountDisplayStatistic extends Display\Statistic\AbstractStatistic
{
    protected $_type, $_downVote = false;
    
    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        if (substr($this->_name, -5) === '_down') {
            $this->_downVote = true;
            $this->_type = substr($this->_name, 7, -5); // remove voting_ prefix and _down suffix
        } else {
            $this->_type = substr($this->_name, 7); // remove voting_ prefix
        }
    }
    
    protected function _displayStatisticInfo(Entity\Model\Bundle $bundle)
    {
        if (!$bundle_type_info = (array)$this->_application->Entity_BundleTypeInfo($bundle, 'voting_enable')) return;

        if (!in_array($this->_type,  $bundle_type_info)) return;

        $info = $this->_application->Voting_Types_impl($this->_type)->votingTypeInfo();
        return array(
            'label' => $this->_downVote ? $info['label_statistic_down'] : $info['label_statistic'],
            'default_settings' => array(),
            'iconable' => false,
        );
    }
    
    public function displayStatisticRender(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (!$value = $entity->getSingleFieldValue('voting_' . $this->_type, '')) return;
        
        $voting_type = $this->_application->Voting_Types_impl($this->_type);
        return array(
            'number' => $voting_type->votingTypeFormat($value, $this->_downVote ? 'num_down' : 'num'),
            'format' => $voting_type->votingTypeFormat($value, $this->_downVote ? 'format_down' : 'format'),
            'icon' => $voting_type->votingTypeInfo($this->_downVote ? 'icon_down' : 'icon'),
            'color' => ['value' => $voting_type->votingTypeInfo($this->_downVote ? 'color_down' : 'color')],
        );
    }
}