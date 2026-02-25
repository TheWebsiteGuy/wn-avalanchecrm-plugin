<?php

namespace TheWebsiteGuy\AvalancheCRM\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use TheWebsiteGuy\AvalancheCRM\Models\Invoice;
use TheWebsiteGuy\AvalancheCRM\Classes\InvoicePdf;

/**
 * Invoices Backend Controller
 */
class Invoices extends Controller
{
    /**
     * @var array Behaviors that are implemented by this controller.
     */
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\RelationController::class,
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';

    /**
     * @var array Permissions required to view this page.
     */
    protected $requiredPermissions = [
        'thewebsiteguy.avalanchecrm.invoices.manage_all',
    ];

    public function listExtendQuery($query)
    {
        if ($status = request()->get('status')) {
            $query->where('status', $status);
        }
    }

    /**
     * Download invoice as PDF (backend).
     * URL: backend/thewebsiteguy/avalanchecrm/invoices/pdf/{id}
     */
    public function pdf($invoiceId = null)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $pdf = InvoicePdf::generate($invoice);

        return $pdf->download(InvoicePdf::filename($invoice));
    }
}
