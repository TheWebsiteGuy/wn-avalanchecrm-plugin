<?php

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('thewebsiteguy_nexuscrm_subscription_plans')) {
            Schema::create('thewebsiteguy_nexuscrm_subscription_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('slug')->nullable()->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->string('billing_cycle')->default('monthly');
                $table->text('features')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('thewebsiteguy_nexuscrm_subscriptions', 'plan_id')) {
            Schema::table('thewebsiteguy_nexuscrm_subscriptions', function (Blueprint $table) {
                $table->integer('plan_id')->unsigned()->nullable()->after('client_id');
            });
        }
    }

    public function down()
    {
        Schema::table('thewebsiteguy_nexuscrm_subscriptions', function (Blueprint $table) {
            $table->dropColumn('plan_id');
        });

        Schema::dropIfExists('thewebsiteguy_nexuscrm_subscription_plans');
    }
};
