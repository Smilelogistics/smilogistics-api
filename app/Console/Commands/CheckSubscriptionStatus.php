<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Branch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SubscriptionReminderNotification;
use App\Notifications\TrialSubscriptionReminderNotification;

class CheckSubscriptionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-subscription-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send subscription reminders and update status on expiration';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today  = Carbon::today();


        $branchs = Branch::where('isSubscribed', 1)
        ->whereDate('subscription_end_date', '<', $today)->get();

        foreach ($branches as $branch) {
            $daysLeft = Carbon::parse($branch->subscription_end_date)->diffInDays($today, false);

            // Handle TRIAL subscriptions
            if ($branch->subscription_type === 'trial') {
                if (in_array($daysLeft, [10, 8, 5, 2, 0])) {
                    Notification::route('mail', $branch->user->email)
                        ->notify(new TrialSubscriptionReminderNotification($branch, $daysLeft));

                    // You can also store in DB
                    $branch->user->notify(new TrialSubscriptionReminderNotification($branch, $daysLeft));
                }

                // If trial ends
                if ($daysLeft === 0) {
                    $branch->update(['isSubscribed' => 0]);
                }

                continue; // Skip the paid logic
            }

            // Handle PAID subscriptions
            if (in_array($daysLeft, [10, 8, 5, 2, 0])) {
                Notification::route('mail', $branch->user->email)
                    ->notify(new SubscriptionReminderNotification($branch, $daysLeft));

                $branch->user->notify(new SubscriptionReminderNotification($branch, $daysLeft));
            }

            if ($daysLeft === 0) {
                $branch->update(['isSubscribed' => 0]);
            }
        }
        Log::info("Subscription check complete.");
    }
}
