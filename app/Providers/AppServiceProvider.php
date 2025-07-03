<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Driver;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //lets get our email settings dynamically from the database 🚀
        if (!App::runningInConsole() && Auth::check()) {
            $user = auth()->user();
            $branchId = $user->getBranchId();

            if ($user->hasRole('businessadministrator')) {
                $mail = Branch::where('user_id', $user->id)->first();
            } elseif ($user->hasRole('customer')) {
                $mail = Customer::with('branch')->where('user_id', $user->id)
                    ->where('branch_id', $branchId)->first();
                $mail = $mail?->branch;
            } elseif ($user->hasRole('driver')) {
                $mail = Driver::with('branch')->where('user_id', $user->id)
                    ->where('branch_id', $branchId)->first();
                $mail = $mail?->branch;
            } elseif ($user->hasRole('superadministrator')) {
                $mail = SuperAdmin::where('user_id', $user->id)->first();
            }

            if (!empty($mail)) {
                Config::set('mail.mailers.smtp.host', $mail->mail_host);
                Config::set('mail.mailers.smtp.port', $mail->mail_port);
                Config::set('mail.mailers.smtp.username', $mail->mail_username);
                Config::set('mail.mailers.smtp.password', $mail->mail_password);
                Config::set('mail.mailers.smtp.encryption', $mail->mail_encryption);
                Config::set('mail.from.address', $mail->mail_from_address);
                Config::set('mail.from.name', $mail->mail_from_name);
            }
        }
        
    }
}
