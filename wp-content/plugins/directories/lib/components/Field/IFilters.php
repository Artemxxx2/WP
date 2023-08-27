<?php
namespace SabaiApps\Directories\Component\Field;

interface IFilters
{
    public function fieldGetFilterNames();
    public function fieldGetFilter($filterName);
}