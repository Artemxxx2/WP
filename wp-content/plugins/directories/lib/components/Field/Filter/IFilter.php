<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

interface IFilter
{
    public function fieldFilterInfo($key = null);
    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = []);
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, $autoSubmit = true, array $parents = []);
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null);
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts);
    public function fieldFilterSupports(IField $field);
    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel);
}