<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| NexusCRM Plugin Routes
|--------------------------------------------------------------------------
|
| Webhook and API routes for the NexusCRM plugin.
|
*/

Route::post('nexuscrm/webhook/stripe', [\TheWebsiteGuy\NexusCRM\Http\StripeWebhookController::class, 'handle'])
    ->middleware('web')
    ->name('nexuscrm.webhook.stripe');

Route::post('nexuscrm/webhook/gocardless', [\TheWebsiteGuy\NexusCRM\Http\GoCardlessWebhookController::class, 'handle'])
    ->middleware('web')
    ->name('nexuscrm.webhook.gocardless');

Route::post('nexuscrm/webhook/paypal', [\TheWebsiteGuy\NexusCRM\Http\PayPalWebhookController::class, 'handle'])
    ->middleware('web')
    ->name('nexuscrm.webhook.paypal');

/*
|--------------------------------------------------------------------------
| Client Invoice PDF Download
|--------------------------------------------------------------------------
*/
Route::get('nexuscrm/invoice/{id}/pdf', function ($id) {
    $user = \Auth::getUser();
    if (!$user) {
        abort(403, 'Unauthorized');
    }

    $client = \TheWebsiteGuy\NexusCRM\Models\Client::where('user_id', $user->id)->first();
    if (!$client) {
        abort(403, 'No client profile found.');
    }

    $invoice = \TheWebsiteGuy\NexusCRM\Models\Invoice::where('id', $id)
        ->where('client_id', $client->id)
        ->firstOrFail();

    $pdf = \TheWebsiteGuy\NexusCRM\Classes\InvoicePdf::generate($invoice);

    return $pdf->download(\TheWebsiteGuy\NexusCRM\Classes\InvoicePdf::filename($invoice));
})->middleware('web')->name('nexuscrm.invoice.pdf');

/*
|--------------------------------------------------------------------------
| Invoice Payments (Stripe, PayPal, GoCardless)
|--------------------------------------------------------------------------
*/
Route::prefix('nexuscrm/invoice/{id}/payment')->middleware('web')->group(function () {
    // Initiate payment
    Route::get('stripe', [\TheWebsiteGuy\NexusCRM\Http\InvoicePaymentController::class, 'stripeCheckout'])
        ->name('nexuscrm.invoice.pay.stripe');

    Route::get('paypal', [\TheWebsiteGuy\NexusCRM\Http\InvoicePaymentController::class, 'paypalCheckout'])
        ->name('nexuscrm.invoice.pay.paypal');

    Route::get('gocardless', [\TheWebsiteGuy\NexusCRM\Http\InvoicePaymentController::class, 'gocardlessCheckout'])
        ->name('nexuscrm.invoice.pay.gocardless');

    // PayPal capture callback (after approval)
    Route::get('paypal/capture', [\TheWebsiteGuy\NexusCRM\Http\InvoicePaymentController::class, 'paypalCapture'])
        ->name('nexuscrm.invoice.pay.paypal.capture');

    // Success & cancel landing pages
    Route::get('success', [\TheWebsiteGuy\NexusCRM\Http\InvoicePaymentController::class, 'success'])
        ->name('nexuscrm.invoice.pay.success');

    Route::get('cancel', [\TheWebsiteGuy\NexusCRM\Http\InvoicePaymentController::class, 'cancel'])
        ->name('nexuscrm.invoice.pay.cancel');
});
