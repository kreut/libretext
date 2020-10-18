<?php


namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use App\Cutup;
use Illuminate\Support\Facades\Log;

trait S3
{
    public function getTemporaryUrl($assignment_id, $file)
    {
        return \Storage::disk('s3')->temporaryUrl("assignments/$assignment_id/$file", now()->addMinutes(120));
    }

    public function fileValidator()
    {
        return ['required', 'mimes:pdf,txt,png,jpeg,jpg', 'max:500000'];//update in UploadFiles.js
    }

    function cutUpPdf(string $filename, string $directory, Cutup $cutup, int $assignment_id, int $user_id)
    {
        $pdf = new Fpdi();
        $storage_path  = Storage::disk('local')->getAdapter()->getPathPrefix();
        $pageCount = $pdf->setSourceFile(   $storage_path .$filename);
        $file = pathinfo($filename, PATHINFO_FILENAME);
        // Split each page into a new PDF
        for ($i = 1; $i <= $pageCount; $i++) {
            $newPdf = new Fpdi();
            $newPdf->addPage();

            $newPdf->setSourceFile(   $storage_path . $filename);
            $newPdf->useTemplate($newPdf->importPage($i));

            $full_path_to_cutup = sprintf('%s/%s_%s.pdf',    $storage_path . $directory, $file, $i);

            $path_to_cutup_without_storage_prefix = str_replace(   $storage_path, '',   $full_path_to_cutup);

            $cutup->create([
                'assignment_id' => $assignment_id,
                'user_id' => $user_id,
                'file' => "{$file}_{$i}.pdf"
            ]);
            $newPdf->output(  $full_path_to_cutup , 'F');

            $cutupFilename =  str_replace(storage_path(), '',  $path_to_cutup_without_storage_prefix );
            $cutupContents = Storage::disk('local')->get( $path_to_cutup_without_storage_prefix );
            Storage::disk('s3')->put($cutupFilename, $cutupContents, ['StorageClass' => 'STANDARD_IA']);
        }
    }

}
