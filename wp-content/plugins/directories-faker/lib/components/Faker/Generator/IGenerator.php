<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

interface IGenerator
{
    public function fakerGeneratorInfo($key = null);
    public function fakerGeneratorSettingsForm(IField $field, array $settings, array $parents = []);
    public function fakerGeneratorGenerate(IField $field, array $settings, array &$values, array &$formStorage);
    public function fakerGeneratorSupports(Bundle $bundle, IField $field);
}