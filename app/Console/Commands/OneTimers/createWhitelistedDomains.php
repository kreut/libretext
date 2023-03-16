<?php

namespace App\Console\Commands\OneTimers;

use App\WhitelistedDomain;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createWhitelistedDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:whitelistedDomains';

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
    public function handle(WhitelistedDomain $whitelistedDomain)
    {
        try {
            DB::beginTransaction();
            $courses = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('users.role', 2)
                ->select('courses.id', 'email')
                ->get();
            foreach ($courses as $course) {
                $whitelistedDomain = new WhitelistedDomain();
                $whitelistedDomain->course_id = $course->id;
                $whitelisted_domain = $whitelistedDomain->getWhitelistedDomainFromEmail($course->email);
                $whitelistedDomain->whitelisted_domain = $whitelisted_domain;
                $whitelistedDomain->save();
                echo $whitelisted_domain . "\r\n";
            }
            DB::commit();
        } catch (Exception $e) {
            echo $e->getMessage();
            DB::rollback();
        }
        return 0;
    }
}
