<?php

namespace Jgu\Wfa\Traits;

use App\Models\WfaActionLog;
use Jgu\Wfa\Models\WfaAction;
use Jgu\Wfa\Models\WfaMaster;
use Jgu\Wfa\Models\WfaMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

trait Approvable{

    public function getClassName(){
        return $this->className ?? get_class();
    } 
    
    public function getCreatedBy(){        
        return $this->created_by ?? 0; 
    }

    public function wfa(){
        return WfaMaster::where('model_path', $this->getClassName())
        ->with(['events.approvers.approvingMethod', 'events.sequenceGroup'])
        ->with(['events.actions' => function ($query){
                    $query->where('wfa_actionable_id', $this->id);
                }])        
        ->first();
    }

    public function wfaPendingMappings(){
        return $this->morphMany(WfaMapping::class, 'wfa_actionable');
    }

    public function loadTimeline(){
        return $this->load(['actions.event', 'actions.actioner']);
    }

    public function actions(){
        return $this->morphMany(WfaAction::class, 'wfa_actionable');
    }

    public function actionsLog(){
        return $this->morphMany(WfaActionLog::class, 'wfa_actionable');
    }

    public function actionsWithTrashed(){
        return $this->morphMany(WfaAction::class, 'wfa_actionable')->withTrashed();
    }

    public function saveAction($eventId, $userId, $usingService = "default"){
        $wfa = new WfaAction();
        $wfa->wfa_master_event_id = $eventId;
        $wfa->user_id = $userId;
        $wfa->using_service = $usingService;
        if(WfaAction::where([
            "wfa_master_event_id" => $eventId,
            'wfa_actionable_type' => $this->getClassName(),
            'wfa_actionable_id' => $this->id,
        ])->exists() ){
            return $this->saveActionLog($eventId,$userId);
        }
        return $this->saveActionWithModel($wfa);
    }

    public function saveActionLog($eventId, $userId, $usingService = "default"){
        $wfaLog = new WfaActionLog();
        $wfaLog->wfa_master_event_id = $eventId;
        $wfaLog->user_id = $userId;
        $wfaLog->using_service = $usingService;
        return $this->saveActionLogWithModel($wfaLog);
    }

    public function saveActionWithModel(WfaAction $wfa){
        $this->actions()->save($wfa);
    }

    public function saveActionLogWithModel(WfaActionLog $wfa){
        $this->actionsLog()->save($wfa);
    }

    public function getAvailableActionableEvents($userId,$role =null){
        $structure = $this->wfa();
        $lastAction = $this->getLastAction();


        if(!$lastAction){            
            return $this->checkAvailableActionInSequence($structure, $userId, 1,$role);            
        }
        

        if($lastAction->event->is_last === 1) //check if wfa is closed
            return null;
        
        if($lastAction->event->equals_final_rejection === 1 || $lastAction->event->equals_final_approval === 1) //check if the event is last by rejection
            return null;

        if($lastAction->event->wfa_sequence_group_id === null){            
            return $this->checkAvailableActionInSequence($structure, $userId, $lastAction->event->sequence+1,$role);
        }else{
            if($this->checkIfSequenceGroupComplete($structure, $lastAction->event->sequence,$role)){
                return $this->checkAvailableActionInSequence($structure, $userId, $lastAction->event->sequence+1,$role);
            }else{
                return $this->checkAvailableActionInSequence($structure, $userId, $lastAction->event->sequence,$role);
            }            
        }        
    }

    public function getAvailableActionableUsers($role = null){
        $structure = $this->wfa();
        $lastAction = $this->getLastAction();
        $sequence = 1;        
        if($lastAction){
            if($lastAction->event->is_last === 1)
                return;
            else{
                if($lastAction->event->wfa_sequence_group_id === null){
                    $sequence = $lastAction->event->sequence+1;
                } else{
                    if($this->checkIfSequenceGroupComplete($structure, $lastAction->event->sequence, $role)){
                        $sequence = $lastAction->event->sequence+1;
                    }else{
                        $sequence = $lastAction->event->sequence;
                    }
                }
            }
        }        
        $eventsInSequence = $this->fetchEventsInSequence($sequence);
        return $this->fetchAvailableActionersForAvailableEvents($eventsInSequence,$role);        
    }

