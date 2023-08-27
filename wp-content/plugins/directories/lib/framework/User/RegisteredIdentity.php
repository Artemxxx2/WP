<?php
namespace SabaiApps\Framework\User;

class RegisteredIdentity extends AbstractIdentity
{
    final public function isAnonymous()
    {
        return false;
    }
}