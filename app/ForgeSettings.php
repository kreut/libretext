<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForgeSettings extends Model
{
    private $secret;

    public function __construct()
    {
        $this->secret = DB::table('key_secrets')
            ->where('key', 'forge')
            ->first()
            ->secret;
    }

    /**
     * @param array $data
     * @return Response
     */
    public function store(array $data): Response
    {

        $forge_question_id = $data['forge_question_id'];
        $secret = $this->secret;

        $url = config('services.antecedent.url') . "/api/adapt/assignment/$forge_question_id/details";
        $jsonData = json_encode($data);
        $curl = "curl -X POST '{$url}' \\\n"
          . "  -H 'Content-Type: application/json' \\\n"
          . "  -H 'Authorization: Bearer {$secret}' \\\n"
          . "  -d '{$jsonData}'";
Log::info($curl);
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer $secret",
        ])->post($url, $data);
    }
}
