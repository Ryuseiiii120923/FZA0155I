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
        Schema::connection('PRecord')->create('hf_sub_forms', function (Blueprint $table) {
            $table->id();
            $table->string('formId', 36)->unique();
            $table->string('parentFormId', 36)->index();   // hf_forms.formId of the main process
            $table->unsignedBigInteger('ppfno');
            $table->string('hfid', 10);
            $table->integer('total_inspect');
            $table->integer('goodQty')->default(0);
            $table->integer('totalNg')->default(0);
            $table->integer('totalRework')->default(0);
            $table->boolean('forRework')->default(false);
            $table->string('finishingProcedure')->nullable();
            $table->string('process', 20)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('updated_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['ppfno', 'updated_by']);
        });
    }

    public function down(): void
    {
        Schema::connection('PRecord')->dropIfExists('hf_sub_forms');
    }
};
