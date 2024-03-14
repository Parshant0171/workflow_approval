<?php

namespace Jgu\Wfa\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use function Illuminate\Events\queueable;

use App\Models\User;

class WfaAction extends WfaBaseModel
{
    use HasFactory;            

    protected $with = ['actioner'];

    private static function onEvent(WfaAction $action){        
        $modelType = $action->wfa_actionable_type;
        $model = $modelType::withoutGlobalScopes()->find($action->wfa_actionable_id);
        
        $wfaStructure = $model->wfa();
        if($wfaStructure->requires_pending_mapping == 1){    
            $model->load(['wfaPendingMappings']);
            foreach($model->wfaPendingMappings as $map){
                $map->delete();
            }                    
            $modelApprovers = $model->getAvailableActionableUsers($action->event()->first()->role_id);
            if(!empty($modelApprovers)){
                foreach($modelApprovers as $approver){
                    $map = new WfaMapping();
                    $map->user_id = $approver;
                    $model->wfaPendingMappings()->save($map);
                }
            }
        }
        
    }
    
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(queueable(function ($action) {
            self::onEvent($action);            
        }));

        static::deleted(queueable(function ($action) {
            self::onEvent($action);            
        }));

        static::updated(queueable(function ($action) {
            self::onEvent($action);            
        }));
    }

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
