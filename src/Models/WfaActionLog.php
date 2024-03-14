<?php

namespace Jgu\Wfa\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\User;

class WfaActionLog extends WfaBaseModel
{
    use HasFactory;            

    protected $with = ['actioner'];

    public function wfaActionable()
    {
        return $this->morphTo();
    }

    public function event(){
        return $this->belongsTo(WfaMasterEvent::class, 'wfa_master_event_id');
    }

    public function actioner(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
