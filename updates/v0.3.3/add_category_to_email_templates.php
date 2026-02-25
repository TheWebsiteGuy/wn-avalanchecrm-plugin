<?php

namespace TheWebsiteGuy\AvalancheCRM\Updates;

use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

class AddCategoryToEmailTemplates extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('thewebsiteguy_avalanchecrm_email_templates', 'category')) {
            return;
        }

        Schema::table('thewebsiteguy_avalanchecrm_email_templates', function ($table) {
            $table->string('category', 40)->default('marketing')->after('name');
        });

        // Update existing templates to marketing category
        \Db::table('thewebsiteguy_avalanchecrm_email_templates')
            ->whereNull('category')
            ->orWhere('category', '')
            ->update(['category' => 'marketing']);
    }

    public function down()
    {
        Schema::table('thewebsiteguy_avalanchecrm_email_templates', function ($table) {
            $table->dropColumn('category');
        });
    }
}
