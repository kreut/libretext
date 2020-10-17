<?php


namespace App\Traits;

use setasign\Fpdi\Fpdi;

trait S3
{
    public function getTemporaryUrl($assignment_id, $file)
    {
        return \Storage::disk('s3')->temporaryUrl("assignments/$assignment_id/$file", now()->addMinutes(120));
    }

    public function fileValidator() {
        return ['required', 'mimes:pdf,txt,png,jpeg,jpg', 'max:500000'];//update in UploadFiles.js
    }
    function cutUpPdf(string $filename, string $directory)
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filename);
        $file = pathinfo($filename, PATHINFO_FILENAME);

        // Split each page into a new PDF
        for ($i = 1; $i <= $pageCount; $i++) {
            $newPdf = new Fpdi();
            $newPdf->addPage();
            $newPdf->setSourceFile($filename);
            $newPdf->useTemplate($newPdf->importPage($i));

            $newFilename = sprintf('%s/%s_%s.pdf', $directory, $file, $i);
            $newPdf->output($newFilename, 'F');
        }
    }

}
