<?php
namespace SabaiApps\Directories\Component\Voting\Type;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Voting\Model\Vote;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Link;
use SabaiApps\Directories\Request;

interface IType
{
    public function votingTypeInfo($name);
    public function votingTypeFormat(array $value, $format = null);
    public function votingTypeTableRow(Vote $vote, array $tableHeaders);
    public function votingTypeButtonSettingsForm(Bundle $bundle, array $settings, array $parents = []);
    public function votingTypeOnDisplayButtonLink(Link $link, IEntity $entity, array $settings, $displayName);
    public function votingTypeOnVoteEntity(IEntity $entity, array &$response, Request $request);
}