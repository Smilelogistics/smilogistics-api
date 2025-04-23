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
            $table->string('branch_code', 20)->nullable();
            $table->string('address');
            $table->text('about_us')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('parcel_tracking_prefix', 10)->nullable();
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
            $table->integer('tax')->nullable();
            $table->integer('custom_duties_charge')->nullable();
            $table->integer('shipment_insurance')->nullable();
            $table->integer('discount')->nullable();
            $table->string('db_backup')->nullable();
            $table->string('app_theme')->nullable();
            $table->string('app_secondary_color', 50)->nullable();
            $table->string('app_text_color', 50)->nullable();
            $table->string('app_alt_color', 50)->nullable();
            $table->string('logo1')->nullable();
            $table->string('logo2')->nullable();
            $table->string('logo3')->nullable();
            $table->string('business_status', 15)->default('active');

            $table->integer('isSubscribed')->nullable()->default(1);
            $table->date('subscription_date')->nullable();
            $table->date('subscription_end_date')->nullable();
            $table->integer('subscription_count')->nullable();
            $table->string('subscription_type')->nullable()->default('trial');
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
