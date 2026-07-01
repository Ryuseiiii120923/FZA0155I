<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('PRecord')->create('hfrw_defects', function(Blueprint $table){
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('hfid', 10)->index();
            $table->string('defect',50);
            $table->integer('qty');
            $table->string('updated_by', 10);
            $table->string('inspect_REC', 100);
            $table->integer('reworkNo')->nullable();
        });

        Schema::connection('PRecord')->create('hfrw_forms', function(Blueprint $table){
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('hfid', 10)->index();
            $table->integer('total_inspect');
            $table->integer('goodQty');
            $table->integer('totaNG');
            $table->string('operation', 50)->index();
            $table->string('process')->index();
            $table->string('updated_by', 10);
            $table->string('inspect_REC', 100);
            $table->integer('reworkNo')->nullable();
            $table->timestamps();
        });

         Schema::connection('PRecord')->create('hfrw_small', function(Blueprint $table){
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('hfid', 10)->index();
            $table->string('large_defect',50);
            $table->string('small_defect',50);
            $table->integer('qty');
            $table->string('updated_by', 10);
            $table->string('inspect_REC', 100);
            $table->integer('reworkNo')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
