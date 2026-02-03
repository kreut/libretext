<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
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

    /**
     * @param array $data
     * @param int $role
     * @return Response
     * @throws Exception
     */
    public function store(array $data, int $role): Response
    {
        switch($role){
            case(3):
                $endpoint = '/api/adapt/enroll';
                break;
            case(4):
                $endpoint = '/api/adapt/assistant';
                break;
            default:
                throw new Exception("$role is not a valid role for Forge");

        }
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $this->secret",
        ])->post(config('services.antecedent.url') . $endpoint, $data);
    }

}
