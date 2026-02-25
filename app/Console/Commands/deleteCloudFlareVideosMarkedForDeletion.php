<?php

namespace App\Console\Commands;

use App\CloudflareStreamVideo;
use App\Services\CloudflareStream;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\Facades\DB;
use Throwable;

class deleteCloudFlareVideosMarkedForDeletion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:cloudFlareVideosMarkedForDeletion';

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
     * @throws Throwable
     */
    public function handle()
    {
        try {
            $cloudFlareStream = new CloudflareStream();
            $videos_marked_for_deletion =   CloudflareStreamVideo::where('marked_for_deletion',1)->get();
            foreach ($videos_marked_for_deletion as $video_marked_for_deletion){
                $response = $cloudFlareStream->deleteVideo($video_marked_for_deletion->cloudflare_uid);
              if ($response['type'] === 'error'){
                  $video_marked_for_deletion->error_message = "Not deleted: " .$response['message']['errors'][0]['message'];
                  $video_marked_for_deletion->save();
              }
            }
            echo count($videos_marked_for_deletion) . " deleted";
            return 0;
        } catch (Exception $e){
            $h = new Handler(app());
            $h->report($e);
            echo $e->getMessage();
            return 1;
        }
    }
}
