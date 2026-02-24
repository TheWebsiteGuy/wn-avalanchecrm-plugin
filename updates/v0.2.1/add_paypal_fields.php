<?php

namespace TheWebsiteGuy\NexusCRM\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

/**
 * Add PayPal fields to subscriptions table.
 */
class AddPaypalFields extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('thewebsiteguy_nexuscrm_subscriptions', 'paypal_subscription_id')) {
            return;
        }

        Schema::table('thewebsiteguy_nexuscrm_subscriptions', function (Blueprint $table) {
            $table->string('paypal_subscription_id')->nullable()->after('gocardless_redirect_flow_id');
        });
    }

    public function down()
    {
        Schema::table('thewebsiteguy_nexuscrm_subscriptions', function (Blueprint $table) {
            $table->dropColumn('paypal_subscription_id');
        });
    }
}
