<?php

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('thewebsiteguy_nexuscrm_ticket_categories')) {
            Schema::create('thewebsiteguy_nexuscrm_ticket_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('color')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('thewebsiteguy_nexuscrm_tickets', 'category_id')) {
            Schema::table('thewebsiteguy_nexuscrm_tickets', function (Blueprint $table) {
                $table->integer('category_id')->unsigned()->nullable()->after('project_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('thewebsiteguy_nexuscrm_tickets', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('thewebsiteguy_nexuscrm_ticket_categories');
    }
};
