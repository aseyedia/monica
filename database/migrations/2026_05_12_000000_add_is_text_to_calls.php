<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsTextToCalls extends Migration
{
    public function up()
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->boolean('is_text')->default(false)->after('contact_called');
        });
    }

    public function down()
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('is_text');
        });
    }
}
