<?php
namespace SabaiApps\Directories\Component\Faker;

interface IGenerators
{
    public function fakerGetGeneratorNames();
    public function fakerGetGenerator($name);
}