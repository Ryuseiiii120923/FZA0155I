<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('PRecord')->table('hf_forms', function (Blueprint $table) {
            $table->string('parentFormId', 36)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::connection('PRecord')->table('hf_forms', function (Blueprint $table) {
            $table->dropIndex(['parentFormId']);
            $table->dropColumn('parentFormId');
        });
    }
};
