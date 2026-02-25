<?php

namespace TheWebsiteGuy\AvalancheCRM\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use TheWebsiteGuy\AvalancheCRM\Models\Client;
use TheWebsiteGuy\AvalancheCRM\Models\Project;
use TheWebsiteGuy\AvalancheCRM\Models\Ticket;
use TheWebsiteGuy\AvalancheCRM\Models\Invoice;
use TheWebsiteGuy\AvalancheCRM\Models\Subscription;
use TheWebsiteGuy\AvalancheCRM\Models\Staff;
use TheWebsiteGuy\AvalancheCRM\Models\Task;
use TheWebsiteGuy\AvalancheCRM\Models\TicketStatus;

/**
 * Dashboard Backend Controller
 */
class Dashboard extends Controller
{
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('TheWebsiteGuy.AvalancheCRM', 'avalanchecrm', 'dashboard');
    }

    public function index()
    {
        $this->pageTitle = 'Dashboard';

        $this->vars['totalClients'] = Client::count();
        $this->vars['activeProjects'] = Project::where('status', 'active')->count();

        $closedStatuses = TicketStatus::whereIn('name', ['Closed', 'Resolved'])->pluck('id')->toArray();
        $this->vars['openTickets'] = Ticket::whereNotIn('status_id', $closedStatuses)->count();

        $this->vars['activeSubscriptions'] = Subscription::where('status', 'active')->count();
        $this->vars['unpaidInvoices'] = Invoice::whereIn('status', ['outstanding', 'due', 'overdue'])->count();
        $this->vars['totalStaff'] = Staff::count();
        $this->vars['pendingTasks'] = Task::whereIn('status', ['todo', 'in_progress'])->count();
    }
}
