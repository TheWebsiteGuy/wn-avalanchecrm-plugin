<?php

namespace TheWebsiteGuy\AvalancheCRM\Components;

use Winter\User\Facades\Auth;
use Cms\Classes\ComponentBase;
use TheWebsiteGuy\AvalancheCRM\Models\Client;
use TheWebsiteGuy\AvalancheCRM\Models\Invoice;
use TheWebsiteGuy\AvalancheCRM\Models\Settings;

/**
 * Dashboard Component
 *
 * Provides an overview of tickets, invoices, projects, and subscriptions for the logged-in client.
 */
class Dashboard extends ComponentBase
{
    public $user;
    public $client;
    public $stats = [];

    public function componentDetails(): array
    {
        return [
            'name' => 'Client Dashboard',
            'description' => 'Overview of tickets, invoices, projects, and subscriptions.',
        ];
    }

    public function defineProperties(): array
    {
        return [];
    }

    public function onRun()
    {
        $this->addCss('/plugins/thewebsiteguy/avalanchecrm/assets/css/dashboard.css');
        $this->addCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
        $this->page['themeStyles'] = \TheWebsiteGuy\AvalancheCRM\Classes\ThemeStyles::render();

        $this->user = Auth::getUser();
        if ($this->user) {
            $this->client = Client::where('user_id', $this->user->id)->first();
            if ($this->client) {
                $this->prepareStats();
            }
        }

        $this->page['crmSettings'] = Settings::instance();
    }

    protected function prepareStats()
    {
        // Tickets: status name not 'Closed'
        $this->stats['tickets'] = [
            'total' => $this->client->tickets()->count(),
            'open' => $this->client->tickets()->whereHas('status_relation', function ($query) {
                $query->where('name', '!=', 'Closed');
            })->count(),
        ];

        // Invoices: visible to client and not 'Paid' or 'Cancelled'
        $this->stats['invoices'] = [
            'total' => $this->client->invoices()->clientVisible()->count(),
            'unpaid' => $this->client->invoices()->clientVisible()
                ->whereNotIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED])
                ->count(),
        ];

        // Projects: status 'active' or 'pending'
        $this->stats['projects'] = [
            'total' => $this->client->projects()->count(),
            'active' => $this->client->projects()->whereIn('status', ['active', 'pending', 'in_progress'])->count(),
        ];

        // Subscriptions: status 'active'
        $this->stats['subscriptions'] = [
            'total' => $this->client->subscriptions()->count(),
            'active' => $this->client->subscriptions()->where('status', 'active')->count(),
        ];
    }
}
