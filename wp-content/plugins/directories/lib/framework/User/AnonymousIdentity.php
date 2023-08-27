<?php
namespace SabaiApps\Framework\User;

class AnonymousIdentity extends AbstractIdentity
{
    final public function isAnonymous()
    {
        return true;
    }
}