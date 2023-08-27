<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Display\Model\Display;
use SabaiApps\Directories\Component\Display\Model\Element;

interface IElement
{
    public function displayElementInfo(Bundle $bundle, $key = null);
    public function displayElementSupports(Bundle $bundle, Display $display);
    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = []);
    public function displayElementRender(Bundle $bundle, array $element, $var);
    public function displayElementTitle(Bundle $bundle, array $element);
    public function displayElementAdminAttr(Bundle $bundle, array $settings);
    public function displayElementIsNoTitle(Bundle $bundle, array $element);
    public function displayElementIsEnabled(Bundle $bundle, array $element, Display $display);
    public function displayElementIsDisabled(Bundle $bundle, array $settings);
    public function displayElementIsInlineable(Bundle $bundle, array $settings);
    public function displayElementIsPreRenderable(Bundle $bundle, array &$element);
    public function displayElementPreRender(Bundle $bundle, array $element, &$var);
    public function displayElementOnCreate(Bundle $bundle, array &$data, $weight, Display $display, $elementName, $elementId);
    public function displayElementOnUpdate(Bundle $bundle, array &$data, Element $element);
    public function displayElementOnExport(Bundle $bundle, array &$data);
    public function displayElementOnRemoved(Bundle $bundle, array $settings, $elementName, $elementId);
    public function displayElementOnPositioned(Bundle $bundle, array $settings, $weight);
    public function displayElementOnSaved(Bundle $bundle, Element $element);
    public function displayElementReadableInfo(Bundle $bundle, Element $element);
    //public function displayElementCreateChildren(Display $display, array $settings, $parentId);
}