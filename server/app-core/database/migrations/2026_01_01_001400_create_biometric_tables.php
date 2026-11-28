<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fingerprint-key binding (his approved plan, 2026-11-28 — NO selfie):
 * the app generates a hardware key pair locked to the midwife's enrolled
 * fingerprint set; the PUBLIC key lives here. Adding any fingerprint on
 * the phone permanently invalidates the key on-device; the app reports
 * it and this row's status becomes 'invalidated' until the office
 * re-enrolls her with a fresh one-time code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('midwife_id')->constrained()->cascadeOnDelete();
            $table->text('public_key'); // PEM (X.509 SubjectPublicKeyInfo)
            $table->string('status', 16)->default('active'); // active | invalidated | replaced
            $table->timestamp('enrolled_at');
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('biometric_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('midwife_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('biometric_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('midwife_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('challenge', 64); // base64 of 32 random bytes
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_challenges');
        Schema::dropIfExists('biometric_codes');
        Schema::dropIfExists('biometric_keys');
    }
};
