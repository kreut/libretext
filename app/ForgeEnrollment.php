<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ForgeEnrollment extends Model
{

    private $secret;
    protected $guarded=  [];

    public function __construct()
    {
        $this->secret = DB::table('key_secrets')
            ->where('key', 'forge')
            ->first()
            ->secret;
    }

    public function store(array $data)
    {
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $this->secret",
        ])->post(config('services.antecedent.url') . '/api/adapt/enroll', $data)
            ->body();
    }

}
