<?php
namespace SabaiApps\Directories\Component\Search\Field;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\Query;

interface IField
{
    public function searchFieldInfo($key = null);
    public function searchFieldSupports(Bundle $bundle);
    public function searchFieldSettingsForm(Bundle $bundle, array $settings, array $parents = []);
    public function searchFieldForm(Bundle $bundle, array $settings, $request = null, array $requests = null, array $parents = []);
    public function searchFieldIsSearchable(Bundle $bundle, array $settings, &$value, array $requests = null);

    public function searchFieldSearch(Bundle $bundle, Query $query, array $settings, $value, $sort, array &$sorts);

    public function searchFieldLabels(Bundle $bundle, array $settings, $value);
    public function searchFieldUnsearchableLabel(Bundle $bundle, array $settings, $value);
}