<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppLicensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_licenses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('imei',20)->unique();
            $table->string('key',20)->unique();
            $table->string('name',50);
            $table->string('email',30);
            $table->date('registration_date');
            $table->date('activation_date')->nullable();
            $table->date('expiration_date');
            $table->integer('validity');
            $table->integer('remaining_days')->nullable();
            $table->date('last_sync')->nullable();
            $table->string('last_sync_time',20)->nullable();
            $table->integer('van_sales');
            $table->integer('sync');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_licenses');
    }
}
