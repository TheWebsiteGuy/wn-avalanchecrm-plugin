<?php

namespace TheWebsiteGuy\AvalancheCRM\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;

/**
 * Subscription Plans Backend Controller
 */
class SubscriptionPlans extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    protected $requiredPermissions = [
        'thewebsiteguy.avalanchecrm.subscriptions.manage_all',
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('TheWebsiteGuy.AvalancheCRM', 'avalanchecrm', 'subscriptionplans');
    }
}
