<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\EmailTakenException;
use App\Http\Controllers\Controller;
use App\OAuthProvider;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        config([
            'services.github.redirect' => route('oauth.callback', 'github'),
            'services.libretexts.redirect' => route('oauth.callback', 'libretexts'),
        ]);
    }

    /**
     * @param $provider
     * @return array
     */
    public function redirectToProvider($provider)
    {
        return [
            'url' => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl(),
        ];
    }

    /**
     * @param $provider
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws EmailTakenException
     */
    public function handleProviderCallback($provider)
    {
        $user = Socialite::driver($provider)->stateless()->user();
        $user = $this->findOrCreateUser($provider, $user);

        $this->guard()->setToken(
            $token = $this->guard()->login($user)
        );

        return view('oauth/callback', [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->getPayload()->get('exp') - time(),
        ]);
    }

    /**
     * @param string $provider
     * @param $user
     * @return User|false
     */
    protected function findOrCreateUser(string $provider, $user)
    {

        $oauthProvider = OAuthProvider::where('provider', $provider)
            ->where('provider_user_id', $user->getId())
            ->first();

        if ($oauthProvider) {
            $oauthProvider->update([
                'access_token' => $user->token,
                'refresh_token' => $user->refreshToken,
            ]);

            return $oauthProvider->user;
        }

        return $this->createUser($provider, $user);
    }

    /**
     * @param string $provider
     * @param \Laravel\Socialite\Contracts\User $sUser
     * @return User
     */
    protected function createUser(string $provider, \Laravel\Socialite\Contracts\User $sUser): User
    {
        DB::beginTransaction();
        if (User::where('email', $sUser->getEmail())->exists()) {
            $user = User::where('email', $sUser->getEmail())->first();
            $user->update(['password' => '']);
            $provider_user_id = $user->email;
        } else {
            $user = User::create([
                'first_name' => $sUser->first_name,
                'last_name' => $sUser->last_name,
                'email' => $sUser->getEmail(),
                'role' => 0,
                'time_zone' => '',
                'email_verified_at' => now(),
            ]);
            $provider_user_id = $sUser->getId();
        }
        $user->oauthProviders()->create([
            'provider' => $provider,
            'provider_user_id' => $provider_user_id,
            'access_token' => $sUser->token,
            'refresh_token' => $sUser->refreshToken,
        ]);
        DB::commit();
        return $user;
    }
}
