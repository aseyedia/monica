<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactIntakeSubmissionsTable extends Migration
{
    public function up()
    {
        Schema::create('contact_intake_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_raw', 50);
            $table->string('phone_normalized', 20)->index();
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->text('note')->nullable();
            $table->text('raw_vcf')->nullable();
            $table->string('ip_address', 45);
            $table->enum('status', ['pending', 'approved', 'discarded'])->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contact_intake_submissions');
    }
}
