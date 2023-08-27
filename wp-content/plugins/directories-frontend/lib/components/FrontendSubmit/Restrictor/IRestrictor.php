<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Restrictor;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Framework\User\AbstractIdentity;
use SabaiApps\Directories\Exception;

interface IRestrictor
{
    public function frontendsubmitRestrictorInfo($key = null);
    public function frontendsubmitRestrictorEnabled();
    public function frontendsubmitRestrictorSettingsForm(array $bundles, array $settings, array $parents = []);
    /**
     * @param Bundle $bundle
     * @param AbstractIdentity|string $identity
     * @param array $settings
     * @param int|null $parentEntityId
     * @return bool
     * @throws Exception\IException
     */
    public function frontendsubmitRestrictorIsAllowed(Bundle $bundle, array $settings, $identity, $parentEntityId = null);
}