<?php

namespace App\Support;

use App\Models\BiometricChallenge;
use App\Models\BiometricKey;
use App\Models\Midwife;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Fingerprint-key binding (his approved plan, 2026-11-28 — no selfie).
 *
 * The phone's secure hardware holds a private key that ONLY the midwife's
 * enrolled fingerprint set can use, and that dies permanently if the
 * phone's fingerprint set changes. The server stores the PUBLIC key and
 * verifies a fresh signature per check-in / check-out:
 *   state 'none'        → never enrolled: attendance works (rollout phase),
 *                         the panel shows «ثبت نشده».
 *   state 'active'      → a valid signature is REQUIRED.
 *   state 'invalidated' → the key died on the phone (fingerprint set
 *                         changed): attendance is BLOCKED until the office
 *                         re-enrolls her with a new one-time code.
 */
class BiometricGuard
{
    public static function activeKey(Midwife $midwife): ?BiometricKey
    {
        return BiometricKey::where('midwife_id', $midwife->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();
    }

    /** @return 'none'|'active'|'invalidated' */
    public static function state(Midwife $midwife): string
    {
        if (self::activeKey($midwife)) {
            return 'active';
        }

        $hasInvalidated = BiometricKey::where('midwife_id', $midwife->id)
            ->where('status', 'invalidated')
            ->exists();

        return $hasInvalidated ? 'invalidated' : 'none';
    }

    /** Issue a single-use challenge (2-minute life) for a signed request. */
    public static function issueChallenge(Midwife $midwife): BiometricChallenge
    {
        return BiometricChallenge::create([
            'midwife_id' => $midwife->id,
            'token' => Str::random(48),
            'challenge' => base64_encode(random_bytes(32)),
            'expires_at' => Carbon::now()->addMinutes(2),
        ]);
    }

    /**
     * Verify an ECDSA signature (DER, base64) over the raw challenge bytes.
     * Consumes the challenge either way.
     */
    public static function verifyChallenge(Midwife $midwife, ?string $token, ?string $signatureB64): bool
    {
        if (! $token || ! $signatureB64) {
            return false;
        }

        $challenge = BiometricChallenge::where('token', $token)
            ->where('midwife_id', $midwife->id)
            ->whereNull('used_at')
            ->first();

        if (! $challenge || $challenge->expires_at->isPast()) {
            return false;
        }

        $challenge->update(['used_at' => Carbon::now()]);

        return self::verifyRaw($midwife, base64_decode($challenge->challenge), $signatureB64);
    }

    /** Verify a signature over an arbitrary payload (offline sync records). */
    public static function verifyPayload(Midwife $midwife, string $payload, ?string $signatureB64): bool
    {
        if (! $signatureB64) {
            return false;
        }

        return self::verifyRaw($midwife, $payload, $signatureB64);
    }

    private static function verifyRaw(Midwife $midwife, string $data, string $signatureB64): bool
    {
        $key = self::activeKey($midwife);
        if (! $key) {
            return false;
        }

        $signature = base64_decode($signatureB64, true);
        if ($signature === false) {
            return false;
        }

        return openssl_verify($data, $signature, $key->public_key, OPENSSL_ALGO_SHA256) === 1;
    }

    /** Canonical string an offline record signs at capture time. */
    public static function offlinePayload(string $clientUuid, string $deviceTimeIso, float $lat, float $lng): string
    {
        return $clientUuid.'|'.$deviceTimeIso.'|'.sprintf('%.7F', $lat).'|'.sprintf('%.7F', $lng);
    }
}
