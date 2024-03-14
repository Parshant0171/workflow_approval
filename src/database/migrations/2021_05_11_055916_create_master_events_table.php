<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('wfa_masters', function (Blueprint $table) {
            $table->id(); 

            if(config('wfa.useTenants') == 1){
                $table->foreignId('tenant_id');            
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            }

            $table->string('table_name');
            $table->index('table_name');                    

            $table->string('model_path');

            $table->foreignId('form_id')->nullable();            

            $table->tinyInteger('is_active')->default(0);

            $table->auditableWithDeletes();

            $table->timestampTz('created_at', $precision = 0)->useCurrent();
            $table->timestampTz('updated_at', $precision = 0)->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes($column = 'deleted_at', $precision = 0);
        });

        Schema::create('wfa_master_events', function (Blueprint $table) {
            $table->id();
            if(env('WFA_USE_TENANT') == 1){
                $table->foreignId('tenant_id');            
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            }

            $table->foreignId('wfa_master_id');            
            $table->foreign('wfa_master_id')->references('id')->on('wfa_masters')->onDelete('cascade');

            $table->string('event_name');
            $table->string('display_name');

            $table->tinyInteger('sequence')->nullable();
            
            $table->foreignId('wfa_sequence_group_id')->nullable();            
            $table->foreign('wfa_sequence_group_id')->references('id')->on('wfa_sequence_groups')->onDelete('cascade');

            $table->tinyInteger('is_last')->default(0);                        

            $table->tinyInteger('equals_final_approval')->default(0);
            $table->tinyInteger('equals_final_rejection')->default(0);

            $table->string('unique_event_code');
            $table->unique('unique_event_code');

            $table->json('display_config')->nullable();

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
        Schema::dropIfExists('wfa_master_events');
        Schema::dropIfExists('wfa_masters');
    }
}
