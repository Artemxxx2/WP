<?php
namespace SabaiApps\Directories\Component\Directory\Type;

interface IType
{
    public function directoryInfo($key);
    public function directoryContentTypeInfo($contentType);    
}