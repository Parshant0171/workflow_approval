<?php

namespace Jgu\Wfa\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Yajra\Auditable\AuditableWithDeletesTrait;

class WfaApprovingMethod extends Model
{
    use HasFactory;
    use AuditableWithDeletesTrait, SoftDeletes;
}
