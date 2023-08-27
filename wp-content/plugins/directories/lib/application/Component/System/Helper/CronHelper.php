<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;

class CronHelper
{
    public function help(Application $application, array &$logs = null, $force = false)
    {
        // Init progress
        if (!isset($logs)) {
            $logs = ['notice' => [], 'info' => [], 'success' => [], 'error' => [], 'warning' => []];
            $log = true;
        }
        $logs['info'][] = __('Running cron...', 'directories');
        // Get timestamp of last cron
        $last_run = $application->getPlatform()->getOption('system_cron_last');
        if (!is_array($last_run)) {
            $last_run = ['' => time()];
        } else {
            $logs['info'][] = sprintf(
                __('Cron was last run at %s', 'directories'),
                $application->System_Date_datetime($last_run[''])
            );
            $last_run[''] = time();
        }
        // Invoke cron
        $application->Action('system_cron', [&$logs, &$last_run, $force]);
        // Save timestamp
        $application->getPlatform()->setOption('system_cron_last', $last_run);
        // Log
        if (!empty($log)) {
            foreach (array_keys($logs) as $level) {
                foreach ((array)$logs[$level] as $log) {
                    switch ($level) {
                        case 'notice':
                            $application->logDebug(strip_tags($log));
                            break;
                        case 'success':
                        case 'info':
                            $application->logNotice(strip_tags($log));
                            break;
                        case 'warning':
                            $application->logWarning(strip_tags($log));
                            break;
                        case 'error':
                            $application->logError(strip_tags($log));
                            break;
                        default:
                    }
                }
            }
        }
    }

    public function canRunTask(Application $application, $name, array &$logs, array &$lastRun, $interval = 86400, $updateLastRun = true, $addLog = true)
    {
        $ret = true;
        if (!empty($lastRun[$name])) {
            $ret = time() > $lastRun[$name] + $interval;
            if (!$ret) {
                // Add log?
                if ($addLog) {
                    $logs['info'][] = sprintf(
                        __('%s - cron task was last run at %s, skipping until %s.', 'directories'),
                        $name,
                        $application->System_Date_datetime($lastRun[$name]),
                        $application->System_Date_datetime($lastRun[$name] + $interval)
                    );
                }
            }
        }
        // Update last run timestamp?
        if ($ret && $updateLastRun) $lastRun[$name] = time();

        return $ret;
    }
}
