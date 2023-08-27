<?php
namespace SabaiApps\Directories;

class Template extends \SabaiApps\Framework\Template
{
    private $_application;

    public function __construct(Application $application, array $dirs = [])
    {
        parent::__construct($dirs);
        $this->_application = $application;
    }

    public function __call($name, $args)
    {
        return $this->_application->callHelper($name, $args);
    }
}