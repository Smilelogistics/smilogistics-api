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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('branch_code')->nullable();
            $table->string('address');
            $table->string('about_us')->nullable();
            $table->string('phone');
            $table->string('parcel_tracking_prefix')->nullable();
            $table->string('invoice_prefix')->nullable();
            $table->string('invoice_logo')->nullable();
            $table->string('currency')->nullable();
            $table->string('copyright')->nullable();
            $table->string('paystack_publicKey')->nullable();
            $table->string('paystack_secretKey')->nullable();
            $table->string('FLW_pubKey')->nullable();
            $table->string('FLW_secKey')->nullable();
            $table->string('Razor_pubKey')->nullable();
            $table->string('Razor_secKey')->nullable();
            $table->string('stripe_pubKey')->nullable();
            $table->string('stripe_secKey')->nullable();
            $table->string('mail_driver')->nullable();
            $table->string('mail_host')->nullable();
            $table->string('mail_port')->nullable();
            $table->string('mail_username')->nullable();
            $table->string('mail_password')->nullable();
            $table->string('mail_encryption')->nullable();
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->integer('enable_2fa')->nullable();
            $table->integer('enable_email_otp')->nullable();
            $table->integer('enable_recaptcha')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('custom_duties_charge')->nullable();
            $table->integer('shipment_insurance')->nullable();
            $table->integer('discount')->nullable();
            $table->string('db_backup')->nullable();
            $table->string('app_theme')->nullable();
            $table->string('app_secondary_color')->nullable();
            $table->string('app_text_color')->nullable();
            $table->string('app_alt_color')->nullable();
            $table->string('logo1')->nullable();
            $table->string('logo2')->nullable();
            $table->string('logo3')->nullable();
            $table->string('business_status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
