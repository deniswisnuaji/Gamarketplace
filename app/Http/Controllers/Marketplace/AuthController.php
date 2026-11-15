<?php

namespace App\Http\Controllers\Marketplace;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\MeetupPoint;
use App\Models\User;
use App\Services\MarketplaceData;
use App\Services\MarketplaceFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function state(Request $request): JsonResponse
    {
        return $this->response($request, ['ok' => true]);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => 'required|string|email|max:255', 'password' => 'required|string|max:255', 'remember' => 'sometimes|boolean', 'admin' => 'sometimes|boolean']);
        $user = User::where('email', Str::lower(trim($data['email'])))->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || ($request->boolean('admin') && ! $user->isAdmin())) {
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect.']);
        }
        abort_unless($user->status === 'active', 403, 'Your account is suspended.');
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->response($request, ['user' => ['id' => (string) $user->id, 'role' => $user->role]]);
    }

    public function register(Request $request, CreateNewUser $creator): JsonResponse
    {
        abort_if($request->user() !== null, 409, 'Sign out before creating another account.');
        $user = $creator->create($request->all());

        // The existing registration UI signs in explicitly after successful registration.
        return $this->response($request, ['user' => ['id' => (string) $user->id, 'role' => $user->role]]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->response($request, ['ok' => true]);
    }

    public function password(Request $request): JsonResponse
    {
        $data = $request->validate(['oldPw' => 'required|string|current_password:web', 'newPw' => ['required', 'string', Password::min(8), 'confirmed', 'max:255']]);
        $user = $request->user();
        $user->password = $data['newPw'];
        $user->remember_token = Str::random(60);
        $user->save();
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        $request->session()->regenerate();

        return $this->response($request, ['ok' => true]);
    }

    public function profile(Request $request, MarketplaceFiles $files): JsonResponse
    {
        $user = $request->user();
        abort_unless((string) $request->input('id') === (string) $user->id, 403);
        $patch = $request->input('patch', []);
        if (isset($patch['username'])) {
            $patch['username'] = Str::lower(trim($patch['username']));
        }
        $data = Validator::make($patch, [
            'name' => 'sometimes|required|string|max:255',
            'username' => ['sometimes', 'nullable', 'string', 'regex:/^[a-z0-9_.]{3,40}$/', Rule::unique('users', 'username')->ignore($user->id)],
            'faculty' => 'sometimes|nullable|exists:faculties,code', 'tikum' => 'sometimes|nullable|exists:meetup_points,short_name',
            'bio' => 'sometimes|nullable|string|max:500', 'phone' => 'sometimes|nullable|string|max:32',
            'showPhone' => 'sometimes|boolean', 'showFaculty' => 'sometimes|boolean', 'photo' => 'sometimes|nullable|string|max:7500000',
            'prefs' => 'sometimes|array',
            'prefs.notifMessages' => 'sometimes|boolean', 'prefs.notifOffers' => 'sometimes|boolean', 'prefs.notifOutbid' => 'sometimes|boolean',
            'prefs.notifPriceDrop' => 'sometimes|boolean', 'prefs.notifScam' => 'sometimes|boolean', 'prefs.notifEmail' => 'sometimes|boolean|declined', 'prefs.verifiedOnly' => 'sometimes|boolean|declined',
        ])->validate();
        $oldPhoto = $user->avatar_path;
        $newPhoto = null;
        try {
            DB::transaction(function () use ($data, $user, $files, &$newPhoto) {
                foreach (['name' => 'name', 'username' => 'username', 'faculty' => 'faculty_code', 'bio' => 'bio', 'phone' => 'phone', 'showPhone' => 'show_phone', 'showFaculty' => 'show_faculty'] as $input => $column) {
                    if (array_key_exists($input, $data)) {
                        $user->{$column} = $data[$input];
                    }
                }
                if (array_key_exists('tikum', $data)) {
                    $user->default_meetup_point_id = MeetupPoint::where('short_name', $data['tikum'])->value('id');
                }
                if (array_key_exists('photo', $data)) {
                    if (! $data['photo']) {
                        $user->avatar_path = null;
                    } elseif ($data['photo'] !== ($user->avatar_path ? $files->url($user->avatar_path) : null)) {
                        $newPhoto = $files->store($data['photo'], 'avatars');
                        $user->avatar_path = $newPhoto;
                    }
                }
                $user->save();
                if (isset($data['prefs'])) {
                    $prefs = [];
                    foreach (['notifMessages' => 'notif_messages', 'notifOffers' => 'notif_offers', 'notifOutbid' => 'notif_outbid', 'notifPriceDrop' => 'notif_price_drop', 'notifScam' => 'notif_scam', 'notifEmail' => 'notif_email', 'verifiedOnly' => 'verified_only'] as $input => $column) {
                        if (array_key_exists($input, $data['prefs'])) {
                            $prefs[$column] = $data['prefs'][$input];
                        }
                    }
                    DB::table('user_preferences')->updateOrInsert(['user_id' => $user->id], [...$prefs, 'updated_at' => now(), 'created_at' => now()]);
                }
            });
        } catch (\Throwable $e) {
            if ($newPhoto) {
                Storage::disk('public')->delete($newPhoto);
            }
            throw $e;
        }
        if ($oldPhoto && $oldPhoto !== $user->avatar_path) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return $this->response($request, ['ok' => true]);
    }

    private function response(Request $request, mixed $result): JsonResponse
    {
        return response()->json(['result' => $result, 'state' => app(MarketplaceData::class)->snapshot($request->user()), 'csrf' => csrf_token()]);
    }
}
