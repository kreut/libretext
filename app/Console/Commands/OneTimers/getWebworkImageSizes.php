<?php

namespace App\Console\Commands\OneTimers;

use App\Webwork;
use App\WebworkAttachment;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class getWebworkImageSizes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:webworkImageSizes';

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
            $webwork_attachments = WebworkAttachment::all();
            DB::beginTransaction();
            foreach ($webwork_attachments as $webwork_attachment) {
                $path = "/Users/franciscaparedes/Downloads/webwork/$webwork_attachment->question_id/$webwork_attachment->filename";
                $image_info = getimagesize($path);
                $width = $image_info[0];
                $height = $image_info[1];
                DB::table('webwork_image_sizes')->insert([
                    'webwork_attachment_id' => $webwork_attachment->id,
                    'width' => $width,
                    'height' => $height]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