    public function getPendingEvents(){
        $structure = $this->wfa();
        $lastAction = $this->getLastAction();
        $sequence = 1;
        $events = array();
        if($lastAction){
            if($lastAction->event->is_last === 1)
                return;
            else{
                if($lastAction->event->wfa_sequence_group_id === null){
                    $sequence = $lastAction->event->sequence+1;
                } else{
                    if($this->checkIfSequenceGroupComplete($structure, $lastAction->event->sequence, $lastAction->event->role_id )){
                        $sequence = $lastAction->event->sequence+1;
                    }else{
                        $sequence = $lastAction->event->sequence;
                    }
                }
            }
        }        
        $eventsInSequence = $this->fetchEventsInSequence($sequence);
        foreach($eventsInSequence as $event ){
            if($event->role_id != $lastAction->role_id){
                continue;
            }
            if(sizeof($event->actions) != 0){
                continue;
            }

            $events[] =$event;

        }
        return $events;        
    }

    private function fetchAvailableActionersForAvailableEvents($eventsInSequence,$role =null){
        $actionableUsers = [];
        foreach($eventsInSequence as $event){
            if($event->role_id != $role)     {
                continue;
            } 
            if($event->actions && sizeof($event->actions)===0){
                if($event->approvers){
                    foreach($event->approvers as $approver){
                        $actionableUsers = array_merge($actionableUsers, $this->findApprovingUsers($approver));
                    }
                }
            }
        }
        
        return array_unique($actionableUsers);    
    }

    private function findApprovingUsers($approver){
        $users = [];
        if($approver->approvingMethod){
            switch($approver->approvingMethod->method_name){
                case "role":
                    $userRoles =  DB::table('user_roles')
                        ->select('user_roles.user_id')
                        ->join('users','users.id','=','user_roles.user_id')
                        ->where('user_roles.role_id',$approver->wfa_approvable_id) 
                        ->whereNull('users.deleted_at')    
                        ->get();
            
                    foreach($userRoles as $userRole){
                        $users[] = $userRole->user_id;
                    }

                    break;
                case "user":
                    $users[] = $approver->wfa_approvable_id;
                    break;
                case "related_by_table":
                    $relationshipClause = json_decode($approver->relationship_clause);
                    $relations = $this->getActionsRelatedTable($relationshipClause);                    
                    $prop = $relationshipClause->approver_id;
                    foreach($relations as $relation){
                        $users[] = $relation->$prop;
                    }
                    break;
                case "self":
                    $users[] = $this->getCreatedBy();
                    break;
                default:
                    break;     
            }
        }        
        return $users;
    }

    private function fetchEventsInSequence($sequence){
        
        if (in_array(FormApprovable::class, class_uses($this->getClassName()))) {
            $sequenceStructure = WfaMaster::where('model_path', $this->getClassName())
            ->where('form_id', $this->getFormId())   
            ->with(['events' => function($query) use ($sequence){
                        $query->where('sequence', $sequence);
                    }, 'events.approvers.approvingMethod', 'events.sequenceGroup', 'events.actions' => function ($query){
                        $query->where('wfa_actionable_id', $this->id);
                    }])        
            ->first();
        }else{
            $sequenceStructure = WfaMaster::where('model_path', $this->getClassName())
            ->with(['events' => function($query) use ($sequence){
                        $query->where('sequence', $sequence);
                    }, 'events.approvers.approvingMethod', 'events.sequenceGroup', 'events.actions' => function ($query){
                        $query->where('wfa_actionable_id', $this->id);
                    }])        
            ->first();
        }
        return $sequenceStructure->events;
    }   

    private function checkIfSequenceGroupComplete($structure, $sequence,$role=null){
        foreach($structure->events as $event){
            if($event->role_id != $role)     {
                continue;
            }   

            if($event->sequence === $sequence){
                if($event->is_skippable != 1) {
                    if($event->actions){
                        if(sizeof($event->actions) === 0)
                            return false;
                    }else   
                        return false;
                }
            }
        }
        return true;
    }
    
