<?php
namespace SabaiApps\Framework\Application;

interface IRoute
{
    public function __toString();
    public function isForward();
    public function getController();
    public function getControllerArgs();
    public function getControllerFile();
}