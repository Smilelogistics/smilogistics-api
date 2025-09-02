<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Branch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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
        $today = Carbon::today();
        $tenDaysFromNow = $today->copy()->addDays(10);

        // Get branches that are subscribed and have subscription end dates
        // within the next 10 days OR have already expired
        $branches = Branch::where('isSubscribed', 1)
            ->whereNotNull('subscription_end_date')
            ->where(function($query) use ($today, $tenDaysFromNow) {
                $query->whereDate('subscription_end_date', '<=', $tenDaysFromNow)
                      ->orWhereDate('subscription_end_date', '<=', $today);
            })
            ->with('user') // Eager load user relationship
            ->get();

        $this->info("Found {$branches->count()} branches to check.");

        foreach ($branches as $branch) {
            if (!$branch->user) {
                $this->warn("Branch {$branch->id} has no associated user. Skipping.");
                continue;
            }

            $subscriptionEndDate = Carbon::parse($branch->subscription_end_date);
            
            // Calculate days until expiration (negative means expired)
            $daysLeft = $today->diffInDays($subscriptionEndDate, false);
            
            $this->line("Branch: {$branch->id}, Days left: {$daysLeft}, Type: {$branch->subscription_type}");

            // Handle expired subscriptions
            if ($daysLeft < 0) {
                if ($branch->isSubscribed) {
                    $branch->update(['isSubscribed' => 0]);
                    $this->info("Branch {$branch->id} subscription expired. Updated status to inactive.");
                    
                    // Log the expiration
                    Log::info("Subscription expired for branch {$branch->id} (User: {$branch->user->email})");
                }
                continue; // Skip reminder logic for expired subscriptions
            }

            // Handle TRIAL subscriptions
            if ($branch->subscription_type === 'trial') {
                if (in_array($daysLeft, [10, 8, 5, 2, 1, 0])) {
                    try {
                        // Send notification via email route
                        Notification::route('mail', $branch->user->email)
                            ->notify(new TrialSubscriptionReminderNotification($branch, $daysLeft));

                        // Store in database notifications table
                        $branch->user->notify(new TrialSubscriptionReminderNotification($branch, $daysLeft));
                        
                        $this->info("Trial reminder sent to {$branch->user->email} for branch {$branch->id} ({$daysLeft} days left)");
                        
                        Log::info("Trial reminder sent", [
                            'branch_id' => $branch->id,
                            'user_email' => $branch->user->email,
                            'days_left' => $daysLeft
                        ]);
                    } catch (\Exception $e) {
                        $this->error("Failed to send trial reminder to {$branch->user->email}: " . $e->getMessage());
                        Log::error("Failed to send trial reminder", [
                            'branch_id' => $branch->id,
                            'user_email' => $branch->user->email,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // If trial expires today
                if ($daysLeft === 0) {
                    $branch->update(['isSubscribed' => 0]);
                    $this->info("Trial expired for branch {$branch->id}. Updated status to inactive.");
                }

                continue; // Skip the paid logic
            }

            // Handle PAID subscriptions
            if (in_array($daysLeft, [10, 8, 5, 2, 1, 0])) {
                try {
                    // Send notification via email route
                    Notification::route('mail', $branch->user->email)
                        ->notify(new SubscriptionReminderNotification($branch, $daysLeft));

                    // Store in database notifications table
                    $branch->user->notify(new SubscriptionReminderNotification($branch, $daysLeft));
                    
                    $this->info("Paid subscription reminder sent to {$branch->user->email} for branch {$branch->id} ({$daysLeft} days left)");
                    
                    Log::info("Paid subscription reminder sent", [
                        'branch_id' => $branch->id,
                        'user_email' => $branch->user->email,
                        'days_left' => $daysLeft,
                        'subscription_type' => $branch->subscription_type
                    ]);
                } catch (\Exception $e) {
                    $this->error("Failed to send paid subscription reminder to {$branch->user->email}: " . $e->getMessage());
                    Log::error("Failed to send paid subscription reminder", [
                        'branch_id' => $branch->id,
                        'user_email' => $branch->user->email,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // If paid subscription expires today
            if ($daysLeft === 0) {
                $branch->update(['isSubscribed' => 0]);
                $this->info("Paid subscription expired for branch {$branch->id}. Updated status to inactive.");
            }
        }

        $this->info("Subscription check completed successfully.");
        Log::info("Subscription check complete. Processed {$branches->count()} branches.");
    }
}