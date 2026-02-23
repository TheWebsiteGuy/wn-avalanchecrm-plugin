<?php

namespace TheWebsiteGuy\NexusCRM\Components;

use Auth;
use Flash;
use Input;
use Log;
use Cms\Classes\ComponentBase;
use TheWebsiteGuy\NexusCRM\Models\Client;
use TheWebsiteGuy\NexusCRM\Models\Invoice;
use TheWebsiteGuy\NexusCRM\Models\Settings;
use Winter\Storm\Exception\ApplicationException;

/**
 * ClientInvoices Component
 *
 * Allows frontend clients to view invoices assigned to them,
 * including filtering by status and viewing invoice details.
 */
class ClientInvoices extends ComponentBase
{
    /**
     * @var Client The authenticated client.
     */
    public $client;

    /**
     * @var \Winter\Storm\Database\Collection Invoices belonging to the client.
     */
    public $invoices;

    /**
     * @var Invoice|null The currently viewed invoice (detail view).
     */
    public $activeInvoice;

    /**
     * @var Settings CRM settings instance.
     */
    public $settings;

    /**
     * Component details.
     */
    public function componentDetails(): array
    {
        return [
            'name'        => 'Client Invoices',
            'description' => 'Allows clients to view and manage their invoices.',
        ];
    }

    /**
     * Defines the properties used by this component.
     */
    public function defineProperties(): array
    {
        return [
            'invoicesPerPage' => [
                'title'       => 'Invoices Per Page',
                'description' => 'Number of invoices to display per page.',
                'type'        => 'string',
                'default'     => '10',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Please enter a number.',
            ],
            'showProject' => [
                'title'       => 'Show Project',
                'description' => 'Display the associated project for each invoice.',
                'type'        => 'checkbox',
                'default'     => true,
            ],
        ];
    }

    /**
     * Prepare variables before the page loads.
     */
    public function onRun()
    {
        $this->addCss('/plugins/thewebsiteguy/nexuscrm/assets/css/client-invoices.css');

        $this->prepareVars();
    }

    /**
     * Set up all page variables.
     */
    protected function prepareVars()
    {
        $user = Auth::getUser();

        if (!$user) {
            return;
        }

        $this->settings = $this->page['settings'] = Settings::instance();
        $this->client = $this->page['client'] = Client::where('user_id', $user->id)->first();

        if (!$this->client) {
            return;
        }

        // Check if a specific invoice is requested
        $invoiceId = Input::get('invoice');

        if ($invoiceId) {
            $this->activeInvoice = $this->page['activeInvoice'] = Invoice::where('id', $invoiceId)
                ->where('client_id', $this->client->id)
                ->where('status', '!=', 'draft')
                ->with(['project', 'items'])
                ->first();
        }

        // Load all client invoices (exclude drafts)
        $this->invoices = $this->page['invoices'] = Invoice::where('client_id', $this->client->id)
            ->where('status', '!=', 'draft')
            ->with(['project'])
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->paginate($this->property('invoicesPerPage', 10));

        $this->page['showProject'] = $this->property('showProject');
        $this->page['currencySymbol'] = $this->settings->currency_symbol ?? '$';
        $this->page['currencyCode'] = $this->settings->currency_code ?? 'USD';

        $this->setPaymentGatewayFlags($this->settings);
    }

    /**
     * AJAX: Load invoice detail view.
     */
    public function onViewInvoice()
    {
        $client = $this->getAuthenticatedClient();

        $invoiceId = Input::get('invoice_id');
        if (!$invoiceId) {
            throw new ApplicationException('No invoice specified.');
        }

        $invoice = Invoice::where('id', $invoiceId)
            ->where('client_id', $client->id)
            ->where('status', '!=', 'draft')
            ->with(['project', 'items'])
            ->first();

        if (!$invoice) {
            throw new ApplicationException('Invoice not found or access denied.');
        }

        $this->page['activeInvoice'] = $invoice;
        $this->page['showProject'] = $this->property('showProject');

        $settings = Settings::instance();
        $this->page['currencySymbol'] = $settings->currency_symbol ?? '$';
        $this->page['currencyCode'] = $settings->currency_code ?? 'USD';

        $this->setPaymentGatewayFlags($settings);

        return [
            '#client-invoices-detail' => $this->renderPartial('@detail'),
        ];
    }

    /**
     * AJAX: Return to the invoice list view.
     */
    public function onBackToList()
    {
        $this->prepareVars();

        return [
            '#client-invoices-list' => $this->renderPartial('@list'),
            '#client-invoices-detail' => '',
        ];
    }

    /**
     * AJAX: Filter invoices by status.
     */
    public function onFilterInvoices()
    {
        $client = $this->getAuthenticatedClient();

        $status = Input::get('status');

        $query = Invoice::where('client_id', $client->id)
            ->where('status', '!=', 'draft')
            ->with(['project']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $this->page['invoices'] = $query->orderBy('created_at', 'desc')
            ->paginate($this->property('invoicesPerPage', 10));

        $this->page['showProject'] = $this->property('showProject');
        $this->page['currencySymbol'] = (Settings::instance())->currency_symbol ?? '$';
        $this->page['currencyCode'] = (Settings::instance())->currency_code ?? 'USD';

        return [
            '#client-invoices-list' => $this->renderPartial('@list'),
        ];
    }

    /**
     * Set payment gateway enabled flags for the template.
     */
    protected function setPaymentGatewayFlags(Settings $settings): void
    {
        $this->page['stripeEnabled'] = (bool) $settings->stripe_enabled;
        $this->page['paypalEnabled'] = (bool) $settings->paypal_enabled;
        $this->page['gocardlessEnabled'] = (bool) $settings->gocardless_enabled;
    }

    /**
     * Helper: Retrieve the authenticated client or throw.
     */
    protected function getAuthenticatedClient(): Client
    {
        $user = Auth::getUser();
        if (!$user) {
            throw new ApplicationException('You must be logged in.');
        }

        $client = Client::where('user_id', $user->id)->first();
        if (!$client) {
            throw new ApplicationException('No client profile found.');
        }

        return $client;
    }
}
