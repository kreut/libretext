<?php

namespace App\Console\Commands\OneTimers\QuestionSubjectChapterSection;

use App\Console\Commands\OneTimers\Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createCommonsCourseRegexChapterMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:CommonsCourseRegexChapterMapping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            DB::beginTransaction();
            $assignments = DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->where('courses.user_id', 1377)
                ->select('courses.name AS course_name', 'assignments.name', 'assignments.id')
                ->get();
            foreach ($assignments as $assignment) {
                $chapter = $assignment->name;
                if (preg_match('/^\s*(Homework|setHW|Summer-Math-Dev|Introduction)\b/i', $chapter)) {
                    continue;
                }
                if (preg_match('/^\s*Chapter/i', $chapter)) {
                    continue;
                }
                // 2️⃣ Remove the "OER-Mechanics-S-4-" prefix up to the first underscore
                $chapter = preg_replace('/^OER-Mechanics-S-4-[^_]*_/', '', $chapter);

                // 3️⃣ Remove "NODES Chapter 1:" prefix
                $chapter = preg_replace('/^\s*NODES\s+Chapter\s+\d+[:.]\s*/i', '', $chapter);

                // 4️⃣ Remove number ranges like "8 & 9." or "15 & 16."
                $chapter = preg_replace('/^\s*\d+\s*&\s*\d+[.:]?\s*/', '', $chapter);

                // 5️⃣ Remove standard "Chapter 6:" or "6.3:" prefixes
                $chapter = preg_replace('/^\s*(?:Chapter\s*\d+(?:\.\d+)?[:.]|\d+(?:\.\d+)?[:.])\s*/i', '', $chapter);

                $chapter = preg_replace('/^[\p{Z}\p{C}\s]*(?:\d+\.\d+\s*)+/u', '', $chapter);
                $chapter = preg_replace('/^\d+(?:\.\d+)*\s*/', '', $chapter);
                $chapter = preg_replace('/^\s*(?:[0-9][^\s]*|-[^\s]*)\s*/', '', $chapter);
                $chapter = preg_replace('/^:\s*/', '', $chapter);
                $chapter = preg_replace('/^ : */', '', $chapter);
                // 6️⃣ Remove the long list of specific subject prefixes
                $chapter = preg_replace(
                    '/^\s*(
            USask-OER-Mechanics[^_]*_ |
            Fourier\s+series\s+and\s+PDEs\s*-\s* |
            Systems\s+of\s+ODEs\s*-\s* |
            Higher\s+order\s+linear\s+ODEs\s*-\s* |
            Eigenvalue\s+problems\s*-\s* |
            Power\s+series\s+methods\s*-\s* |
            The\s+Laplace\s+Transform\s*-\s* |
            Energy\s+Balances\s*-\s* |
            Material\s+Balances\s*-\s* |
            Engineering\s+Economics\s*-\s* |
            Mechanics\s*-\s* |
            General\s+Chemistry\s*-\s* |
            Fluid\s+Mechanics\s*-\s* |
            Thermodynamics\s*-\s* |
            Heat\s+Transfer\s*-\s* |
            Particle\s+Technology\s*-\s* |
            Dynamics\s+and\s+Control\s*-\s* |
            Dynamics\s*-\s* |
            Mass\s+Transfer\s*-\s* |
            Stagewise\s+Separations\s*-\s* |
            Reactor\s+Design\s*-\s* |
            Process\s+Design\s*-\s* |
            Numerical\s+Methods\s*-\s* |
            Pre-Algebra\s*-\s* |
            Statistics\s*-\s* |
            Essential\s+Mathematics\s*-\s* |
            Elementary\s+Linear\s+Algebra\s*-\s*
        )/ix',
                    '',
                    $chapter
                );
                $chapter = trim($chapter);
                if ($assignment->name !== $chapter) {
                    DB::table('commons_course_regex_chapter_mappings')
                        ->insert(['before_regex' => $assignment->name,
                            'course_name' => $assignment->course_name,
                            'after_regex' => $chapter,
                            'assignment_id' => $assignment->id,
                            'updated_at' => now(),
                            'created_at' => now()]);
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
