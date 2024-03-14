<?php

namespace Jgu\Wfa\Traits;

use Jgu\Wfa\Models\WfaMaster;

trait FormApprovable{

    use Approvable {
        Approvable::wfa as parentWfa;
    }

    abstract public function getFormId() : int;

    public function wfa(){
        return WfaMaster::where('model_path', $this->getClassName())
                            ->where('form_id', $this->getFormId())    
                            ->with(['events.approvers.approvingMethod', 'events.sequenceGroup'])
                            ->with(['events.actions' => function ($query){
                                        $query->where('wfa_actionable_id', $this->id);
                                    }])        
                            ->first();
    }
}


?>