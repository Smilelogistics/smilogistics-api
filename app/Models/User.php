<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Jetstream\HasProfilePhoto;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Events\PasswordReset;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\PasswordResetNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRolesAndPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fname',
        'mname',
        'lname',
        'email',
        'user_type',
        'otp',
        'otp_expires_at',
        'otp_last_sent_at',
        'password',
    ];
 
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            //'abilities' => 'string',
    
        ];
    }



// Add this inside the User class
public function createToken(string $name, array $abilities = null)
{
    $plainTextToken = Str::random(40);

    $token = $this->tokens()->create([
        'name' => $name,
        'token' => hash('sha256', $plainTextToken),
        'abilities' => null, 
    ]);

    return new NewAccessToken($token, $plainTextToken);
}


    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetNotification($token));
    }

    public function superadmin() {
        return $this->hasOne(SuperAdmin::class);
    }

    public function branch() {
        return $this->hasOne(Branch::class);
    }

    public function customer() {
        return $this->hasOne(Customer::class);
    }

    public function driver() {
        return $this->hasOne(Driver::class);
    }

    public function creatorDriver() {
        return $this->hasOne(Driver::class);
    }

    public function agency() {
        return $this->hasOne(Agency::class);
    }
    public function consolidateShipment() {
        return $this->hasOne(ConsolidateShipment::class);
    }
    public function delivery() {
        return $this->hasOne(Delivery::class);
    }
    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }

    public function settlement()
    {
        return $this->hasMany(Settlement::class);
    }
    public function getBranchId()
    {
        if ($this->branch) {
            return $this->branch->id;
        } elseif ($this->customer) {
            return $this->customer->branch_id;
        } elseif ($this->driver) {
            return $this->driver->branch_id;
        }
        
        return null;
    }

    public function getBranchHandlingFee()
    {
        if ($this->branch) {
            return $this->branch->handling_fee;
        } elseif ($this->customer) {
            return optional($this->customer->branch)->handling_fee;
        } elseif ($this->driver) {
            return optional($this->driver->branch)->handling_fee;
        }

        return null;
    }

    public function getBranchTotalHeight()
    {
        if ($this->branch) {
            return $this->branch->max_height;
        } elseif ($this->customer) {
            return optional($this->customer->branch)->max_height;
        } elseif ($this->driver) {
            return optional($this->driver->branch)->max_height;
        }

        return null;
    }

    public function getBranchTotalLength()
    {
        if ($this->branch) {
            return $this->branch->max_length;
        } elseif ($this->customer) {
            return optional($this->customer->branch)->max_length;
        } elseif ($this->driver) {
            return optional($this->driver->branch)->max_length;
        }

        return null;
    }


    public function getMPG()
    {
        if ($this->branch) {
            return $this->branch->mpg;
        } elseif ($this->customer) {
            return optional($this->customer->branch)->mpg;
        } elseif ($this->driver) {
            return optional($this->driver->branch)->mpg;
        }

        return null;
    }

    public function isSubscribed(): bool
    {
        return $this->branch && $this->branch->isSubscribed == 1;
    }

    public static function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));

        [$local, $domain] = explode('@', $email, 2);

        switch ($domain) {
            case 'gmail.com':
            case 'googlemail.com':
                // remove dots + strip everything after +
                $local = preg_replace('/\./', '', $local);
                $local = preg_replace('/\+.*/', '', $local);
                break;

            case 'outlook.com':
            case 'hotmail.com':
            case 'live.com':
            case 'office365.com':
            case 'protonmail.com':
            case 'icloud.com':
                // strip everything after +
                $local = preg_replace('/\+.*/', '', $local);
                break;

            case 'yahoo.com':
            case 'ymail.com':
            case 'rocketmail.com':
                // strip everything after -
                $local = preg_replace('/\-.*$/', '', $local);
                break;
        }

        return $local . '@' . $domain;
    }

}
