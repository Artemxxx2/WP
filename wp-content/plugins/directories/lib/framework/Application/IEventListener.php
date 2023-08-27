<?php
namespace SabaiApps\Framework\Application;

interface IEventListener
{
    public function handleEvent($eventType, array $eventArgs);
}