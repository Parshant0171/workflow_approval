<?php

namespace Jgu\Wfa\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

if(config('wfa.useTenants') && trait_exists('\App\Traits\ExTrait')){
    class WfaBaseModelHardDelete extends Model
    {
        use HasFactory;
        use \App\Traits\ExTrait;     

    }
}else{
    class WfaBaseModelHardDelete extends Model
    {
        use HasFactory;
        // use \App\Traits\ExTrait;

    }
}