<?php

namespace TheWebsiteGuy\AvalancheCRM\Traits;

use TheWebsiteGuy\AvalancheCRM\Models\ActivityLog;

trait LogsActivity
{
    /**
     * Boot the trait
     */
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('Created');
        });

        static::updated(function ($model) {
            $model->logActivity('Updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('Deleted');
        });
    }

    /**
     * Log a custom activity
     */
    public function logActivity($action, $message = null, $data = null)
    {
        $module = $this->getActivityModule();

        if (!$message) {
            $name = $this->getActivityName() ?: 'item';
            $message = sprintf('%s %s #%s', $action, $name, $this->getKey());
        }

        return ActivityLog::log($message, $module, $action, $this, $data);
    }

    /**
     * Get module name for logging
     */
    protected function getActivityModule()
    {
        if (property_exists($this, 'activityModule')) {
            return $this->activityModule;
        }

        $className = class_basename($this);
        return str_plural($className);
    }

    /**
     * Get display name for logging
     */
    protected function getActivityName()
    {
        if (property_exists($this, 'activityName')) {
            return $this->activityName;
        }

        return class_basename($this);
    }
}
