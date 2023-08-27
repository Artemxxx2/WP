<?php
namespace SabaiApps\Directories\Component\Display\Model;

class ElementGateway extends Base\ElementGateway
{
    public function getElementId($displayId, $elementName)
    {
        $sql = sprintf(
            'SELECT MAX(element_element_id) + 1'
                . ' FROM %sdisplay_element'
                . ' WHERE element_display_id = %d AND element_name = %s',
            $this->_db->getResourcePrefix(),
            $displayId,
            $this->_db->escapeString($elementName)
        );

        return ($id = $this->_db->query($sql)->fetchSingle()) ? $id : 1;
    }
}