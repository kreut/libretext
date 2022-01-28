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
     * @param \Laravel\Socialite\Contracts\User $sUser
     * @return \App\User|false
     */
    protected function findOrCreateUser($provider, $user)
    {

        $oauthProvider = OAuthProvider::where('provider', $provider)
            ->where('provider_user_id', $user->getId())
            ->first();

        if (User::where('email', $user->getEmail())->exists()) {
            $local_user = User::where('email', $user->getEmail())->first();
            DB::beginTransaction();
            $local_user->update(['password' => '']);
            if (!$oauthProvider) {
                OAuthProvider::create([
                    'provider' => $provider,
                    'provider_user_id' => $local_user->email,
                    'user_id' => $local_user->id
                ]);
            }
            DB::commit();
        }



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
     * @return \App\User
     */
    protected function createUser($provider, $sUser)
    {

        $user = User::create([
            'first_name' => $sUser->first_name,
            'last_name' => $sUser->last_name,
            'email' => $sUser->getEmail(),
            'role' => 0,
            'time_zone' => '',
            'email_verified_at' => now(),
        ]);

        $user->oauthProviders()->create([
            'provider' => $provider,
            'provider_user_id' => $sUser->getId(),
            'access_token' => $sUser->token,
            'refresh_token' => $sUser->refreshToken,
        ]);

        return $user;
    }
}
