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
        Schema::connection('PRecord')->create('inspector_defect', function (Blueprint $table) {
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('inspectorId', 10)->index();
            $table->string('inspName', 50)->index();
            $table->string('defect', 50);
            $table->integer('qty');
            $table->string('process', 30);
            $table->string('operation', 30);
            $table->string('updated_by',10);
            $table->timestamps();
        });
        Schema::connection('PRecord')->create('inspector_small', function (Blueprint $table) {
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('inspectorId', 10)->index();
            $table->string('small_defect', 50);
            $table->string('large_defect', 50);
            $table->integer('qty');
            $table->string('process', 30);
            $table->string('operation', 30);
            $table->string('updated_by',10);
            $table->timestamps();
        });
        Schema::connection('PRecord')->create('inspector_pr', function (Blueprint $table) {
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('inspectorId', 10)->index();
            $table->integer('total_inspect');
            $table->integer('totalNg');
            $table->integer('totalRework');
            $table->integer('totalGood');
            $table->string('process', 30)->index();
            $table->string('operation', 30)->index();
            $table->string('updated_by',10);
            $table->timestamps();
        });
          Schema::connection('PRecord')->create('inspector_rework', function (Blueprint $table) {
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('hfno', 10)->index();
            $table->string('inspectorId', 10)->index();
            $table->string('inspName', 50)->index();
            $table->string('rework', 50);
            $table->integer('qty');
            $table->integer('total_inspect');
            $table->string('process', 30)->index();
            $table->string('operation', 30)->index();
            $table->string('updated_by',10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('PRecord')->dropIfExists('inspector_rework');
        Schema::connection('PRecord')->dropIfExists('inspector_small');
        Schema::connection('PRecord')->dropIfExists('inspector_forms');
        Schema::connection('PRecord')->dropIfExists('inspector_defect');
    }
};
