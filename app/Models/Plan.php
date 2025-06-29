<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends Model
{

    use HasFactory;

    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class)->withPivot('limits');
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getLowerPlans()
    {
        return Plan::where('level', '<', $this->level)->get();
    }


public function getAllFeaturesAttribute()
{
    // Get features from all lower plans plus current plan
    $getLowerPlans = $this->getLowerPlans()->pluck('id');
    $allPlanIds = $getLowerPlans->push($this->id);
    
    return Feature::whereHas('plans', function($q) use ($allPlanIds) {
        $q->whereIn('plans.id', $allPlanIds);
    })->get();
}
}
