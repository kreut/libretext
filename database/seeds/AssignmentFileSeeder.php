<?php

use Illuminate\Database\Seeder;
use App\AssignmentFile;
use Carbon\Carbon;
class AssignmentFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ([1, 2, 3] as $value){
        AssignmentFile::create(['user_id' => $value,
            'assignment_id' => 1,
            'submission' => 'fake_' . $value.'.pdf',
            'date_submitted' => Carbon::now()]);
        }
    }
}
