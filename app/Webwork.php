<?php

namespace App;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use CURLFile;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Webwork extends Model
{

    /**
     * @throws Exception
     */
    private function _environmentIsNotProductionButFileIsProduction($asset)
    {
        if (app()->environment() !== 'production') {
            if (strpos($asset, app()->environment()) === false) {
                throw new Exception ("Trying to act on an asset that is a production path but you are not in production: $asset.");
            }
        }

    }

    /**
     * @throws Exception
     */
    public function deletePath($removeFilePath)
    {
        $this->_environmentIsNotProductionButFileIsProduction($removeFilePath);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://wwlibrary.libretexts.org/render-api/remove');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $headers = array();
        $headers[] = 'Authorization: Bearer ' . config('myconfig.webwork_token');
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = array(
            'removeFilePath' => $removeFilePath
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            echo 'Error:' . curl_error($curl);
            throw new Exception ("Error deleting $removeFilePath:" . curl_error($curl));
        }

        curl_close($curl);
        if ($response !== 'Path deleted') {
            throw new Exception ("Error deleting $removeFilePath: $response");
        }
    }

    /**
     * @throws Exception
     */
    public function listDir($dir)
    {
        $this->_environmentIsNotProductionButFileIsProduction($dir);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://wwlibrary.libretexts.org/render-api/cat?basePath=$dir");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

        $headers = array();
        $headers[] = 'Authorization: Bearer ' . config('myconfig.webwork_token');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception ("Error listing $dir:" . curl_error($curl));
        }

        return json_decode($response, 1);

    }

    /**
     * @throws Exception
     */
    public function storeQuestion($webwork_code, $webwork_dir)
    {
        $write_file_path = Helper::getWebworkCodePath() . "$webwork_dir/code.pg";
        $problem_source = base64_encode($webwork_code);
        return $this->_doCurl(['writeFilePath' => $write_file_path, 'problemSource' => $problem_source], "https://wwlibrary.libretexts.org/render-api/can");
    }

    /**
     * @throws Exception
     */
    public function cloneDir($sourceDir, $targetDir)
    {
        /**
         * required: `sourceFilePath` and `targetFilePath`
         * the source must exist
         * the target must not exist
         * if the source is a directory
         * the target must end with a /
         * only files directly contained in the source directory will be cloned (no recursion)
         * individual files may also be cloned, but only within the existing directory structure -- no new directories can be created via this process
         * cloning individual files requires the same file extension on source & target
         * **/
        $path = Helper::getWebworkCodePath();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://wwlibrary.libretexts.org/render-api/clone?sourceFilePath=$path$sourceDir&targetFilePath=$path$targetDir/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . config('myconfig.webwork_token')
            ),
        ));
        curl_exec($curl);
        $response = curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200 ? 'clone successful' : "Error cloning webwork from $path$sourceDir to $path$targetDir/";
        if (curl_errno($curl)) {
            $response = curl_error($curl);
        }
        curl_close($curl);
        return $response;
    }

    /**
     * @throws Exception
     */
    public function putLocalAttachmentToLiveServer($filename, $local_path, $webwork_dir)
    {
        if (app()->environment() !== 'local') {
            echo "Can only run this locally.";
            return false;
        }
        $write_file_path = "private/ww_files/$webwork_dir/$filename";

        $post_fields = [
            "path" => $write_file_path,
            "file" => new CURLFile($local_path, mime_content_type($local_path), $filename)
        ];
        return $this->_doCurl($post_fields, "https://wwlibrary.libretexts.org/render-api/upload");
    }


    /**
     * @throws Exception
     */
    public function storeAttachment($filename, $local_path, $webwork_dir)
    {

        $write_file_path = Helper::getWebworkCodePath() . "$webwork_dir/$filename";
        $post_fields = [
            "path" => $write_file_path,
            "file" => new CURLFile($local_path, mime_content_type($local_path), $filename)
        ];
        return $this->_doCurl($post_fields, "https://wwlibrary.libretexts.org/render-api/upload");
    }

    /**
     * @throws Exception
     */
    private function _doCurl($post_fields, $url)
    {
        $webwork_token = config('myconfig.webwork_token');
        if (!$webwork_token) {
            throw new Exception ("No webwork token in the .env file.");

        }
        $headers = [
            "Content-Type:multipart/form-data",
            "Authorization: Bearer " . $webwork_token
        ];
        $curl = curl_init();
        $curl_opts = [
            CURLOPT_FAILONERROR => true,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => $headers
        ];
        curl_setopt_array($curl, $curl_opts);
        curl_exec($curl);
        $response = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_errno($curl)) {
            $response = curl_error($curl);
        }
        curl_close($curl);
        return $response;
    }

    /**
     * @param $question_id
     * @param $question_revision_id
     * @return string
     */
    public function getDir($question_id, $question_revision_id): string
    {
        return $question_revision_id ? "$question_id-$question_revision_id" : $question_id;
    }

    /**
     * @param $value
     * @return bool
     */
    public function algorithmicSolution($value): bool
{
    return $value->webwork_code
        && strpos($value->webwork_code, 'BEGIN_PGML_SOLUTION') !== false
        && !preg_match("/#\s*BEGIN_PGML_SOLUTION/", $value->webwork_code);
}
}
