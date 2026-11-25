<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const USERNAME_RULE = ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_.]+$/'];

    private const MESSAGES = [
        'username.regex' => 'Username hanya huruf kecil, angka, titik, dan garis bawah.',
        'username.unique' => 'Username sudah dipakai.',
        'username.min' => 'Username minimal 3 karakter.',
        'email.unique' => 'Email sudah terdaftar.',
        'email.email' => 'Format email tidak valid.',
        'password.confirmed' => 'Konfirmasi password tidak sama.',
        'password.min' => 'Password minimal 8 karakter.',
        'current_password.current_password' => 'Password lama salah.',
    ];

    public function register(Request $request): JsonResponse
    {
        $request->merge([
            'username' => strtolower(trim((string) $request->input('username'))),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'username' => [...self::USERNAME_RULE, 'unique:users,username'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ], self::MESSAGES);

        $user = User::create([
            'name' => trim($data['name']),
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return $this->tokenResponse($user, $data['device_name'] ?? 'android', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $login = strtolower(trim($data['login']));
        $user = User::where(str_contains($login, '@') ? 'email' : 'username', $login)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'Username/email atau password salah.']);
        }

        return $this->tokenResponse($user, $data['device_name'] ?? 'android');
    }

    /**
     * Daftar/masuk dengan Google. ID token dari aplikasi diverifikasi ke Google;
     * akun dicari lewat google_id, lalu email (akun lama otomatis tertaut), selain itu dibuat baru.
     */
    public function google(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string', 'max:4096'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);
        $clients = config('services.google.client_ids', []);
        if (empty($clients)) {
            throw ValidationException::withMessages(['id_token' => 'Login Google belum diaktifkan di server.']);
        }

        $res = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $data['id_token']]);
        $t = $res->json() ?? [];
        $valid = $res->ok()
            && in_array($t['aud'] ?? null, $clients, true)
            && in_array($t['iss'] ?? null, ['accounts.google.com', 'https://accounts.google.com'], true)
            && (int) ($t['exp'] ?? 0) > time()
            && filter_var($t['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && ! empty($t['sub']) && ! empty($t['email']);
        if (! $valid) {
            throw ValidationException::withMessages(['id_token' => 'Login Google gagal diverifikasi. Coba lagi.']);
        }

        $email = strtolower($t['email']);
        $user = User::where('google_id', $t['sub'])->first() ?? User::where('email', $email)->first();
        $created = false;
        if (! $user) {
            $user = User::create([
                'name' => mb_substr(trim($t['name'] ?? '') ?: strstr($email, '@', true), 0, 60),
                'username' => $this->uniqueUsername($email),
                'email' => $email,
                'password' => Str::random(40), // belum punya password; bisa dibuat lewat "Lupa password"
            ]);
            $created = true;
        }
        if ($user->google_id !== $t['sub']) {
            $user->forceFill(['google_id' => $t['sub']])->save();
        }
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $this->tokenResponse($user, $data['device_name'] ?? 'android', $created ? 201 : 200);
    }

    /** Username dari bagian depan email, dibuat unik dengan angka bila perlu. */
    private function uniqueUsername(string $email): string
    {
        $base = substr(preg_replace('/[^a-z0-9_.]/', '', strtolower(strstr($email, '@', true))) ?: 'user', 0, 24);
        if (strlen($base) < 3) $base = str_pad($base, 3, '0');
        $name = $base;
        for ($i = 1; User::where('username', $name)->exists(); $i++) {
            $name = $base.random_int(10, 9999);
        }

        return $name;
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->toProfile()]);
    }

    /** Pengguna mengajukan upgrade Premium; disetujui admin lewat /admin. */
    public function requestPremium(Request $request): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:255']]);
        $user = $request->user();
        if (! $user->isPremium()) {
            $user->forceFill([
                'premium_requested_at' => now(),
                'premium_request_note' => isset($data['note']) ? trim($data['note']) : null,
            ])->save();
        }

        return response()->json(['user' => $user->toProfile()]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->merge([
            'username' => strtolower(trim((string) $request->input('username'))),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'username' => [...self::USERNAME_RULE, Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
        ], self::MESSAGES);
        $user->update(['name' => trim($data['name']), 'username' => $data['username'], 'email' => $data['email']]);

        return response()->json(['user' => $user->toProfile()]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], self::MESSAGES);
        $request->user()->update(['password' => $data['password']]);

        return response()->json(['ok' => true]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => strtolower((string) $request->input('email'))]);

        // Selalu sukses agar tidak membocorkan email mana yang terdaftar.
        return response()->json(['ok' => true, 'message' => 'Jika email terdaftar, tautan reset password sudah dikirim.']);
    }

    private function tokenResponse(User $user, string $device, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken($device)->plainTextToken,
            'user' => $user->toProfile(),
        ], $status);
    }
}
