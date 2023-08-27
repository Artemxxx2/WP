<?php
namespace SabaiApps\Directories\Component\WordPressContent;

interface INotifications
{
    public function wpGetNotificationNames();
    public function wpGetNotification($name);
}