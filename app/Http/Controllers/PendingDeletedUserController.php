<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PendingDeletedUserController extends Controller
{

    /**
     * @var mixed|string
     */
    private $bearer_token;

    public function __construct()
    {
        $credentials = DB::table('libreone_credentials')->first();
        $this->bearer_token = $credentials ? $credentials->bearer_token : '';
    }

    /**
     * @param Request $request
     * @return array
     */
    public function store(Request $request): array
    {
        try {
            $response['type'] = 'error';
            $claims = Helper::authorizedLibreOneClaims($request, $this->bearer_token);
            if (!isset($claims['central_identity_id']) || !$claims['central_identity_id']) {
                $response['message'] = 'Missing the central_identity_id.';
                return $response;
            }
            $adapt_user = DB::table('users')->where('central_identity_id', $claims['central_identity_id'])->first();
            if (!$adapt_user) {
                $response['message'] = 'No ADAPT user with the central identity id: ' . $claims['central_identity_id'];
                return $response;
            }
            $pending_deleted_user = DB::table('pending_deleted_users')->where('central_identity_id', $claims['central_identity_id'])->first();
            if ($pending_deleted_user) {
                $response['message'] = 'The user with central identity id ' . $claims['central_identity_id'] . ' is in the pending deleted users table with the status: ' . $pending_deleted_user->status;
                return $response;
            }
            DB::table('pending_deleted_users')->insert([
                'central_identity_id' => $claims['central_identity_id'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()]);
            $response['type'] = 'success';
            $response['message'] = "User {$claims['central_identity_id']} is now pending deletion.";
        } catch (Exception $e) {
            $response['type'] = 'error';
            $response['message'] = $e->getMessage();
        }
        return $response;
    }

}
