<?php
namespace SabaiApps\Directories\Component\System\Model;

class Route extends Base\Route
{
    public function toArray()
    {
        return array(
            'path' => $this->path,
            'controller' => $this->controller,
            'controller_component' => $this->controller_component,
            'forward' => $this->forward,
            'component' => $this->component,
            'type' => $this->type,
            'title_callback' => $this->title_callback,
            'access_callback' => $this->access_callback,
            'callback_path' => $this->callback_path,
            'callback_component' => $this->callback_component,
            'weight' => $this->weight,
            'format' => $this->format,
            'method' => $this->method,
            'data' => $this->data,
            'language' => $this->language,
        );
    }
}

class RouteRepository extends Base\RouteRepository
{
}