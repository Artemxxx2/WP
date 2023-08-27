<?php
namespace SabaiApps\Directories\Component\Field\Type;

interface IImage
{
    public function fieldImageGetUrl($value, $size);
    public function fieldImageGetIconUrl($value, $size = null);
    public function fieldImageGetFullUrl($value);
    public function fieldImageGetTitle($value);
    public function fieldImageGetAlt($value);
}