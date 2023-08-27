<?php
namespace SabaiApps\Directories\Component\View\Model;

class View extends Base\View
{
    public function getLabel()
    {
        return $this->data['label'];
    }
}

class ViewRepository extends Base\ViewRepository
{
}