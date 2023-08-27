<?php
namespace SabaiApps\Directories\Component\Voting;

interface ITypes
{
    public function votingGetTypeNames();
    public function votingGetType($name);
}