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
        // Get the last numeric part by stripping SETMNT and finding the max
        $lastRecord = self::where('settlement_no', 'LIKE', 'SETMNT%')
                          ->orderByRaw('CAST(SUBSTRING(settlement_no, 7) AS UNSIGNED) DESC')
                          ->first();

        if ($lastRecord) {
            $lastNumber = (int) str_replace('STL', '', $lastRecord->settlement_no);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'SETMNT' . $nextNumber;
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
