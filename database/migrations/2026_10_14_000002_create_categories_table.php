<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// T-02: category — semilla de solo lectura (data-model §3).
// Sin DEFAULT en ninguna columna.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('library.category', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library.category');
    }
};
