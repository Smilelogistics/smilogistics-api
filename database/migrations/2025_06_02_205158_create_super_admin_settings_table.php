<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('super_admin_settings', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_prefix', 10)->nullable();
            $table->string('invoice_logo')->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('copyright', 100)->nullable();
            $table->string('paystack_publicKey', 150)->nullable();
            $table->string('paystack_secretKey', 150)->nullable();
            $table->string('FLW_pubKey', 150)->nullable();
            $table->string('FLW_secKey', 150)->nullable();
            $table->string('Razor_pubKey', 150)->nullable();
            $table->string('Razor_secKey', 150)->nullable();
            $table->string('stripe_pubKey', 150)->nullable();
            $table->string('stripe_secKey', 150)->nullable();
            $table->string('mail_driver', 150)->nullable();
            $table->string('mail_host', 50)->nullable();
            $table->string('mail_port', 10)->nullable();
            $table->string('mail_username', 80)->nullable();
            $table->string('mail_password', 150)->nullable();
            $table->string('mail_encryption', 100)->nullable();
            $table->string('mail_from_address', 80)->nullable();
            $table->string('mail_from_name', 80)->nullable();
            $table->integer('enable_2fa')->nullable();
            $table->integer('enable_email_otp')->nullable();
            $table->integer('enable_recaptcha')->nullable();
            $table->string('logo1')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_settings');
    }
};
