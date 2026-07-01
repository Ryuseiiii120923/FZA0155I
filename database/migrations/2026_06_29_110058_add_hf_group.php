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
        Schema::connection('PRecord')->create('groups',function(Blueprint $table){
            $table->id();
            $table->integer('ppfno')->index();
            $table->string('hf_group',50);
            $table->string('operation', 10);
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
