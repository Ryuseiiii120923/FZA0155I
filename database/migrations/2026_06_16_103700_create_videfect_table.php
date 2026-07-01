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
       Schema::connection('PRecord')->create('vi_defect', function (Blueprint $table) {
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('defect', 30);
            $table->integer('qty');
            $table->string('updated_by', 15);
            $table->string('formId', 100);
        });
        Schema::connection('PRecord')->create('vi_forms', function (Blueprint $table) {
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('hfid', 10)->index();
            $table->integer('total_inspect');
            $table->string('updated_by', 15);
            $table->string('formId', 100);
            $table->integer('goodQty');
            $table->integer('totalNG');
            $table->smallInteger('forRework')->nullable();
            $table->string('finishingProcedure', 50);
            $table->string('operation', 50)->index();
            $table->string('process')->index();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::connection('PRecord')->create('vi_small', function (Blueprint $table) {
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('hfid', 10)->index();
            $table->string('largeDefect', 30);
            $table->string('smallDefect', 30);
            $table->integer('qty');
            $table->string('updated_by', 15);
            $table->string('formId', 100);
        });

        Schema::connection('PRecord')->create('vi_rework', function (Blueprint $table) {
            $table->id();
            $table->integer('ppfno');
            $table->string('hfid', 10);
            $table->string('rework_type', 30);
            $table->integer('qty');
            $table->string('updated_by', 15);
            $table->integer('total_inspect');
            $table->smallInteger('proceedToWork')->nullable();
            $table->smallInteger('flgDone')->nullable();
            $table->integer('reworkNo')->nullable();
            $table->string('formId', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
