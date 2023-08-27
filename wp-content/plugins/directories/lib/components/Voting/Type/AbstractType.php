<?php
namespace SabaiApps\Directories\Component\Voting\Type;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Voting\Model\Vote;
use SabaiApps\Directories\Link;
use SabaiApps\Directories\Request;

abstract class AbstractType implements IType
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function votingTypeInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = $this->_application->Filter('voting_type_info', (array)$this->_votingTypeInfo(), [$this->_name]);
            $this->_info += [
                'table_sortable_headers' => ['value', 'created'],
                'table_timestamp_headers' => ['created'],
                'table_default_header' => 'created',
            ];
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function votingTypeButtonSettingsForm(Bundle $bundle, array $settings, array $parents = [])
    {
        return [];
    }

    public function votingTypeOnDisplayButtonLink(Link $link, IEntity $entity, array $settings, $displayName){}
    public function votingTypeOnVoteEntity(IEntity $entity, array &$response, Request $request){}
    abstract protected function _votingTypeInfo();

    public function votingTypeTableRow(Vote $vote, array $tableHeaders)
    {
        $ret = [];
        if (isset($tableHeaders['created'])) {
            $ret['created'] = $this->_application->System_Date_datetime($vote->created, true);
        }
        if (isset($tableHeaders['author'])) {
            $ret['author'] = $this->_application->UserIdentityHtml($vote->User, 'link_thumb_s');
        }
        if (isset($tableHeaders['value'])) {
            $ret['value'] = '<i class="' . $this->votingTypeInfo('icon') . '"></i> ' . $vote->value;
        }
        return $ret;
    }
}
