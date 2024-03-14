<?php

namespace Jgu\Wfa\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfaSequenceGroup extends WfaBaseModel
{
    use HasFactory;

    public function events(){
        return $this->hasMany(WfaMasterEvent::class);
    }
}
