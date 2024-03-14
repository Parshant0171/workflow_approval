<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWfaApproverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wfa_approving_methods', function (Blueprint $table) {
            $table->id();

            $table->string('method_name');
            $table->string('display_name');
            //direct_role, direct_user, relationship_through_table 
            
            $table->auditableWithDeletes();

            $table->timestampTz('created_at', $precision = 0)->useCurrent();
            $table->timestampTz('updated_at', $precision = 0)->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes($column = 'deleted_at', $precision = 0);
        });

        Schema::create('wfa_approvers', function (Blueprint $table) {
            $table->id();

            if(config('wfa.useTenants') == 1){
                $table->foreignId('tenant_id');            
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            }

            $table->foreignId('wfa_master_event_id');            
            $table->foreign('wfa_master_event_id')->references('id')->on('wfa_master_events')->onDelete('cascade');

            $table->foreignId('wfa_approving_method_id');            
            $table->foreign('wfa_approving_method_id')->references('id')->on('wfa_approving_methods')->onDelete('cascade');

            $table->nullableMorphs('wfa_approvable'); //for user_id and role_id

            $table->json('relationship_clause')->nullable();            

            $table->auditableWithDeletes();
            
            $table->timestampTz('created_at', $precision = 0)->useCurrent();
            $table->timestampTz('updated_at', $precision = 0)->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes($column = 'deleted_at', $precision = 0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wfa_approvers');
        Schema::dropIfExists('wfa_approving_methods');        
    }
}
