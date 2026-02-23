<?php

namespace TheWebsiteGuy\NexusCRM\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;

/**
 * Subscriptions Backend Controller
 */
class Subscriptions extends Controller
{
    /**
     * @var array Behaviors that are implemented by this controller.
     */
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    /**
     * @var array Permissions required to view this page.
     */
    protected $requiredPermissions = [
        'thewebsiteguy.nexuscrm.subscriptions.manage_all',
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('TheWebsiteGuy.NexusCRM', 'nexuscrm', 'subscriptions');
    }
}
