<?php
namespace SabaiApps\Directories\Component\FrontendSubmit;

interface IRestrictors
{
    public function frontendsubmitGetRestrictorNames();
    public function frontendsubmitGetRestrictor($name);
}