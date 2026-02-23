<?php

namespace TheWebsiteGuy\NexusCRM\Classes;

use Barryvdh\DomPDF\Facade\Pdf;
use TheWebsiteGuy\NexusCRM\Models\Invoice;
use TheWebsiteGuy\NexusCRM\Models\Settings;
use Winter\Storm\Support\Facades\Twig;

/**
 * Generates PDF documents for invoices.
 */
class InvoicePdf
{
    /**
     * Generate a PDF for the given invoice.
     *
     * @param  Invoice  $invoice
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generate(Invoice $invoice)
    {
        $invoice->load(['client', 'project', 'items']);

        $settings = Settings::instance();

        $data = [
            'invoice'        => $invoice,
            'currencySymbol' => $settings->currency_symbol ?: '$',
            'currencyCode'   => $settings->currency_code ?: 'USD',
            'companyName'    => $settings->company_name ?? '',
            'companyDetails' => $settings->company_details ?? '',
        ];

        // Render the Twig template to HTML
        $templatePath = plugins_path('thewebsiteguy/nexuscrm/views/pdf/invoice.htm');
        $templateContent = file_get_contents($templatePath);
        $html = Twig::parse($templateContent, $data);

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Get a download-friendly filename for the invoice.
     *
     * @param  Invoice  $invoice
     * @return string
     */
    public static function filename(Invoice $invoice): string
    {
        $number = $invoice->invoice_number ?: ('INV-' . $invoice->id);
        return preg_replace('/[^A-Za-z0-9\-_]/', '_', $number) . '.pdf';
    }
}
