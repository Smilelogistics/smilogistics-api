<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
    ];

    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function settlementDocs()
    {
        return $this->hasMany(SettlementDoc::class);
    }

    public function settlementPayment()
    {
        return $this->hasMany(SettlementPayment::class);
    }
    public function settlementEscrow()
    {
        return $this->hasMany(SettlementEscrow::class);
    }

    public function settlementDeduction()
    {
        return $this->hasMany(SettlementDeduction::class);
    }

    public static function generateSettlementId()
    {
        // For PostgreSQL compatibility
        if (config('database.default') === 'pgsql') {
            $lastRecord = self::where('settlement_no', 'LIKE', 'SETMNT%')
                            ->orderByRaw("SUBSTRING(settlement_no, 7)::INTEGER DESC")
                            ->first();
        } 
        // For MySQL/MariaDB
        else {
            $lastRecord = self::where('settlement_no', 'LIKE', 'SETMNT%')
                            ->orderByRaw('CAST(SUBSTRING(settlement_no, 7) AS UNSIGNED) DESC')
                            ->first();
        }

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->settlement_no, 6); // Changed from str_replace
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'SETMNT' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT); // Added padding for consistent format
    }

    /**
     * Automatically assign settlement_no before creating the record.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->settlement_no)) {
                $model->settlement_no = self::generateSettlementId();
            }
        });
    }
}
