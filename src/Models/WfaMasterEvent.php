<?php

namespace Jgu\Wfa\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfaMasterEvent extends WfaBaseModel
{
    use HasFactory;

    public function master(){
        return $this->belongsTo(WfaMaster::class,'wfa_master_id');
    }

    public function sequenceGroup(){
        return $this->belongsTo(WfaSequenceGroup::class);
    }

    public function approvers(){
        return $this->hasMany(WfaApprover::class);
    }
    
    public function actions(){
        return $this->hasMany(WfaAction::class);
    }
}
