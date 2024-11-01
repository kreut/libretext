<?php

namespace App\Console\Commands\OneTimers;


use App\DiscussionComment;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class restoreFromDigitalOcean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restore:fromDigitalOcean';

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
        //$assignment_id = 100514;
// Get environment variables
        $accessKeyId = env('DIGITAL_OCEAN_AWS_ACCESS_KEY_ID');
        $secretAccessKey = env('DIGITAL_OCEAN_AWS_SECRET_ACCESS_KEY');
        $bucket = env('DIGITAL_OCEAN_AWS_BUCKET');
        $spaceEndpoint = 'https://sfo3.digitaloceanspaces.com'; // Replace with your actual space endpoint

// Initialize the S3 client for DigitalOcean Spaces
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1', // Default region, this can be any valid region name, as DigitalOcean ignores it
            'endpoint' => $spaceEndpoint,
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
        ]);

        $comments = DiscussionComment::whereIn('discussion_id', function ($query) {
            $query->select('id')
                ->from('discussions')
                ->where('assignment_id', 100514);
        })->get();

// Directory and file key
        $directory = 'uploads/question-media';

        foreach ($comments as $comment) {

            try {

                $s3Key = $comment->file;
// Retrieve the file

                $result = $s3Client->getObject([
                    'Bucket' => $bucket,
                    'Key' => $directory . '/' . $s3Key,
                ]);

                // Save the original file locally
                $filePath = '/Users/franciscaparedes/Downloads/discussion-comments/' . $s3Key; // Set your local save path here
                file_put_contents($filePath, $result['Body']);

                echo "Downloaded: $filePath\n";

                // Check for the .vtt file
                $vttKey = $directory . '/' . pathinfo($s3Key, PATHINFO_FILENAME) . '.vtt'; // Construct the .vtt filename

                // Check if the .vtt file exists
                $vttResult = $s3Client->headObject([
                    'Bucket' => $bucket,
                    'Key' => $vttKey,
                ]);

                // If it exists, download the .vtt file
                if ($vttResult) {
                    $vttFilePath = '/Users/franciscaparedes/Downloads/discussion-comments/' . pathinfo($vttKey, PATHINFO_BASENAME);
                    $vttContent = $s3Client->getObject([
                        'Bucket' => $bucket,
                        'Key' => $vttKey,
                    ]);
                    file_put_contents($vttFilePath, $vttContent['Body']);
                    echo "Downloaded VTT: $vttFilePath\n";
                }
            } catch (AwsException $e) {
                // Output error message if fails to download the original file
                echo "Error downloading file: $s3Key - " . $e->getMessage() . "\n";
            }
        }
        return 0;
    }
}

