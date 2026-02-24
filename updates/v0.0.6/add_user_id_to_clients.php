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
        Schema::table('thewebsiteguy_nexuscrm_clients', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('thewebsiteguy_nexuscrm_clients', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
