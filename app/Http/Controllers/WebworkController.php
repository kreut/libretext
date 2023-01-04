<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Webwork;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WebworkController extends Controller
{

    public function list(Webwork $webwork)
    {
        //for testing
        if (app()->environment() !== 'local') {
            dd('no access');
        }

        $path = Helper::getWebworkCodePath();
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://wwlibrary.libretexts.org/render-api/cat?basePath={$path}100152");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $headers = array();
        $headers[] = 'Authorization: Bearer ' . config('myconfig.webwork_token');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }

        curl_close($ch);

       dd(json_decode($response,1));


    }

    public function delete(Webwork $webwork)
    {
        //for testing
        if (app()->environment() !== 'local') {
            dd('no access');
        }


        try {

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://wwlibrary.libretexts.org/render-api/remove');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

            $headers = array();
            $headers[] = 'Authorization: Bearer ' . config('myconfig.webwork_token');
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $path = Helper::getWebworkCodePath();
            $data = array(
                'removeFilePath' => "{$path}152930"
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }

            curl_close($ch);
//'Path deleted'
            //Path does not exist
            //Directory is not empty
            echo $response;

        } catch (Exception $e) {
            echo $e->getMessage();
        }


    }

    public function cloneDir(Webwork $webwork)
    {
        //for testing
        if (app()->environment() !== 'local') {
            dd('no access');
        }

        $path = Helper::getWebworkCodePath();

        try {

            $curl = curl_init();
            $from_dir = 152929;
            $to_dir = 152931;
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://wwlibrary.libretexts.org/render-api/clone?sourceFilePath=$path$from_dir&targetFilePath=$path$to_dir/",
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

            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;
            dd($response);
        } catch (Exception $e) {
            echo $e->getMessage();
        }


    }

    public function convertDefFileToMassWebworkUploadCSV(Request $request)
    {
        $contents = file($request->file);
        foreach ($contents as $line) {
            if (str_starts_with($line, 'source_file')) {
                $pg_file = str_replace('source_file = ', '', $line);
                echo $pg_file;
            }
        }
        exit;
        $contents = file_get_contents('/Users/franciscaparedes/Downloads/setCobleBigIdeasCosmology5.def');
        dd($contents);

    }
}
