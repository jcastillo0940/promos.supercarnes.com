<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_type' => ['required', Rule::in(['cedula', 'passport', 'residente'])],
            'document_number' => ['required', 'string', 'max:40'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ]);

        $documentNumber = $this->normalizeDocumentNumber($data['document_type'], $data['document_number']);
        if ($documentNumber === '') {
            throw ValidationException::withMessages([
                'document_number' => 'Debes ingresar un documento valido.',
            ]);
        }

        $existingUser = User::query()->where('cedula', $documentNumber)->first();
        $emailOwner = User::query()->where('email', $data['email'])->first();

        if ($emailOwner && (! $existingUser || $emailOwner->id !== $existingUser->id)) {
            throw ValidationException::withMessages([
                'email' => 'Este correo ya esta registrado. Inicia sesion.',
            ]);
        }

        $user = $existingUser ?: new User();
        $user->forceFill([
            'name' => $data['full_name'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'cedula' => $documentNumber,
            'document_type' => $data['document_type'],
            'password' => Hash::make($data['password']),
            'role' => $user->role ?: 'client',
            'is_active' => true,
            'resides_in_panama' => true,
            'is_employee' => false,
            'registration_completed_at' => $user->registration_completed_at ?: now(),
            'accepted_terms_at' => $user->accepted_terms_at ?: now(),
        ])->save();

        return $this->tokenResponse($user, $existingUser ? 'Cuenta actualizada e iniciada.' : 'Registro completado.');
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        $login = trim($data['login']);
        $user = User::query()
            ->where('email', $login)
            ->orWhere('cedula', $this->normalizeDocumentNumber('cedula', $login))
            ->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'Credenciales invalidas.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => 'Este usuario no esta activo.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->tokenResponse($user, 'Sesion iniciada.');
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->serializeUser($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada.',
        ]);
    }

    public function dreamProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entrepreneur_name' => ['required', 'string', 'max:180'],
            'entrepreneur_province' => ['required', 'string', 'max:120'],
            'entrepreneur_reason' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'entrepreneur_name' => $data['entrepreneur_name'],
            'entrepreneur_province' => $data['entrepreneur_province'],
            'entrepreneur_reason' => $data['entrepreneur_reason'],
        ])->save();

        return response()->json([
            'message' => 'Formulario de emprendimiento guardado.',
            'data' => $this->serializeUser($user->refresh()),
        ]);
    }

    private function tokenResponse(User $user, string $message): JsonResponse
    {
        $user->tokens()->where('name', 'participant-session')->delete();
        $token = $user->createToken('participant-session', ['participant'])->plainTextToken;

        return response()->json([
            'message' => $message,
            'token' => $token,
            'data' => $this->serializeUser($user),
        ]);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'role' => $user->role,
            'full_name' => $user->full_name ?? $user->name,
            'cedula' => $user->cedula,
            'document_type' => $user->document_type ?? 'cedula',
            'email' => $user->email,
            'phone' => $user->phone,
            'entrepreneur_name' => $user->entrepreneur_name,
            'entrepreneur_province' => $user->entrepreneur_province,
            'entrepreneur_reason' => $user->entrepreneur_reason,
            'dream_promo_qualified_at' => optional($user->dream_promo_qualified_at)->toIso8601String(),
        ];
    }

    private function normalizeDocumentNumber(string $documentType, string $value): string
    {
        $value = strtoupper(trim($value));

        if ($documentType === 'cedula') {
            return preg_replace('/[^0-9-]/', '', $value) ?? '';
        }

        return preg_replace('/[^A-Z0-9-]/', '', $value) ?? '';
    }
}
