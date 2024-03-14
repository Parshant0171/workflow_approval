<?php

namespace Jgu\Wfa\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfaMaster extends WfaBaseModel
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('is_active', 1);
        });
    }
    
    public function events(){
        return $this->hasMany(WfaMasterEvent::class)->orderBy('sequence', 'asc')->orderBy('id');
    }
}
