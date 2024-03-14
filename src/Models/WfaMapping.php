<?php

namespace Jgu\Wfa\Models;

use App\Models\User;
use function Illuminate\Events\queueable;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class WfaMapping extends WfaBaseModelHardDelete
{
    use HasFactory;            

    public function user() {
        return $this->belongsTo(User::class,'user_id');
    }

    public function wfa_actionable()
    {
        return $this->morphTo();
    }

}