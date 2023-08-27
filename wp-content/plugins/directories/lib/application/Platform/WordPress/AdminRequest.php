<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Directories\Request;

class AdminRequest extends Request
{
    /**
     * Request parameters used by WordPress which should not be used in drts context to prevent conflicts
     *
     * @var array
     */
    private $_reservedParams = array('page', 'noheader');

    public function get($name)
    {
        $this->_checkParam($name);

        return parent::get($name);
    }

    protected function _as($type, $name, $default, array $include = null, array $exclude = null)
    {
        $this->_checkParam($name);

        return parent::_as($type, $name, $default, $include, $exclude);
    }

    private function _checkParam($name)
    {
        // Trigger warning if trying to access a reserved parameter
        if (in_array($name, $this->_reservedParams)) {
            trigger_error(
                sprintf('The requested parameter "%s" is a special parameter used by the WordPress administration section. Do not use it in drts context.', $name),
                E_USER_WARNING
            );
        }
    }
}