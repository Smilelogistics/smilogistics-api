<?php

namespace App\Providers;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
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
        if (Auth::check()) {
        $user = auth()->user();
        $mail = Branch::where('user_id', $user->id)->first();

        if ($mail) {
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
