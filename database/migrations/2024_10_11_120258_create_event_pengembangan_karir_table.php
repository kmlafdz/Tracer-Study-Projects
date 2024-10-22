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
        Schema::create('event_pengembangan_karir', function (Blueprint $table) {
            $table->id();
            $table->string('judul_event')->nullable(false);
            $table->text('deskripsi_event')->nullable(false);
            $table->timestamp('tanggal_mulai')->useCurrent()->nullable(false);
            $table->timestamp('tanggal_akhir')->useCurrent()->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_pengembangan_karir');
    }
};

