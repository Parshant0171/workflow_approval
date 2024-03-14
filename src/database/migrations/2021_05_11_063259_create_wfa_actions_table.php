<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWfaActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wfa_actions', function (Blueprint $table) {
            $table->id();
            
            if(config('wfa.useTenants') == 1){
                $table->foreignId('tenant_id');            
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            }

            $table->morphs('wfa_actionable');
            
            $table->foreignId('wfa_master_event_id');            
            $table->foreign('wfa_master_event_id')->references('id')->on('wfa_master_events')->onDelete('cascade');

            $table->foreignId('user_id');            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('using_service')->default('default');
            
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
        Schema::dropIfExists('wfa_actions');
    }
}
