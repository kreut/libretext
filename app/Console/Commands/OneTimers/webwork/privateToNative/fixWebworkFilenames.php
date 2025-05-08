<?php

namespace App\Console\Commands\OneTimers\webwork\privateToNative;

use Aws\Pricing\PricingClient;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class fixWebworkFilenames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:webworkFilenames';

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
        $sshKey = '~/.ssh/id_rsa';
        $userAtHost = 'kreut@webwork.libretexts.org';
        $baseDir = '/opt/webwork/private/ww_files';

        // Retrieve all question IDs
        $questionIds = DB::table('opls')->pluck('question_id')->toArray();

        // Prepare the list of question IDs for the remote script
        $idsList = implode(' ', array_map('escapeshellarg', $questionIds));

        // Construct the remote shell script
        $remoteScript = <<<EOT
count=0
for id in $idsList; do
    dir="$baseDir/\$id"
    if [ -d "\$dir" ]; then
        if [ ! -e "\$dir/code.pg" ]; then
            pg_files=( "\$dir"/*.pg )
            if [ -e "\${pg_files[0]}" ]; then
                mv "\${pg_files[0]}" "\$dir/code.pg"
                ((count++))
            fi
        fi
    fi
done
echo "Renamed \$count file(s)."
EOT;

        // Escape the remote script for SSH
        $escapedScript = escapeshellarg($remoteScript);

        // Construct the SSH command
        $sshCommand = "ssh -i {$sshKey} {$userAtHost} 'bash -s' <<< $escapedScript";

        // Execute the SSH command
        exec($sshCommand, $output, $returnVar);

        // Display the results
        if ($returnVar === 0) {
            foreach ($output as $line) {
                $this->info($line);
            }
        } else {
            $this->error("SSH command failed.");
        }
    }
}
