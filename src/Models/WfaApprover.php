<?php

namespace Jgu\Wfa\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfaApprover extends WfaBaseModel
{
    use HasFactory;

    protected $with = ['approvingMethod'];

    public function approvingMethod(){
        return $this->belongsTo(WfaApprovingMethod::class, 'wfa_approving_method_id');
    }
}
