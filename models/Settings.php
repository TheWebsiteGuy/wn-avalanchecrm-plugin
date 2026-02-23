<?php

namespace TheWebsiteGuy\NexusCRM\Models;

use Winter\Storm\Database\Model;

/**
 * Settings Model
 */
class Settings extends Model
{
    public $implement = [\System\Behaviors\SettingsModel::class];

    // A unique code
    public $settingsCode = 'thewebsiteguy_nexuscrm_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';
}
