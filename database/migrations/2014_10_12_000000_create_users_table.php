<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();;
            $table->enum('userType', ['admin', 'accountant', 'consumer'])->default('consumer');
            $table->string('image')->nullable();
            $table->string('mobileNumber')->unique()->nullable();
            $table->string('userDeviceId')->unique()->nullable();
            $table->string('providerName')->nullable();
            $table->string('providerId')->nullable();
            $table->string('socialLoginResponse')->nullable();
            $table->string('defaultCurrency')->nullable();
            
            $table->timestamp('user_verified_at')->nullable();
            $table->enum('userVerificationStatus', ['1', '0'])->default('0');
            $table->string('password')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
