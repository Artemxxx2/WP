<?php
namespace SabaiApps\Directories\Component\Social\Helper;

use SabaiApps\Directories\Application;

class MediasHelper
{
    /**
     * Returns all available social medias
     * @param Application $application
     */
    public function help(Application $application)
    {
        if (!$medias = $application->getPlatform()->getCache('social_medias')) {
            $medias = [];
            foreach ($application->InstalledComponentsByInterface('Social\IMedias') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->socialMediaNames() as $media_name) {
                    if (!$media_info = $application->getComponent($component_name)->socialMediaInfo($media_name)) {
                        continue;
                    }
                    $medias[$media_name] = array('component' => $component_name) + $media_info;
                }
            }
            $application->getPlatform()->setCache($medias, 'social_medias');
        }
        return $medias;
    }
}