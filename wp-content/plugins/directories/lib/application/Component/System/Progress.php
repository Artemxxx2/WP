<?php
namespace SabaiApps\Directories\Component\System;

use SabaiApps\Directories\Application;

class Progress
{
    protected $_application, $_name, $_running = false;
    
    const DEFAULT_SLEEP = 1.2;
    
    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }
    
    public function start($total = null, $format = null, array $options = [])
    {
        if ($this->_running) return $this;
        
        // Suppress PHP errors while process is running to prevent json parse error 
        $level = error_reporting(0);
        
        $options += array(
            'sleep' => self::DEFAULT_SLEEP,
            'log' => true,
        );
        $this->_running = true;
        $this->_application->System_Progress_set($this->_name, array(
            'success' => 0,
            'fail' => 0,
            'total' => isset($total) ? intval($total) : null,
            'percent' => 0,
            'message' => null,
            'format' => isset($format) ? $format : '%s',
            'done' => false,
            'sleep' => $options['sleep'],
            'error_level' => $level,
        ));
        
        // Log
        if ($options['log']) {
            $this->_application->logDebug(sprintf('Progress (%s) started.', $this->_name), array('progress' => $this->_name));
        }
        
        // Sleep
        usleep($options['sleep'] * 1000000);
        
        return $this;
    }
    
    public function set($target, $success = true, array $options = [])
    {
        if (!$this->_running) return $this;
        
        $options += array(
            'total' => null,
            'log' => true,
        );
        $data = $this->_application->System_Progress_get($this->_name);
        if ($success) {
            if (is_int($success)) {
                $data['success'] = $success;
            } else {
                ++$data['success'];
            }
        } else {
            ++$data['fail'];
        }
        if (isset($options['total'])
            && is_numeric($options['total'])
        ) {
            $data['total'] = $options['total'];
        }
        if (isset($data['total'])) {
            if ($data['success'] > $data['total']) {
                $data['success'] = $data['total'];
            }
            $data['percent'] = $data['success'] && $data['total'] ? intval($data['success'] / $data['total'] * 100) : 0;
        } else {
            $data['percent'] = -1;
        }
        $data['message'] = sprintf(
            $this->_application->H($data['format']),
            $data['success'],
            $data['total'],
            '<span style="font-weight:600;font-style:italic;">' . $this->_application->H($target) . '</span>',
            $data['fail']
        );
        $this->_application->System_Progress_set($this->_name, $data);
        
        // Log
        if ($options['log']) {
            $this->_application->logDebug(strip_tags($data['message']), array('progress' => $this->_name));
        }
        
        // Sleep
        usleep((isset($options['sleep']) ? $options['sleep'] : $data['sleep']) * 1000000);
        
        return $this;
    }
    
    public function done($message = null, $more = false, array $options = [])
    {
        if (!$this->_running) return $this;
        
        $this->_running = false;
        $data = $this->_application->System_Progress_get($this->_name);
        if (!empty($data['done'])) return $this;
        
        $options += array(
            'log' => true,
        );
        $data['done'] = true;
        $data['message'] = sprintf(
            $this->_application->H(_x('Complete! (%1$s success, %2$s failed)', 'progress complete', 'directories')),
            '<span class="' . DRTS_BS_PREFIX . 'text-success">' . $data['success'] . '</span>',
            '<span class="' . DRTS_BS_PREFIX . 'text-danger">' . $data['fail'] . '</span>',
            $data['total']
        );
        if (isset($message)) {
            $data['message'] = sprintf($this->_application->H($message), $data['message']);
        }
        $data['more'] = $more;
        $this->_application->System_Progress_set($this->_name, $data);
        
        // Log
        if ($options['log']) {
            $this->_application->logDebug(strip_tags($data['message']), array('progress' => $this->_name));
        }
        
        // Sleep
        $sleep = isset($options['sleep']) ? $options['sleep'] : $data['sleep'];
        if ($more) $sleep *= 2;
        usleep($sleep * 1000000);
        
        error_reporting($data['error_level']);
        
        return $this;
    }
    
    public function isRunning()
    {
        return $this->_running;
    }
}