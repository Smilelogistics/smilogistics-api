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

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class)->withPivot('limits');
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function lowerPlans()
{
    return $this->where('level', '<', $this->level)->orderBy('level');
}

public function getAllFeaturesAttribute()
{
    // Get features from all lower plans plus current plan
    $lowerPlans = $this->lowerPlans->pluck('id');
    $allPlanIds = $lowerPlans->push($this->id);
    
    return Feature::whereHas('plans', function($q) use ($allPlanIds) {
        $q->whereIn('plans.id', $allPlanIds);
    })->get();
}
}
