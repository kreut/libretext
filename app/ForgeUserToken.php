<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ForgeUserToken extends Model
{
    /**
     * @param User $user
     * @return string
     */
    public function create(User $user): string
    {
        $token = Str::random(32);
        DB::table('forge_user_tokens')->insert([
            'user_id' => $user->id,
            'token' => $token,
            'created_at' => now(),
            'updated_at' => now()]);
        return $token;
    }
}
