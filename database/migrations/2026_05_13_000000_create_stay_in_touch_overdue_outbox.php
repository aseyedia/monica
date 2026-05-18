<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStayInTouchOverdueOutbox extends Migration
{
    public function up()
    {
        Schema::create('stay_in_touch_overdue_outbox', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('user_id');
            $table->date('planned_date');
            $table->tinyInteger('days_overdue'); // 3, 7, 14, 30
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stay_in_touch_overdue_outbox');
    }
}
