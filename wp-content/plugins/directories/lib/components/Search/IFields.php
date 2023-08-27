<?php
namespace SabaiApps\Directories\Component\Search;

interface IFields
{
    public function searchGetFieldNames();
    public function searchGetField($name);
}