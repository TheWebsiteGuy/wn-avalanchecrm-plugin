<?php

namespace TheWebsiteGuy\AvalancheCRM\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;

/**
 * Activity Logs Backend Controller
 */
class ActivityLogs extends Controller
{
    public $implement = [
        \Backend\Behaviors\ListController::class,
    ];

    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['thewebsiteguy.avalanchecrm.*'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('TheWebsiteGuy.AvalancheCRM', 'avalanchecrm', 'logs');
    }
}
