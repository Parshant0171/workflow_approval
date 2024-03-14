# Installation

Add the following code in your composer.json

```
"repositories": [
    ...
    { "type": "vcs", "url": "https://github.com/jgu-it/workflow_approvals.git" },
    ...
  ]
```
Run `composer install`
Run `php artisan vendor:publish` and select wfa from the list

Update the `yourapp/config/wfa.php` set `useTenants` => true if required.

Run `php artisan config:cache`

# Configuration

## WFA Tables:
1. `wfa_master`: Master Table to make a model approvalble
2. `wfa_sequence_groups`: Master Table to allow for multiple parallel approvals in one sequence
3. `wfa_master_events`: Master Table to store Events that occur in WFA Lifecycle
4. `wfa_approving_methods`: Master Table to store Different approving methods configurations
5. `wfa_approvers`: Master Table to store who can approve which event
6. `wfa_actions`: Transaction table that stores actions taken on models.
7. `wfa_mappings`: Table that contains a list of users who have pending actions on some model.
### WFA Master
`Jgu\Wfa\Models\WfaMaster`

Fields:

| Item | Type | Required | Description
| --- | --- | --- | --- |
| table_name | string | Yes | table name of the model to be made `Approvable` |
| model_path | string | Yes | namespace path of the model |
| requires_pending_mapping | tinyint (bool) | No. Default 0 | if set to 1, wfa_mappings table will contain a list of users who have pending approvals |
| form_id | int | No \ Default: `null` | To be used when a form builder form is being made `Approvable`. |
| is_active | tinyint \ to be used as boolean | No \ Default: `0` | To indicate whether a WFA Master is active. Only active rows will be fetched |

### WFA Sequence Groups
`Jgu\Wfa\Models\WfaSequenceGroup`

Fields:

| Item | Type | Required | Description
| --- | --- | --- | --- |
| sequence_group_name | string | Yes |  |
| display_name | string | Yes |  |

### WFA Master Events
`Jgu\Wfa\Models\WfaMasterEvent`

Fields:

| Item | Type | Required | Description
| --- | --- | --- | --- |
| wfa_master_id | int \ Relationship | Yes | Belongs to WFAMaster |
| event_name | string | Yes | name of the event |
| display_name | string | Yes | display name of the event |
| sequence | tinyint | No \ Default: `null` | Don't keep it null. Sequence in which an event must be performed. If `sequence_group_id` is null, only one action can be performed per event. |
| sequence_group_id | int | No \ Defualt: `null` | Belongs to WFA Sequence Groups for parallel actions |
| is_last | tinyint | No \ Defualt: `0` | If 1, the event would be considered as last |
| equals_final_approval | tinyint | no \ Defualt: `0` | If 1, the event would be considered finally approved (for frontend visibility) |
| equals_final_rejection | tinyint | no \ Defualt: `0` | If 1, the event would be considered finally rejected (for frontend visibility) |
| unique_code | string | Yes | Unique code of the event. Eventually can be used for QR codes probably |
| display_config | JSON | No | Display configuration or any other optional parameters that can be used at the front end |

## WFA Approving Methods
`Jgu\Wfa\Models\WfaMasterEvent`

//to-do: Add Seeders

SQL Insertion Query:

```
INSERT INTO `wfa_approving_methods` VALUES (1,'user','User',NULL,NULL,NULL,'2021-05-11 07:09:48','2021-05-11 07:09:48',NULL),(2,'role','Role',NULL,NULL,NULL,'2021-05-11 07:10:11','2021-05-11 07:10:11',NULL),(3,'related_by_table','Related By Table',NULL,NULL,NULL,'2021-05-11 07:10:11','2021-05-11 07:10:11',NULL),(4,'self','Self',NULL,NULL,NULL,'2021-05-12 10:10:55','2021-05-12 10:10:55',NULL);
```

Four `Approving Methods` are available. These methods need to be mapped in `wfa_approvers`:
1. `User`: Mapped to ID of single/multiple users.
2. `Role`: Mapped to ID of single/multiple user roles.
Dependency: `user_roles` table with `user_id` and `role_id`.
3. `Related By Table`: User mapping with user in any table. Eg. Table: `user_has_parents` with mapping `child_user_id`, `parent_user_id`.
4. `Self`: The creator of the row will be able to action on the events.

## WFA Approvers
`Jgu\Wfa\Models\WfaApprovers`

Fields:

| Item | Type | Required | Description
| --- | --- | --- | --- |
| wfa_master_event_id | int \ Relationship | Yes | Mapping with master event |
| wfa_approving_method_id | int \ Relationship | Yes | Mapping with approving method |
| wfa_approvable_type | string | No | `App\Models\Users` is approving_method = `user` \ `App\Models\Roles` if approving_method = `role` |
| wfa_approvable_id | int \ Relationship | No | ID of the approable type. User_Id or Role_Id |
| relationship_clause | JSON | No | Used when `approving_method = related_by_table`. Example configuration: `{"creator_id": "child_user_id", "table_name": "user_relations", "approver_id": "parent_user_id"}` |


# Make a model approvable under WFA Package:

## For Regular Models
In the Eloquent Model file, use the Trait: Approvable

```
use Jgu\Wfa\Traits\Approvable;

class User extends Model
{
    
    use Approvable;    
    ...
    ...
    ...
```

## For Form Builder Models

In the Eloquent Model file, use the Trait: Approvable

```
use Jgu\Wfa\Traits\FormApprovable;

class User extends Model
{
    
    use FormApprovable;    
    ...
    ...
    ...
```

# Available Methods

## Get the Timeline of Actions

Get all the actions that have happened on a particular model.

```

$user = User::find(1);
$user->loadTimeline();
...
```

$user->actions will conain a Laravel Collection of wfa_actions table for model `App\Models\Users` of `ID=1`

Alternatively, you can eager load all the actions while loading the User.

```
$user = User::where('id',1)-
        >with([
                'actions.event', 
                'actions.actioner'
            ])
        ->first();
```

## Get Available Approval Events for given Model & User

```
$model = Model::find(1);
$availableActions = $model->getAvailableActionableEvents($userId);
```

## Insert a new Aciton

To save a new action, you may use one the following two methods:

```
$user = User::find(1);
//$user->saveAction($eventId, $userId /* actionerId */, $usingService = "default")

$user->saveAcation(1,1);

//or

$wfaAction = new \Path\To\WfaAction();
$wfaAction->event_id = 1; 
$wfaAction->user_id = 1; 
$wfaAction->using_service = "any string"; //optional | default="default"

$user->saveActionWithModel($wfaAction);
```

## Get the last action on the model

```
$user = User::find(1);
$user->getLastAction();
```

## Get Final Status - Finally Approved | Rejected

```
$user = User::find(1);
$user->isFinallyApproved(); //returns true or false
$user->isFinallyRejected(); //returns true or false
```

# Extending

## FormApprovalble

Override the following function in your model to get the Form ID of the Submission

```
public function getFormId(){
    return $this->form_id;
}
```

## Approvable

Override the following function in your model to get the class name 

```
public function getClassName(){
    return $this->className ?? get_class();
}
```

Override the following function in your model to get the Creator ID.

```    
public function getCreatedBy(){        
    return $this->created_by ?? 0;
}
```