<?php

namespace App;

use Carbon\Carbon;
use DOMDocument;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Discussion extends Model
{

    /**
     * @param Assignment $assignment
     * @return void
     * @throws Exception
     */
    public function deleteByAssignment(Assignment $assignment)
    {
        $discussions = DB::table('discussions')->where('assignment_id', $assignment->id)->get();
        $questionMediaUpload = new QuestionMediaUpload();

        foreach ($discussions as $discussion) {
            $discussion_comments = DiscussionComment::where('discussion_id', $discussion->id)->get();
            foreach ($discussion_comments as $discussion_comment) {
                if ($discussion_comment->file) {
                    $questionMediaUpload->deleteFileAndVttFile($discussion_comment->file);
                }
                $discussion_comment->delete();
            }
            $discussion->delete();

        }
    }

    /**
     * @param int $assignment_id
     * @param int $question_id
     * @param int $user_id
     * @return mixed
     */
    public function numberOfDiscussionsThatSatisfiedTheRequirements(int $assignment_id,
                                                                    int $question_id,
                                                                    int $user_id)
    {

        return $this->join('discussion_comments', 'discussions.id', '=', 'discussion_comments.discussion_id')
            ->where('discussions.assignment_id', $assignment_id)
            ->where('discussions.question_id', $question_id)
            ->where('discussion_comments.user_id', $user_id)
            ->where('discussion_comments.satisfied_requirement', 1)
            ->distinct()
            ->count('discussions.id');
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param string $media_upload_id
     * @return array
     */
    public function getByAssignmentQuestionMediaUploadId(Assignment $assignment,
                                                         Question   $question,
                                                         string     $media_upload_id): array
    {

        $questionMediaUpload = new QuestionMediaUpload();
        $enrolled_student_ids = $assignment->course->enrolledUsersWithFakeStudent->pluck('id')->toArray();
        $enrolled_students = DB::table('users')
            ->whereIn('id', $enrolled_student_ids)
            ->orWhere('id', $assignment->course->user_id)
            ->select('id', DB::raw('CONCAT(first_name, " " , last_name) AS name'), 'time_zone')
            ->get();
        $enrolled_students_by_user_id = [];
        foreach ($enrolled_students as $enrolled_student) {
            $enrolled_students_by_user_id[$enrolled_student->id] = $enrolled_student->name;
            $enrolled_student_time_zones_by_user_id[$enrolled_student->id] = $enrolled_student->time_zone;
        }


        $discussion_infos = $this->join('discussion_comments', 'discussions.id', '=', 'discussion_comments.discussion_id')
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->select('discussions.id AS discussion_id',
                'discussions.created_at AS discussion_created_at',
                'discussions.user_id AS discussion_user_id',
                'discussion_comments.id AS discussion_comments_id',
                'discussion_comments.user_id AS discussion_comments_user_id',
                'discussion_comments.id AS comment_id',
                'discussion_comments.text',
                'discussion_comments.file',
                'discussion_comments.transcript',
                'discussion_comments.re_processed_transcript',
                'discussion_comments.created_at AS comment_created_at'
            );
        if ($media_upload_id) {
            $discussion_infos = $discussion_infos->where('media_upload_id', $media_upload_id);
        }
        $discussion_infos = $discussion_infos->orderBy('comment_created_at', 'ASC')
            ->get();
        $discussions = [];
        $discussions_by_user_id = [];
        $htmlDom = new DOMDocument();
        foreach ($discussion_infos as $value) {
            $discussion_id = $value->discussion_id;
            if (!isset($discussions[$discussion_id])) {
                $discussions[$discussion_id] = [
                    'id' => $discussion_id,
                    'created_at' => $this->_formatDate($value->discussion_created_at, $enrolled_student_time_zones_by_user_id[$value->discussion_user_id]),
                    'started_by' => $enrolled_students_by_user_id[$value->discussion_user_id],
                    'comments' => []
                ];
            }
            $discussions[$discussion_id]['comments'][] = [
                'id' => $value->comment_id,
                'created_by_user_id' => $value->discussion_comments_user_id,
                'created_by_name' => $enrolled_students_by_user_id[$value->discussion_comments_user_id],
                'text' => $question->addTimeToS3Images($value->text, $htmlDom, false),
                'file' => $value->file,
                'transcript' => $value->transcript ? $questionMediaUpload->parseVtt($value->transcript) : null,
                're_processed_transcript' => $value->re_processed_transcript,
                'created_at' => $this->_formatDate($value->comment_created_at, $enrolled_student_time_zones_by_user_id[$value->discussion_user_id])];
            if (!isset($discussions_by_user_id[$value->discussion_comments_user_id])) {
                $discussions_by_user_id[$value->discussion_comments_user_id] = [
                    'user_id' => $value->discussion_comments_user_id,
                    'comments' => []];
            }
            $discussions_by_user_id[$value->discussion_comments_user_id]['comments'][] = [
                'discussion_comment_id' => $value->comment_id,
                'discussion_id' => $discussion_id,
                'text' => $question->addTimeToS3Images($value->text, $htmlDom, false),
                'file' => $value->file,
                'created_at' => $this->_formatDate($value->comment_created_at, $enrolled_student_time_zones_by_user_id[$value->discussion_comments_user_id])
            ];
        }
      foreach ($discussions as $key => $discussion){
          if (isset($discussion['comments'])){
              $discussion['comments'][$key] = rsort($discussion['comments']);
          }
      }
        foreach ($enrolled_students as $enrolled_student) {
            $discussions_by_user_id[$enrolled_student->id]['user_id'] = $enrolled_student->id;
        }
        return ['discussions' => array_values($discussions), 'discussions_by_user_id' => array_values($discussions_by_user_id)];
    }

    /**
     * @param $date
     * @param $time_zone
     * @return string
     */
    private function _formatDate($date, $time_zone): string
    {
        return Carbon::parse($date)
            ->setTimezone($time_zone)
            ->format('n/j/y \a\t g:iA', $time_zone);

    }
}
