<?php

namespace TheWebsiteGuy\NexusCRM\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;

/**
 * Dashboard Backend Controller
 */
class Dashboard extends Controller
{
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('TheWebsiteGuy.NexusCRM', 'nexuscrm', 'dashboard');
    }

    public function index()
    {
        $this->pageTitle = 'Dashboard';
    }
}
