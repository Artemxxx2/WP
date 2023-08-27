<?php
namespace SabaiApps\Framework\Application;

use SabaiApps\Framework\Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractApplication implements LoggerAwareInterface
{
    private $_routeParam, $_helperDir = [],
        $_eventListeners = [], $_eventListenersSorted = [];
    protected $_helpers = [], $_logger;

    /**
     * Constructor
     */
    protected function __construct($routeParam)
    {
        $this->_routeParam = $routeParam;
    }

    public function getRouteParam()
    {
        return $this->_routeParam;
    }
    
    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }
    
    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    public function logDebug($message, array $context = [])
    {
        if ($this->_logger) $this->_logger->debug($message, $context);
    }
    
    public function logNotice($message, array $context = [])
    {
        if ($this->_logger) $this->_logger->notice($message, $context);
    }
    
    public function logWarning($message, array $context = [])
    {
        if ($this->_logger) $this->_logger->warning($message, $context);
    }
    
    public function logError($message, array $context = [])
    {
        if (!$this->_logger) return;
            
        if ($message instanceof \Exception) {
            $this->_logger->critical($message->getMessage(), $context + ['exception' => (string)$message]);
        } else {
            $this->_logger->error($message, $context);
        }
    }
    
    public static function startSession($path = null)
    {
        if (session_id()) return;
        
        if ($path && is_string($path)) {
            session_save_path($path);
        }
        @ini_set('session.use_only_cookies', 1);
        @ini_set('session.use_trans_sid', 0);
        @ini_set('session.hash_function', 1);
        @ini_set('session.cookie_httponly', 1);
        session_start();
    }

    /**
     * Call a helper method with the application object prepended to the arguments
     */
    public function __call($name, $args)
    {
        return $this->callHelper($name, $args);
    }

    public function run(IController $controller, Context $context, $route = null)
    {
        register_shutdown_function([$this, 'shutdown'], $context);

        // Fetch route from request if none specified
        if (!isset($route)) $route = $context->getRequest()->asStr($this->getRouteParam());

        $controller->setApplication($this)->setRoute($route)->execute($context);

        return $this->_createResponse()->setApplication($this);
    }

    public function shutdown(Context $context)
    {
        if (($error = error_get_last())
            && $error['type'] === E_ERROR
        ) {
            $context->setError(sprintf('Fatal error: %s in %s on line %d.', $error['message'], $error['file'], $error['line']));
            $this->_createResponse()->setApplication($this)->send($context);
        }
    }
    
    abstract protected function _createResponse();
    
    public function addEventListener($eventType, $eventListener, $priority = 10)
    {
        $this->_eventListeners[$eventType][$priority][] = $eventListener;
        return $this;
    }
    
    public function hasEventListner($eventType)
    {
        return !empty($this->_eventListeners[$eventType]);
    }
    
    public function dispatchEvent($eventType, array $eventArgs = [])
    {
        if (!isset($this->_eventListenersSorted[$eventType])) {
            ksort($this->_eventListeners[$eventType]);
            $this->_eventListenersSorted[$eventType] = true;
        }
        foreach ($this->_eventListeners[$eventType] as $listeners) {
            foreach ($listeners as $listener) {
                try {
                    $this->_getEventListener($listener)->handleEvent($eventType, $eventArgs);
                } catch (\Exception $e) {
                    $this->logError($e);
                }
            }
        }
        return $this;
    }
    
    /**
     * @param mixed $eventListener
     * @return IEventListener
     */
    protected function _getEventListener($eventListener)
    {
        return $eventListener;
    }

    public function clearEventListeners()
    {
        $this->_eventListeners = [];
        return $this;
    }
    
    public function addHelperDir($dir, $prefix)
    {
        $this->_helperDir = [$dir => $prefix] + $this->_helperDir;
        return $this;
    }
    
    public function callHelper($name, array $args = [])
    {
        array_unshift($args, $this);
        $callback = $this->_getHelper($name);
        // Append additional args if any
        if (is_array($callback) && is_array($callback[1])) {
            $args = empty($args) ? $callback[1] : array_merge($args, $callback[1]);
            $callback = $callback[0];
        }
        return call_user_func_array($callback, $args);
    }

    protected function _getHelper($name)
    {
        if (isset($this->_helpers[$name])) return $this->_helpers[$name];

        foreach ($this->_helperDir as $helper_dir => $helper_prefix) {
            $class = $helper_prefix . $name . 'Helper';
            if (!class_exists($class, false)) {
                if (!@include sprintf('%s/%sHelper.php', $helper_dir, $name)) {
                    continue;
                }
            }
            $this->setHelper($name, array(new $class($this), 'help'));
            return $this->_helpers[$name];            
        }
        throw new Exception(sprintf('Call to undefined application helper %s.', $name));
    }

    /**
     * Set an application helper
     * @param $name string
     * @param $helper Callable method or function
     */
    public function setHelper($name, $helper)
    {
        $this->_helpers[$name] = $helper;
        return $this;
    }

    public function hasHelper($name)
    {
        return isset($this->_helpers[$name]);
    }
}