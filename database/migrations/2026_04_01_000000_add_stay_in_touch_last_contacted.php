<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStayInTouchLastContacted extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->datetime('stay_in_touch_last_contacted')->nullable()->after('stay_in_touch_trigger_date');
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('stay_in_touch_last_contacted');
        });
    }
}
