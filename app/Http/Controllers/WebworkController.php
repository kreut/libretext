<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebworkController extends Controller
{
    public function convertDefFileToMassWebworkUploadCSV(Request $request)
{
    $contents = file($request->file);
    foreach($contents as $line) {
        if (str_starts_with($line, 'source_file') ) {
            $pg_file = str_replace('source_file = ','', $line);
            echo $pg_file;
        }
    }
   exit;
    $contents = file_get_contents('/Users/franciscaparedes/Downloads/setCobleBigIdeasCosmology5.def');
    dd($contents);

}
}
