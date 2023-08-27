<?php
namespace SabaiApps\Directories\Component\Location\FieldRenderer;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\Renderer\AbstractRenderer;

class PlaceIdFieldRenderer extends AbstractRenderer
{
    protected static $_jsLoaded;

    protected function _fieldRendererInfo()
    {
        return [
            'label' => __('Google Maps place rating', 'directories-pro'),
            'field_types' => ['string'],
            'default_settings' => [],
            'inlineable' => true,
        ];
    }

    public function fieldRendererSupports(Bundle $bundle, IField $field)
    {
        $settings = $field->getFieldSettings();
        return isset($settings['char_validation'])
            && $settings['char_validation'] === 'location_googlemaps_place_id'
            && $this->_application->getComponent('Map')->getConfig('lib', 'map') === 'googlemaps';
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        if (!isset(self::$_jsLoaded)) {
            if (!$this->_application->Map_Api_load()) return;

            $this->_application->Location_Api_load(['location_places' => true]);
            $this->_application->getPlatform()->addJs('$(\'.drts-location-field-place-rating [data-place-id]\').each(function(){
    var $this = $(this);
    DRTS.Location.api.getPlaceRating(
        $this.data("place-id"), 
        function(rating, count){
            $this.find(".drts-voting-rating-average").text(rating).end()
                .find(".drts-voting-rating-count").text(count).end()
                .find(".drts-voting-rating-stars").removeClass("drts-voting-rating-stars-0").addClass("drts-voting-rating-stars-" + ((rating - (rating % 0.5)) * 10)).end()
                .css("visibility", "visible");
        },
        function (err) { console.log(err); $this.text(err.message).css("visibility", "visible"); }
    );
});', true);
        }
        $ret = [];
        foreach ($values as $key => $value) {
            $ret[] = '<span style="visibility:hidden;" data-place-id="' . $this->_application->H($value) . '">' . $this->_application->Voting_RenderRating(0, ['avg_first' => true, 'count' => 0]) . '</span>';
        }
        return [
            'class' => 'drts-location-field-place-rating',
            'html' => implode($settings['_separator'], $ret),
        ];
    }
}
