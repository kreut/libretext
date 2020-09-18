<?php

use Illuminate\Database\Seeder;
use App\AssignmentFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AssignmentFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ([2, 3, 4] as $value){
        AssignmentFile::create(['user_id' => $value,
            'assignment_id' => 1,
            'submission' => 'fake_' . $value.'.pdf',
            'original_filename' => 'orig_fake_' . $value.'.pdf',
            'date_submitted' => Carbon::now()]);
            $submissionContents = Storage::disk('local')->get("assignments/1/fake_$value.pdf");
            Storage::disk('s3')->put("assignments/1/fake_$value.pdf",  $submissionContents,['StorageClass' => 'STANDARD_IA']);
        }
    }
}
