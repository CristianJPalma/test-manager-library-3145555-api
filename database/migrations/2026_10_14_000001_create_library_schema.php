<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// T-02: esquema library en UTC. Sin tablas (las crean las siguientes migraciones).
// Art. V: infra levanta motor vacío, DDL solo aquí.
return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS library');
        DB::statement("SET timezone TO 'UTC'");
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS library CASCADE');
    }
};
