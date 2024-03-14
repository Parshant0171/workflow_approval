<?php

namespace Jgu\Wfa\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Yajra\Auditable\AuditableWithDeletesTrait;

if(config('wfa.useTenants') && trait_exists('\App\Traits\ExTrait')){
    class WfaBaseModel extends Model
    {
        use HasFactory;
        use AuditableWithDeletesTrait, SoftDeletes;
        use \App\Traits\ExTrait;     

    }
}else{
    class WfaBaseModel extends Model
    {
        use HasFactory;
        use AuditableWithDeletesTrait, SoftDeletes;
        // use \App\Traits\ExTrait;

    }
}