<?php

namespace App;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Webwork extends Model
{
    /**
     * @throws Exception
     */
    public function storeQuestion($question)
    {
        $dir = Helper::getWebworkCodePath($question);
        $write_file_path = "$dir/code.pg";
        $problem_source = base64_encode($question->webwork_code);
        return $this->_doCurl($write_file_path, $problem_source);
    }

    /**
     * @throws Exception
     */
    public function storeAttachment($question, $filename, $contents)
    {
        $file_info = pathinfo($filename);
        $filename = preg_replace( '/[^a-z0-9]+/', '_', strtolower( $file_info['filename']) ) . "." . 'png';
        $dir = Helper::getWebworkCodePath($question);
        $write_file_path = "$dir/$filename";
        $contents = base64_encode($contents);
        return $this->_doCurl($write_file_path, $contents);
    }

    /**
     * @throws Exception
     */
    private function _doCurl($write_file_path, $problem_source)
    {
        $webwork_token = config('myconfig.webwork_token');
        if (!$webwork_token) {
            throw new Exception ("No webwork token in the .env file.");

        }
        $post_fields = ['writeFilePath' => $write_file_path, 'problemSource' => $problem_source];
        $headers = [
            "Content-Type:multipart/form-data",
            "Authorization: Bearer " . $webwork_token
        ];
        $curl = curl_init();
        $curl_opts = [
            CURLOPT_FAILONERROR => true,
            CURLOPT_URL => "https://wwlibrary.libretexts.org/render-api/can",
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
