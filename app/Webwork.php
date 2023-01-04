<?php

namespace App;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Webwork extends Model
{
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
        $sourceFilePath = Helper::getWebworkCodePath() . $sourceDir;
        $targetFilePath = Helper::getWebworkCodePath() . $targetDir . '/';
        $post_fields = ['sourceFilePath' => $sourceFilePath, 'targetFilePath ' => $targetFilePath];
        return $this->_doCurl($post_fields, "https://wwlibrary.libretexts.org/render-api/clone");
    }

    /**
     * @throws Exception
     */
    public function storeAttachment($filename, $local_path, $webwork_dir)
    {

        $write_file_path = Helper::getWebworkCodePath() . "$webwork_dir/$filename";
        $post_fields = [
            "path" => $write_file_path,
            "file" => new \CURLFile($local_path, mime_content_type($local_path), $filename)
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
}
