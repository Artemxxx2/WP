<?php
namespace SabaiApps\Directories\Component\WordPressContent\Notification;

use SabaiApps\Directories\Component\Entity;

interface INotification
{
    public function wpNotificationInfo();
    public function wpNotificationSupports(Entity\Model\Bundle $bundle);
    public function wpNotificationSubject(Entity\Model\Bundle $bundle);
    public function wpNotificationBody(Entity\Model\Bundle $bundle);
}