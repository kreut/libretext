<?php

namespace App\Http\Controllers;

use App\H5p;
use Illuminate\Http\Request;

class H5pController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param $curl_exec
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Username for login
        $login_user = getenv('H5P_USERNAME');


        $login_pass = getenv('H5P_PASSWORD');

        /*
        ** Login url address.
        */
        $login_url = "https://h5p.libretexts.org/wp-login.php";

        /*
        ** Which page you want to visit after login.
        ** WordPress redirect their user automatically after login to this page
        ** if you do not assign a visit page,
        ** then the result for this login will return ‘1’.
        ** That means you have logged in successfully.
        ** Visit url is ipmportant to get the content.
        */
        //Start:
      /* 1. add some sort of limit *maybe*
        2. save this stuff to the database
        3. start the search by tags/
      */
        $visit_url = 'https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_contents&offset=0&sortBy=4&sortDir=0';

$cookie_file = "/cookie.txt";

/*
** Set HTTP user agent.
*/
$http_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6";

// Test the call
return $this->curl_get_wp_login( $login_user, $login_pass, $login_url, $visit_url, $http_agent, $cookie_file );

    }

    public function curl_get_wp_login( $login_user, $login_pass, $login_url, $visit_url, $http_agent, $cookie_file ){


// Preparing postdata for wordpress login
        $data = "log=". $login_user ."&pwd=" . $login_pass . "&wp-submit=Log%20In&redirect_to=" . $visit_url;
    $data = ['log' => $login_user,
            'pwd'  => $login_pass,
            'wp-submit' => 'Log%20In',
        'redirect_to' => $visit_url];
        $fields_string = http_build_query($data);
// Intialize cURL
        $ch = curl_init();

       // $data = http_build_query(['page' => 'h5p']);
        //$getUrl = $visit_url."?".$data;







// Url to use
        curl_setopt( $ch, CURLOPT_URL, $login_url );

// Set the cookies for the login in a cookie file.
        curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie_file );

// Set SSL to false
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

// User agent
        curl_setopt( $ch, CURLOPT_USERAGENT, $http_agent );

// Maximum time cURL will wait for get response. in seconds
        curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );

        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );

// Return or echo the execution
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

// Set Http referer.
        curl_setopt( $ch, CURLOPT_REFERER, $login_url );

// Post fields to the login url
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
        curl_setopt( $ch, CURLOPT_POST, 1);

// Save the return in a variable
        $content = curl_exec ($ch);

        /*
        ** if you need to visit another url, you can do it here.
        ** curl_setopt( $ch, CURLOPT_URL, ‘a new url address or a file download url’ );
        ** $content = curl_exec ($ch);
        */

// Close the cURL.
        curl_close( $ch );

// You can echo or return the page data here.
        return $content;
    }
}
