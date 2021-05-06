<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\Handler;
use \Exception;

class Question extends Model
{

    protected $fillable = ['page_id', 'technology_iframe', 'non_technology', 'technology', 'library'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        ini_set('memory_limit', '2G');

    }

    public function getUrlLinkText($url)
    {
        $matches = [];
        preg_match('/\>(.*)<\/a>/', $url, $matches);
        /*** return the match ***/
        return $matches[1];
    }

    public function getCreatedAt($time)
    {
        $matches = [];
        preg_match('/\>(.*)<\/time>/', $time, $matches);
        /*** return the match ***/
        return $matches[1];
    }

    public function tags()
    {
        return $this->belongsToMany('App\Tag')->withTimestamps();
    }


    public function getH5PQuestions(int $offset)
    {
        /** [
         * "<a href=\"https://h5p.libretexts.org/wp-admin/admin.php?page=h5p&task=show&id=1464\">Cap.5: Videos y actividad.</a>",
         * { "id": "H5P.Column", "title": "Column" },
         * { "id": "14", "title": "Anaid Stere-Lugo" },
         * [ { "id": "686", "title": "la ropa" }, { "id": "689", "title": "de compras" }, { "id": "691", "title": "adjetivos." }, { "id": "697", "title": "Video" } ],
         * "<time datetime=\"2020-07-11T02:44:46+00:00\" title=\"2 days ago\">2020/07/11</time>",
         * "1464",
         * "<a href=\"https://h5p.libretexts.org/wp-admin/admin.php?page=h5p&task=results&id=1464\">Results</a>",
         * "<a href=\"https://h5p.libretexts.org/wp-admin/admin.php?page=h5p_new&id=1464\">Edit</a>" ]
         */

        $login_user = getenv('H5P_USERNAME');
        $login_pass = getenv('H5P_PASSWORD');
        $login_url = 'https://h5p.libretexts.org/wp-login.php';
        $visit_url = "https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_contents&limit=100&offset=$offset&sortBy=4&sortDir=0";
        $cookie_file = '/cookie.txt';
        $http_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6";

        $fields_string = http_build_query(['log' => $login_user,
            'pwd' => $login_pass,
            'wp-submit' => 'Log%20In',
            'redirect_to' => $visit_url]);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $login_url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $http_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $login_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_POST, 1);
        $questions = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);

        if (isset($error_msg)) {
            echo $error_msg;
            exit;
        }
        return json_decode($questions);
    }

    public function addTag($key, $tag, $question)
    {
        if ($key) {
            $tag = Tag::firstOrCreate(compact('tag'));
            if (!$question->tags->contains($tag->id)) {
                $question->tags()->attach($tag->id);
            }
        }
    }

    public function storeWebwork()
    {
        try {
            $webwork = DB::connection('webwork');
            echo "Connected to webwork\r\n";
            $questions = $webwork->table('OPL_path')
                ->join('OPL_pgfile', 'OPL_path.path_id', '=', 'OPL_pgfile.path_id')
                ->leftJoin('OPL_pgfile_keyword', 'OPL_pgfile_keyword.pgfile_id', '=', 'OPL_pgfile.pgfile_id')
                ->leftJoin('OPL_keyword', 'OPL_pgfile_keyword.keyword_id', '=', 'OPL_keyword.keyword_id')
                ->leftJoin('OPL_author', 'OPL_author.author_id', '=', 'OPL_pgfile.author_id')
                ->leftJoin('OPL_section', 'OPL_pgfile.DBsection_id', '=', 'OPL_section.section_id')
                ->leftJoin('OPL_chapter', 'OPL_section.chapter_id', '=', 'OPL_chapter.chapter_id')
                ->leftJoin('OPL_textbook', 'OPL_chapter.chapter_id', '=', 'OPL_textbook.textbook_id')
                ->select('keyword',
                    'level',
                    'path',
                    DB::raw("CONCAT(`firstname`,' ',`lastname`) AS author"),
                    DB::raw("CONCAT(`path`,'/',`filename`) AS page_id"),
                    DB::raw("CONCAT(`title`,' - ',OPL_chapter.name,' - ',OPL_section.name,': ',`firstname`,' ',`lastname`) AS textbook_source"))
                ->get();
            DB::disconnect('webwork');
            echo count($questions) . " questions\r\n";
            echo "Disconnected from webwork\r\n";
            echo "Selected questions\r\n";
            foreach ($questions as $value) {
                $data = ['author' => $value->author,
                    'page_id' => $value->page_id,
                    'technology' => 'webwork'];
                $question = Question::firstOrCreate($data);
                $this->addTag($value->keyword, mb_strtolower($value->keyword), $question);
                $this->addTag($value->level, "Difficulty Level = {$value->level}", $question);
                $this->addTag($value->textbook_source, $value->textbook_source, $question);
                $this->addTag($value->path, $value->path, $question);
                $question->refresh();
                echo $value->page_id . "\r\n";
            }
            echo "Inserted questions\r\n";
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    public function storeH5P()
    {
        $offset = 0;
        $questions = $this->getH5PQuestions($offset);
        while ($questions->rows) {
            echo $offset;
            foreach ($questions->rows as $question) {
                $title = $this->getUrlLinkText($question[0]);
                $author = $question[2]->title;
                $tag_info = $question[3];
                //$created_at = $this->getCreatedAt($question[4]);  Do I need this?
                $page_id = $question[5];
                $data = compact('title', 'author', 'page_id') + ['technology' => 'h5p'];
                $question = $this->firstOrCreate($data);
                if ($tag_info) {
                    foreach ($tag_info as $value) {
                        $tag_id = Tag::firstOrCreate(['tag' => mb_strtolower($value->title)]);
                        if (!$question->tags->contains($tag_id)) {
                            $question->tags()->attach($tag_id);
                        }
                    }

                    //store question info in the question table
                    //title, author, id, created at, question_and_tag_pivot_id
                    //store the tags in the tag table if they don't already exist

                }
            }
            $offset += 100;
            $questions = $this->getH5PQuestions($offset);
        }
        echo "\r\n";

    }

    public function store()
    {
        $this->storeWebwork();
        $this->storeH5P();
    }

    /**
     * @param int $page_id
     * @param string $library
     * @param bool $cache_busting
     * @return array
     * @throws Exception
     */
    public function getQuestionIdsByPageId(int $page_id, string $library, bool $cache_busting)
    {
        $question = Question::where('page_id',$page_id)
                            ->where('library', $library)
                            ->first();

        if ($question && !$cache_busting) {
            return [$question->id]; ///just part of the search....
        }

        //either it's not a question or it is a question and we want to bust the cache

        //maybe it was just created and doesn't exist yet...
        ///get it from query
        ///enter it into the database if I can get it
        ///
        /// getPageInfoByPageId(int $page_id)
        $Libretext = new Libretext(['library' => $library]);
        $technology_and_tags['technology'] = false;
        $technology_and_tags['tags'] = [];

        try {
            // id=102629;  //Frankenstein test
            //Public type questions
            $page_info = $Libretext->getPageInfoByPageId($page_id);
            $technology_and_tags = $Libretext->getTechnologyAndTags($page_info);
            $contents = $Libretext->getContentsByPageId($page_id);
            $body = $contents['body'][0];
        } catch (Exception $e) {

            if (strpos($e->getMessage(), '403 Forbidden') === false) {
                //some other error besides forbidden
                echo json_encode(['type' => 'error',
                    'message' => 'We tried getting the public page with page id ' . $page_id . ' but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                    'timeout' => 12000]);
                exit;
            }

            //private page so try again!
            try {
                $contents = $Libretext->getBodyFromPrivatePage($page_id);
                $body = $contents['body'][0];
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
                echo json_encode(['type' => 'error',
                    'message' => 'We tried getting that private page with page id ' . $page_id . ' but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                    'timeout' => 12000]);
                exit;
            }
        }

        try {

            if ($technology = $Libretext->getTechnologyFromBody($body)) {
                $technology_iframe = $Libretext->getTechnologyIframeFromBody($body, $technology);

                $non_technology = str_replace($technology_iframe, '', $body);
                $has_non_technology = trim($non_technology) !== '';

                if ($has_non_technology) {
                    //Frankenstein type problem
                    $non_technology = $Libretext->addExtras($non_technology,
                        ['glMol' => strpos($body, '/Molecules/GLmol/js/GLWrapper.js') !== false,
                            'MathJax' => false]);
                    Storage::disk('local')->put("$library/{$page_id}.php", $non_technology);
                    Storage::disk('s3')->put("$library/{$page_id}.php", $non_technology);
                }
            } else {
                $technology_iframe = '';
                $has_non_technology = true;
                $non_technology = $Libretext->addExtras($body,
                    ['glMol' => false,
                        'MathJax' => true
                    ]);
                $technology = 'text';
                Storage::disk('local')->put("$library/{$page_id}.php", $non_technology);
                Storage::disk('s3')->put("$library/{$page_id}.php", $non_technology);
            }

            $question = Question::updateOrCreate(
                ['page_id' => $page_id, 'library' => $library],
                ['technology' => $technology,
                    'title' => null, //I'll get the title when the question is re-loaded
                    'non_technology' => $has_non_technology,
                    'technology_iframe' => $technology_iframe
                ]);

            if ($technology_and_tags['tags']) {
                $Libretext->addTagsToQuestion($question, $technology_and_tags['tags']);
            }
            return [$question->id];
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            echo json_encode(['type' => 'error',
                'message' => 'We tried saving that page with page id ' . $page_id . ' but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                'timeout' => 12000]);
            exit;
        }


    }

}
