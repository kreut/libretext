<?php

namespace App\Console\Commands\OneTimers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConvertFileUploadModes extends Command
{
    /**
     * @var string
     */
    protected $signature = 'assignments:convert-file-upload-modes
                            {--restore : Put back the original File Upload Modes saved in file_upload_mode_backups}';

    /**
     * @var string
     */
    protected $description = 'Saves the File Upload Mode of every Compiled PDF / Individual assignment in file_upload_mode_backups and switches them to Compiled & Individual.  With --restore, puts the saved modes back.';

    /**
     * @return int
     */
    public function handle(): int
    {
        if (!Schema::hasTable('file_upload_mode_backups')) {
            $this->error('The file_upload_mode_backups table does not exist.  Please run the migrations first.');
            return 1;
        }

        if ($this->option('restore')) {
            $num_restored = $this->_restore();
            $this->info("Restored the original File Upload Mode for $num_restored assignment(s).");
        } else {
            $num_converted = $this->_convert();
            $this->info("Saved the original File Upload Mode and switched $num_converted assignment(s) to Compiled & Individual.");
        }
        return 0;
    }

    /**
     * Saves the current mode of every Compiled PDF / Individual assignment (replacing any earlier backup of that
     * assignment), then switches them to 'both'.
     *
     * @return int
     */
    private function _convert(): int
    {
        $modes_to_convert = ['compiled_pdf', 'individual_assessment'];
        return DB::transaction(function () use ($modes_to_convert) {
            DB::table('file_upload_mode_backups')
                ->whereIn('assignment_id', function ($query) use ($modes_to_convert) {
                    $query->select('id')
                        ->from('assignments')
                        ->whereIn('file_upload_mode', $modes_to_convert);
                })
                ->delete();

            DB::table('file_upload_mode_backups')->insertUsing(['assignment_id', 'file_upload_mode'],
                DB::table('assignments')
                    ->select('id', 'file_upload_mode')
                    ->whereIn('file_upload_mode', $modes_to_convert));

            return DB::table('assignments')
                ->whereIn('file_upload_mode', $modes_to_convert)
                ->update(['file_upload_mode' => 'both']);
        });
    }

    /**
     * Puts each backed-up assignment back on its saved mode.  Assignments that aren't in the backup are left alone.
     *
     * @return int
     */
    private function _restore(): int
    {
        $num_restored = 0;
        DB::transaction(function () use (&$num_restored) {
            $backups = DB::table('file_upload_mode_backups')->get();
            foreach ($backups->groupBy('file_upload_mode') as $file_upload_mode => $rows) {
                foreach ($rows->pluck('assignment_id')->chunk(1000) as $assignment_ids) {
                    $num_restored += DB::table('assignments')
                        ->whereIn('id', $assignment_ids->toArray())
                        ->update(['file_upload_mode' => $file_upload_mode]);
                }
            }
        });
        return $num_restored;
    }
}
