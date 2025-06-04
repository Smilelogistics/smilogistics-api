<?php
//namespace App\Helpers;

use Illuminate\Support\Facades\Config;

function setDynamicMailConfig($user = null)
{
    $mail = null;

    // Handle logged-in user
    if ($user) {
        if ($user->hasRole('businessadministrator')) {
            $mail = \App\Models\Branch::where('user_id', $user->id)->first();

        } elseif ($user->hasRole('superadministrator')) {
            $mail = \App\Models\SuperAdmin::where('user_id', $user->id)->first();

        } elseif ($user->hasRole('customer') || $user->hasRole('driver')) {
            $branchId = $user->getBranchId();

            if ($branchId) {
                $mail = \App\Models\Branch::find($branchId);
            }
        }
    }

    // Fallback to first superadmin if none found or user is null (guest)
    if (!$mail) {
        $mail = \App\Models\SuperAdmin::first();
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


