<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOverdueToReminderOutbox extends Migration
{
    public function up()
    {
        Schema::table('reminder_outbox', function (Blueprint $table) {
            // null for non-overdue natures; 3, 7, 14, or 30 for overdue entries
            $table->tinyInteger('overdue_days_past')->nullable()->after('notification_number_days_before');
        });
    }

    public function down()
    {
        Schema::table('reminder_outbox', function (Blueprint $table) {
            $table->dropColumn('overdue_days_past');
        });
    }
}