    private function checkAvailableActionInSequence($structure, $userId, $sequence,$role=null){        
        $newSequenceEvents = [];
        $availableEvents = [];
        $availableEventsIds = [];
        if($structure){
            foreach($structure->events as $event){    
                if($event->role_id != $role)     {
                    continue;
                }   
                if($event->sequence === $sequence && !$this->checkEventsActionInTimeline($event)){
                    $newSequenceEvents[] = $event;                
                    if($event->approvers){
                        foreach($event->approvers as $approver){
                            if($approver->approvingMethod){
                                switch($approver->approvingMethod->method_name){
                                    case "role":
                                        if($this->availableActionsRoleIdCheck($approver->wfa_approvable_id, $userId)){
                                            if(!in_array($event->id, $availableEventsIds)){
                                                $availableEvents[] = $event;
                                                $availableEventsIds[] = $event->id;
                                            }
                                        }
                                        break;
                                    case "user":
                                        if($this->availableActionsUserIdCheck($approver->wfa_approvable_id, $userId)){
                                            if(!in_array($event->id, $availableEventsIds)){
                                                $availableEvents[] = $event;
                                                $availableEventsIds[] = $event->id;
                                            }
                                        }
                                        break;
                                    case "related_by_table":
                                        if($this->availableActionsRelatedTableCheck(json_decode($approver->relationship_clause), $userId)){
                                            if(!in_array($event->id, $availableEventsIds)){
                                                $availableEvents[] = $event;
                                                $availableEventsIds[] = $event->id;
                                            }
                                        }
                                        break;
                                    case "self":
                                        if($this->getCreatedBy() !== $userId){                                        
                                            if(!in_array($event->id, $availableEventsIds)){
                                                $availableEvents[] = $event;
                                                $availableEventsIds[] = $event->id;
                                            }
                                        }
                                        break;
                                    default:
                                        break;     
                                }
                            }
                        }
                    }
                }
            }
        }
        return $availableEvents;
    }    

    private function checkEventsActionInTimeline($events) {
       $flag = false;
        $timelines = $this->loadTimeline()->actions->toArray();
       foreach($timelines as $timeline){
            if($events->id == $timeline["event"]["id"]){
                $flag = true;
                break;
            }
       }
       return $flag;
    }
    private function availableActionsRoleIdCheck($roleId, $userId){        
        $userRoles = DB::table('user_roles')
                        ->where('role_id', $roleId)
                        ->where('user_id', $userId)
                        ->get();        
        return sizeof($userRoles)>0 ? true : false;
    }

    private function availableActionsUserIdCheck($approverUserId, $userId){
        return $approverUserId === $userId ? true : false;
    }

    private function availableActionsRelatedTableCheck($approveConfig, $userId){
        $relations = DB::table($approveConfig->table_name)
                        ->where($approveConfig->creator_id, $this->getCreatedBy())
                        ->where($approveConfig->approver_id, $userId)
                        ->get();
        return sizeof($relations)>0 ? true : false;
    }

    private function getActionsRelatedTable($approveConfig){
        $relations = DB::table($approveConfig->table_name)
                        ->where($approveConfig->creator_id, $this->getCreatedBy())                        
                        ->get();
        return $relations;
    }

    public function getLastAction(){
        return WfaAction::where('wfa_actionable_type', $this->getClassName())
        ->where('wfa_actionable_id', $this->id)->with('event')->orderBy('id','desc')->first();
    }

    public function isFinallyApproved(){
        $actions = $this->whereHas('actions.event', function (Builder $query){
            $query->where('equals_final_approval', 1);
        })->get();
        if(sizeof($actions)>0) return true;
        return false;
    }

    public function isFinallyRejected(){
        $actions = $this->whereHas('actions.event', function (Builder $query){
            $query->where('equals_final_rejection', 1);
        })->get();
        if(sizeof($actions)>0) return true;
        return false;
    }

}

?>