<?php
namespace SabaiApps\Directories\Component\Payment\Model;

class Feature extends Base\Feature
{
    public function addLog($message = '', $isError = false)
    {
        $logs = (array)$this->logs;
        $logs[] = array(
            'message' => $message,
            'status' => $this->status,
            'is_error' => $isError,
            'time' => time(),
        );
        $this->logs = $logs;
    }
    
    public function addMeta($key, $value)
    {
        $metas = $this->getMetas();
        $metas[$key] = $value;
        $this->metas = $metas;
    }
    
    public function addMetas(array $metas)
    {
        foreach ($metas as $key => $value) {
            $this->addMeta($key, $value);
        }
    }
    
    public function getMeta($key)
    {
        $metas = $this->getMetas();
        return isset($metas[$key]) ? $metas[$key] : null;
    }
    
    public function getMetas()
    {
        return (array)$this->metas;
    }
}

class FeatureRepository extends Base\FeatureRepository
{
}