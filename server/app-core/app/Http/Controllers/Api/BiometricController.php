<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BiometricCode;
use App\Models\BiometricKey;
use App\Support\AttendanceService;
use App\Support\BiometricGuard;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BiometricController extends Controller
{
    /** GET /api/biometric/challenge — a fresh single-use signing challenge. */
    public function challenge(Request $request): JsonResponse
    {
        $midwife = $request->user();
        $challenge = BiometricGuard::issueChallenge($midwife);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $challenge->token,
                'challenge' => $challenge->challenge,
                'state' => BiometricGuard::state($midwife),
            ],
        ]);
    }

    /**
     * POST /api/biometric/enroll {code, public_key}
     * One-time office code + the phone-generated public key (PEM). Any
     * previous key is replaced; the code is consumed.
     */
    public function enroll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
            'public_key' => ['required', 'string', 'max:2000'],
        ]);

        $midwife = $request->user();

        $code = BiometricCode::where('midwife_id', $midwife->id)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $code || $code->expires_at->isPast() || ! Hash::check($data['code'], $code->code_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'کد ثبت‌نام نادرست یا منقضی است. از دفتر کد تازه بگیرید.',
                'error_code' => 'bad_enroll_code',
            ], 422);
        }

        // Sanity: it must parse as a public key.
        if (openssl_pkey_get_public($data['public_key']) === false) {
            return response()->json([
                'success' => false,
                'message' => 'کلید عمومی نامعتبر است.',
                'error_code' => 'bad_key',
            ], 422);
        }

        BiometricKey::where('midwife_id', $midwife->id)
            ->where('status', 'active')
            ->update(['status' => 'replaced']);

        $key = BiometricKey::create([
            'midwife_id' => $midwife->id,
            'public_key' => trim($data['public_key']),
            'status' => 'active',
            'enrolled_at' => AttendanceService::now(),
        ]);

        $code->update(['used_at' => Carbon::now()]);

        return response()->json([
            'success' => true,
            'message' => 'اثر انگشت شما ثبت شد. از این پس حاضری فقط با انگشت خودتان ثبت می‌شود.',
            'data' => ['state' => 'active', 'enrolled_at' => $key->enrolled_at->toIso8601String()],
        ]);
    }

    /**
     * POST /api/biometric/invalidated — the app reports that the phone's
     * fingerprint set changed and the hardware key died. Attendance stays
     * blocked until the office re-enrolls her.
     */
    public function invalidated(Request $request): JsonResponse
    {
        $midwife = $request->user();

        $key = BiometricGuard::activeKey($midwife);
        if ($key) {
            $key->update([
                'status' => 'invalidated',
                'invalidated_at' => AttendanceService::now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'ثبت‌نام اثر انگشت باطل شد. برای فعال‌سازی دوباره به دفتر مراجعه کنید.',
            'data' => ['state' => BiometricGuard::state($midwife)],
        ]);
    }
}
