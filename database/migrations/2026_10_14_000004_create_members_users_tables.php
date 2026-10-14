<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// T-02: member + user (data-model §3-§4).
// user.password_hash nunca se indexa (privacidad §7).
// ck_user_role pendiente T-18: hoy solo dominio, NO se crea aquí.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('library.member', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name', 200);
            $table->string('document_number', 30)->unique();
        });

        Schema::create('library.user', function (Blueprint $table) {
            // Tabla comillada: library."user" (user es reservada).
            $table->uuid('id')->primary();
            $table->string('username', 120)->unique();
            $table->string('password_hash', 512);
            $table->string('role', 40);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library.user');
        Schema::dropIfExists('library.member');
    }
};
