<?php

namespace App;

use DOMDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\Handler;
use \Exception;

class Question extends Model
{

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        ini_set('memory_limit', '2G');

    }

    /**
     * @param DOMDocument $domd
     * @param Libretext $libretext
     * @param string $technology_iframe
     * @return array
     * @throws Exception
     */
    public function getAuthorAndLicense(DOMDocument $domd,
                                        Libretext   $libretext,
                                        string      $technology_iframe): array
    {
        $technology = $libretext->getTechnologyFromBody($technology_iframe);
        $author = null;
        $license = null;
        $license_version = null;

        if ($technology === 'h5p') {
            $domd->loadHTML($technology_iframe);
            $iFrame = $domd->getElementsByTagName('iframe')->item(0);
            $src = $iFrame->getAttribute('src');
            $h5p_id = str_replace(['https://studio.libretexts.org/h5p/', '/embed'], ['', ''], $src);
            $endpoint = "https://studio.libretexts.org/api/h5p/$h5p_id";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $output = curl_exec($ch);
            curl_close($ch);
            if ($info = json_decode($output, 1)) {
                $info = $info[0];
                $author = json_decode(str_replace(['&quot;', '&amp;amp;'], ['"', 'and'], $info['authors']));
                $author = !$author ? $info['uid'] : ($author[0]->name ?? '');
                $license = $this->_mapLicenseTextToValue($info['license']);
                $license_version = $license ? $info['license_version'] : null;
            }
        } else {
            //TODO
        }
        return compact('author', 'license', 'license_version');
    }

    /**
     * @param string $license_text
     * @return string|null
     * @throws Exception
     */
    private function _mapLicenseTextToValue(string $license_text): ?string
    {
        try {
            $licenses = ['Public Domain' => 'publicdomain',
                'PD' => 'publicdomain',
                'U' => 'publicdomain',
                'CC BY' => 'ccby',
                'CC BY-NC' => 'ccbync',
                'CC BY-ND' => 'ccbynd',
                'CC BY-NC-ND' => 'ccbyncnd',
                'CC BY-NC-SA' => 'ccbyncsa',
                'GNU GPL' => 'gnu',
                'All Rights Reserved' => 'arr',
                'GNU FDL' => 'gnufdl'];
            if ($license_text && !isset($licenses[$license_text])) {
                throw new Exception("$license_text does not exist; need to update this in the questions view and in the database.");
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return $license_text;
        }
        return $licenses[$license_text] ?? null;
    }

    public
    function getUrlLinkText($url)
    {
        $matches = [];
        preg_match('/\>(.*)<\/a>/', $url, $matches);
        /*** return the match ***/
        return $matches[1];
    }

    public
    function getCreatedAt($time)
    {
        $matches = [];
        preg_match('/\>(.*)<\/time>/', $time, $matches);
        /*** return the match ***/
        return $matches[1];
    }

    public
    function tags()
    {
        return $this->belongsToMany('App\Tag')->withTimestamps();
    }


    public
    function getH5PQuestions(int $offset)
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

    public
    function addTag($key, $tag, $question)
    {
        if ($key) {
            $tag = Tag::firstOrCreate(compact('tag'));
            if (!$question->tags->contains($tag->id)) {
                $question->tags()->attach($tag->id);
            }
        }
    }

    public
    function storeWebwork()
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

    public
    function storeH5P()
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

    public
    function store()
    {
        $this->storeWebwork();
        $this->storeH5P();
    }

    function refreshProperties()
    {
        $Libretext = new Libretext(['library' => $this->library]);
        $body_technology_and_tags_info = $this->getBodyTechnologyAndTagsByPageId($Libretext, $this->page_id);
        $dom_elements_from_body = $this->getDomElementsFromBody($body_technology_and_tags_info['body']);
        $this->text_question = $dom_elements_from_body['text_question'];
        $this->a11y_question = $dom_elements_from_body['a11y_question'];
        $this->a11y_question = $dom_elements_from_body['answer_html'];
        $this->solution_html = $dom_elements_from_body['solution_html'];
        $this->hint = $dom_elements_from_body['hint'];
        $this->libretexts_link = $dom_elements_from_body['libretexts_link'];
        $this->notes = $dom_elements_from_body['notes'];
        $this->save();
    }


    /**
     * @param int $page_id
     * @param string $library
     * @param bool $cache_busting
     * @return array
     * @throws Exception
     */
    public
    function getQuestionIdsByPageId(int $page_id, string $library, bool $cache_busting)
    {
        $question = Question::where('page_id', $page_id)
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

        $body_technology_and_tags_info = $this->getBodyTechnologyAndTagsByPageId($Libretext, $page_id);
        $body = $body_technology_and_tags_info['body'];
        $technology_and_tags = $body_technology_and_tags_info['technology_and_tags'];


        $dom_elements_from_body = $this->getDomElementsFromBody($body);
        $dom = $dom_elements_from_body['dom'];
        $body = $dom_elements_from_body['body'];
        $text_question = $dom_elements_from_body['text_question'];
        $a11y_question = $dom_elements_from_body['a11y_question'];
        $answer_html = $dom_elements_from_body['answer_html'];
        $solution_html = $dom_elements_from_body['solution_html'];
        $hint = $dom_elements_from_body['hint'];
        $libretexts_link = $dom_elements_from_body['libretexts_link'];
        $notes = $dom_elements_from_body['notes'];


        try {
            $efs_dir = '/mnt/local/';
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();

            $file = "{$storage_path}{$library}/{$page_id}.php";


            if ($technology = $Libretext->getTechnologyFromBody($body)) {
                $technology_iframe = $Libretext->getTechnologyIframeFromBody($body, $technology);

                $non_technology = str_replace($technology_iframe, '', $body);
                $has_non_technology = trim($non_technology) !== '';

                if ($has_non_technology) {
                    //Frankenstein type problem
                    $non_technology = $Libretext->addExtras($non_technology,
                        ['glMol' => strpos($body, '/Molecules/GLmol/js/GLWrapper.js') !== false,
                            'MathJax' => false]);
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
                Storage::disk('s3')->put("$library/{$page_id}.php", $non_technology);
            }


            if ($cache_busting && file_exists($file)) {
                //remove the local file
                unlink($file);
            }


            $author_and_license_info = $this->getAuthorAndLicense($dom,
                $Libretext,
                $technology_iframe);

            $question = Question::updateOrCreate(
                ['page_id' => $page_id, 'library' => $library],
                ['technology' => $technology,
                    'title' => null, //I'll get the title below
                    'non_technology' => $has_non_technology,
                    'author' => $author_and_license_info['author'],
                    'license' => $author_and_license_info['license'],
                    'license_version' => $author_and_license_info['license_version'],
                    'technology_iframe' => $technology_iframe,
                    'text_question' => $text_question,
                    'a11y_question' => $a11y_question,
                    'answer_html' => $answer_html,
                    'solution_html' => $solution_html,
                    'hint' => $hint,
                    'libretexts_link' => $libretexts_link,
                    'notes' => $notes
                ]);

            $Libretext = new Libretext(['library' => $library]);
            $Libretext->updateTitle($page_id, $question->id);


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

    function getBodyTechnologyAndTagsByPageId(Libretext $Libretext, int $page_id)
    {
        $technology_and_tags['technology'] = false;
        $technology_and_tags['tags'] = [];
        try {
            // id=102629;  //Frankenstein test
            //Public type questions
            $page_info = $Libretext->getPageInfoByPageId($page_id);
            $technology_and_tags = $Libretext->getTechnologyAndTags($page_info);
            $contents = $Libretext->getContentsByPageId($page_id);
            $body = $contents['body'][0];
            if (!$body) {
                throw new exception ("This page has no HTML.");
            }
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
                $contents = $Libretext->getPrivatePage('contents', $page_id);
                $body = $contents->body;
                $body = $body[0];
                if (!$body) {
                    throw new exception ("This page has no HTML.");
                }
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
                echo json_encode(['type' => 'error',
                    'message' => 'We tried getting that private page with page id ' . $page_id . ' but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                    'timeout' => 12000]);
                exit;
            }
        }
        return compact('body', 'technology_and_tags');
    }


    function getDomElementsFromBody($body)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($body);
        libxml_clear_errors();
        $selector = new \DOMXPath($dom);


        $text_question = $this->getInnerHTMLByClass($selector, 'ADAPT-TextQuestion');

        $a11y_question = $this->getInnerHTMLByClass($selector, 'ADAPT-A11yQuestion');
        $answer_html = $this->getInnerHTMLByClass($selector, 'ADAPT-Answer');
        $solution_html = $this->getInnerHTMLByClass($selector, 'ADAPT-Solution');
        $hint = $this->getInnerHTMLByClass($selector, 'ADAPT-Hint');
        $libretexts_link = $this->getInnerHTMLByClass($selector, 'ADAPT-Link');
        $notes = $this->getInnerHTMLByClass($selector, 'ADAPT-Notes');

        $classes_to_remove = ['ADAPT-hidden',
            'ADAPT-TextQuestion',
            'ADAPT-A11yQuestion',
            'ADAPT-Answer',
            'ADAPT-Solution',
            'ADAPT-Hint',
            'ADAPT-Link',
            'ADAPT-Notes'];
        foreach ($classes_to_remove as $class_to_remove) {
            foreach ($selector->query('//div[contains(attribute::class, "' . $class_to_remove . '")]') as $e) {
                $e->parentNode->removeChild($e);
            }

        }

        // $body = $doc->saveHTML($doc->documentElement);
        $rootnode = $dom->getELementsByTagName('body')->item(0);
        $body = trim($this->DOMinnerHTML($rootnode));

        return compact('dom',
            'body',
            'text_question',
            'a11y_question',
            'answer_html',
            'solution_html',
            'hint',
            'libretexts_link',
            'notes');

    }

    function getInnerHTMLByClass($selector, $class)
    {
        $innerHTML = null;
        $nodes = $selector->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]")->item(0);

        if (!$nodes) {
            return null;
        }
        $tmp_dom = new DOMDocument();
        foreach ($nodes->childNodes as $node) {
            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
        }
        $innerHTML .= trim($tmp_dom->saveHTML());
        return $innerHTML;
    }

    function DOMinnerHTML(\DOMNode $element)
    {
        $innerHTML = "";
        $children = $element->childNodes;

        foreach ($children as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return $innerHTML;
    }

    /**
     * @param $library_text_page_id
     * @param Libretext $libretext
     * @param Request $request
     * @return array
     * @throws Exception
     */
    function getQuestionToAddByPageId(Request   $request,
                                      Libretext $libretext): array
    {


        $libraries = $libretext->libraries();
        $library_text_page_id = $request->direct_import;
        $library_texts = [];
        $response['type'] = 'error';
        foreach ($libraries as $library_text => $library) {
            $library_texts[] = strtolower($library_text);
        }

        $default_import_library = $request->cookie('default_import_library');
        $library_text_exists = strpos($library_text_page_id, '-') !== false;
        if (!$library_text_exists && !$default_import_library) {
            $response['message'] = "$library_text_page_id should be of the form {library}-{page id}.";
            return $response;
        }

        if ($default_import_library && !$library_text_exists) {
            $library = $default_import_library;
            $page_id = trim($library_text_page_id);
            $library_text = strtolower($this->getLibraryTextFromLibrary($libraries, $library));
        } else {
            [$library_text, $page_id] = explode('-', $library_text_page_id);
            $library_text = strtolower($this->getLibraryTextFromLibrary($libraries, $library_text));
            if (!in_array($library_text, $library_texts)) {
                $response['message'] = "$library_text is not a valid library.";
                return $response;
            }

            $library = $this->getLibraryFromLibraryText($libraries, $library_text);


        }
        if (!(is_numeric($page_id) && is_int(0 + $page_id) && 0 + $page_id > 0)) {
            $response['message'] = "$page_id should be a positive integer.";
            return $response;
        }

        $question_id = $this->getQuestionIdsByPageId($page_id, $library, false)[0];//returned as an array
        $response['question_id'] = $question_id;
        $response['direct_import_id'] = "$library_text-$page_id";
        $response['type'] = 'success';

        return $response;
    }

    public function getQuestionToAddByAdaptId(Request $request)
    {
        $adapt_id = $request->direct_import;
        $response['type'] = 'error';
        $assignment_question_arr = explode('-', $adapt_id);
        if (count($assignment_question_arr) !== 2) {
            $response['message'] = "$adapt_id should be of the form {assignment_id}-{question_id}.";
            return $response;
        }
        $assignment_id = $assignment_question_arr[0];
        $question_id = $assignment_question_arr[1];
        $assignment_question = DB::table('assignment_question')
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->first();
        if (!$assignment_question) {
            $response['message'] = "The assignment question with Adapt ID $adapt_id does not exist.";
            return $response;
        }
        $response['question_id'] = $question_id;
        $response['assignment_question'] = $assignment_question;
        $response['direct_import_id'] = $adapt_id;
        $response['type'] = 'success';
        return $response;
    }


    /**
     * @param $libraries
     * @param $library
     * @return int|string
     */
    public function getLibraryTextFromLibrary($libraries, $library)
    {
        $response = $library;
        foreach ($libraries as $library_text => $value) {
            if ($value === trim($library)) {
                $response = $library_text;
            }
        }
        return $response;
    }


    /**
     * @param $libraries
     * @param $library_text
     * @return false|mixed|string
     */
    public function getLibraryFromLibraryText($libraries, $library_text)
    {
        $response = false;
        foreach ($libraries as $key => $library) {
            //works for the name or abbreviations
            if (strtolower($key) === $library_text || $library === $library_text) {
                $response = $library;
            }
        }
        return $response;
    }
}

