<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class Cutup extends Model
{
    protected $guarded = [];


    public function mergeCutUpPdfs($submissionFile, $solution, string $type, int $assignment_id, int $user_id, array $chosen_cutups, string $page_numbers_and_extension)
    {

        $pdf = new FPDI();

// iterate over array of files and merge
        $dir = ($type === 'solution') ? "solutions/$user_id" : "assignments/$assignment_id";
        //

        $storage_path = Storage::disk('local')->getAdapter()->getPathPrefix();
        $files = Cutup::where('assignment_id', $assignment_id)
            ->where('user_id', $user_id)
            ->get();

        $page_numbers = [];

        foreach ($files as $file_info) {
            $filename =$file_info->file;
            $location_of_underscore = strrpos($filename, '_');
            $page_number_and_extension = substr($filename, $location_of_underscore + 1);
            $page_number = str_replace('.pdf', '', $page_number_and_extension);
            $page_numbers[] = $page_number;
            if (in_array($page_number, $chosen_cutups)) {
                $file = $storage_path . $dir . '/' . $filename;
                $pageCount = $pdf->setSourceFile($file);
                for ($i = 0; $i < $pageCount; $i++) {
                    $tpl = $pdf->importPage($i + 1, '/MediaBox');
                    $pdf->addPage();
                    $pdf->useTemplate($tpl);
                }
            }
        }
        if (array_diff($chosen_cutups, $page_numbers)) {
            throw new \Exception("Your cutups should be a comma separated list of pages chosen from your original PDF.");
        }

// output the pdf as a file (http://www.fpdf.org/en/doc/output.htm)
        $model = ($type === 'solution') ? $solution : $submissionFile;
        $full_pdf = $model->where('assignment_id', $assignment_id)
            ->where('user_id', $user_id)
            ->where('question_id', null)
            ->first();
        $filename = ($type === 'solution') ? $full_pdf->file : $full_pdf->submission;

        $renamed_file = pathinfo($filename, PATHINFO_FILENAME) . "_{$page_numbers_and_extension}";
        $renamed_file = str_replace(' ', '', $renamed_file);//get rid of spaces
        $pdf->Output('F', $storage_path . $dir . '/' . $renamed_file);
        $cutupFilename = str_replace(storage_path(), '', $dir . '/' . $renamed_file);
        $cutupContents = Storage::disk('local')->get($cutupFilename);
        Storage::disk('s3')->put($cutupFilename, $cutupContents, ['StorageClass' => 'STANDARD_IA']);

        return $renamed_file;

    }
    function cutUpPdf(string $filename, string $directory, int $assignment_id, int $user_id)
    {
        $pdf = new Fpdi();
        $storage_path = Storage::disk('local')->getAdapter()->getPathPrefix();
        $pageCount = $pdf->setSourceFile($storage_path . $filename);
        $file = pathinfo($filename, PATHINFO_FILENAME);
        // Split each page into a new PDF
        for ($i = 1; $i <= $pageCount; $i++) {
            $newPdf = new Fpdi();
            $newPdf->addPage();

            $newPdf->setSourceFile($storage_path . $filename);
            $newPdf->useTemplate($newPdf->importPage($i));

            $full_path_to_cutup = sprintf('%s/%s_%s.pdf', $storage_path . $directory, $file, $i);

            $path_to_cutup_without_storage_prefix = str_replace($storage_path, '', $full_path_to_cutup);

            $this->create([
                'assignment_id' => $assignment_id,
                'user_id' => $user_id,
                'file' => "{$file}_{$i}.pdf"
            ]);
            $newPdf->output($full_path_to_cutup, 'F');

            $cutupFilename = str_replace(storage_path(), '', $path_to_cutup_without_storage_prefix);
            $cutupContents = Storage::disk('local')->get($path_to_cutup_without_storage_prefix);
            Storage::disk('s3')->put($cutupFilename, $cutupContents, ['StorageClass' => 'STANDARD_IA']);
        }
    }
}
