<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMappingRequiredFlagToMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wfa_masters', function (Blueprint $table) {
            //
            $table->tinyInteger('requires_pending_mapping')->default(0)->after('model_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wfa_masters', function (Blueprint $table) {
            $table->dropColumn('requires_pending_mapping');
        });
    }
}
