<?php

namespace App\Console\Commands\LMS;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class initLocalCanvas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:localCanvas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            DB::table('users')->where('email', 'kreut@hotmail.com')
                ->update(['first_name' => 'instructor',
                    'role' => 2]);

            $lti_registration_id = DB::table('lti_registrations')->insertGetId([
                'campus_id' => 'local-canvas',
                'admin_name' => 'Eric Kean Local',
                'admin_email' => 'kean@local-canvas.com',
                'iss' => 'https://canvas.instructure.com',
                'api_key' => '10000000000005',
                'api_secret' => '3ZDFMy4JCLhcmX2cr3mU4ChZwZQXLDQLr9UKZtaR3vJCUWChr7JxTNfGNvnCukzD',
                'auth_login_url' => 'http://canvas.docker/api/lti/authorize_redirect',
                'auth_token_url' => 'http://canvas.docker/login/oauth2/token',
                'auth_server' => 'http://canvas.docker',
                'client_id' => '10000000000004',
                'key_set_url' => 'https://canvas.instructure.com/api/lti/security/jwks',
                'kid' => '1',
                'lti_key_id' => 1,
                'active' => 1,
                'created_at' => '2025-03-24 19:30:36',
                'updated_at' => '2025-03-24 19:30:36',
            ]);
            DB::table('lti_schools')->insert(['lti_registration_id' => $lti_registration_id,
                'school_id' => 4217]);
            ///also add the school ID 4217 to that registration which is WWU

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return 0;
    }
}
