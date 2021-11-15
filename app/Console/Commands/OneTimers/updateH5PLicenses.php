<?php

namespace App\Console\Commands\OneTimers;

use App\Libretext;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class updateH5PLicenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:H5PLicenses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds the h5p licenses';

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
    public function handle(Question $question)
    {
        /**I've set up an API endpoint at /api/h5p/[id] that will output the following fields for any h5p id passed as a parameter:
        id: the h5p id
        title_1: the title of the h5p
        title: the h5p type (e.g. interactive video, presentation etc.)
        uid: the name of the author who created the node (always populated with data - for the imported h5p elements, this may still say "LibreStudio Admin")
        authors: an array of author objects with name and 'role' added as metadata to an h5p element (typically blank unless a license is added to the h5p element)
        license: the license attributed to the h5p
        license_extras: additional license info if added to h5p metadata
        license_version: sometimes added to h5p metadata
        body: the description of the h5p element, if added by author
        created: when the h5p element was created
        changed: when the h5p element was last updated
        field_subject: the subject that is h5p is added into
        field_tags: any tags added to the h5p element
        view_node: the url to the h5p page
        view_user: the url to the author's profile
        Examples:
        H5P with license and author: https://studio.libretexts.org/api/h5p/2180
        H5P without license and with drupal author/user: https://studio.libretexts.org/api/h5p/698

         * **/



        /**
         * TODO: Private pages
         * what if there isn't a license version?
         * all testing for the anonymous stuff
         * **/






        try {

            $output = "Updating h5p\r\n";
            //h5p
            $questions = $question->where('technology', 'h5p')
                ->where('author',null)
                ->get();
            $domd = new \DOMDocument();
            $libretext = new Libretext();
            foreach ($questions as $key=>$h5p_question) {
                $count = count( $questions) - $key ."\r\n";
                $output .= $count;
                echo $count;
                $info = $question->getAuthorAndLicense($domd,
                    $libretext,
                    $h5p_question->technology_iframe);
                $h5p_question->author = $info['author'];
                $h5p_question->license = $info['license'];
                $h5p_question->license_version = $info['license_version'];
                $h5p_question->save();
            }
            Storage::disk('s3')->put("updateLicenses.txt", $output, ['StorageClass' => 'STANDARD_IA']);
        } catch (Exception $e){
            echo $e->getmessage();
        }
        return 0;
    }
}
