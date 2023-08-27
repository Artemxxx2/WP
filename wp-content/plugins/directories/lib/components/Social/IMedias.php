<?php
namespace SabaiApps\Directories\Component\Social;

interface IMedias
{
    public function socialMediaNames();
    public function socialMediaInfo($name);
    public function socialMediaUrl($name, $value);
}