<?php

namespace App;

use App\Assignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use setasign\Fpdi\Fpdi;


class Cutup extends Model
{
    protected $guarded = [];


    public function forcePDFRecompileSolutionsByAssignment(int $assignment_id, int $user_id, Solution $Solution)
    {
        $pdf = new FPDI();
        $dir = "solutions/$user_id";
        $storage_path = Storage::disk('local')->getAdapter()->getPathPrefix();

        //recompile if add a single question OR cutups for a question
        //get all of the question ids from that assignment as an array order by assignment_question_id
        $question_ids = DB::table('assignment_question')
                        ->where('assignment_id',$assignment_id)
                        ->orderBy('id')
                        ->select('question_id')
                        ->get()
                        ->pluck('question_id');


        //get all uploaded solutions for that assignment
        //make as an array by question id
        $solutions = $Solution->whereIn('question_id', $question_ids)->where('user_id', $user_id)->get();
        $files_to_merge_by_question_id = [];
        foreach ($solutions as $key => $value) {
            if ($value->type === 'q' && (pathinfo($value->file, PATHINFO_EXTENSION) === 'pdf')) {
                $files_to_merge_by_question_id[$value->question_id] = $value->file;
            }
        }
        $files_to_merge = [];
        //loop through so they're in order of the question_ids
        foreach ($question_ids as $key => $question_id){
            if (isset($files_to_merge_by_question_id[$question_id])){
                $files_to_merge[] = $files_to_merge_by_question_id[$question_id];
            }

        }
        $compiled_filename = false;
        if ($files_to_merge) {
            foreach ($files_to_merge as $file) {
                $pageCount = $pdf->setSourceFile($storage_path . $dir . '/' . $file);
                for ($i = 0; $i < $pageCount; $i++) {
                    $tpl = $pdf->importPage($i + 1, '/MediaBox');
                    $pdf->addPage();
                    $pdf->useTemplate($tpl);
                }
            }
            $compiled_filename = md5(uniqid('', true)) . '.pdf';
            $this->saveOutputContents($pdf, $storage_path, $dir, $compiled_filename);
        }
        return $compiled_filename;

    }

    public function saveOutputContents($pdf, $storage_path, $dir, $filename)
    {
        $pdf->Output('F', $storage_path . $dir . '/' . $filename);
        $s3_name = str_replace(storage_path(), '', $dir . '/' . $filename);
        $contents = Storage::disk('local')->get($s3_name);
        Storage::disk('s3')->put($s3_name, $contents, ['StorageClass' => 'STANDARD_IA']);
    }


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
            $filename = $file_info->file;
            $location_of_underscore = strrpos($filename, '_');
            $page_number_and_extension = substr($filename, $location_of_underscore + 1);
            $page_number = str_replace('.pdf', '', $page_number_and_extension);
            $page_numbers[] = $page_number;
            if (in_array($page_number, $chosen_cutups)) {
                $file = $storage_path . $dir . '/' . $filename;
               if (!Storage::exists($file)){
                   $s3_file_contents = Storage::disk('s3')->get($dir . '/' . $filename);
                   Storage::disk('local')->put($dir . '/' . $filename, $s3_file_contents);
               }
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
        $this->saveOutputContents($pdf, $storage_path, $dir, $renamed_file);

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
