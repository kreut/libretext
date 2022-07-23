<?php

namespace App\Http\Controllers;

use App\Course;
use App\Exceptions\Handler;
use App\MetaTag;
use App\Question;
use App\SavedQuestionsFolder;
use App\Tag;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class MetaTagController extends Controller
{

    /**
     * @param Request $request
     * @param $course_id
     * @param $assignment_id
     * @param MetaTag $metaTag
     * @return array
     * @throws Exception
     */
    public function update(Request $request, $course_id, $assignment_id, MetaTag $metaTag): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $metaTag);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $apply_to = $request->apply_to;
            $author = $request->author;
            $license = $request->license;
            $license_version = $request->license_version;
            $tags_to_add = $request->tags_to_add;
            $tag_to_remove = $request->tag_to_remove;
            DB::beginTransaction();
            if ($apply_to === 'all') {
                if ($assignment_id === 'all') {
                    $course = Course::find($course_id);
                    $assignment_ids = $course->assignments->pluck('id')->toArray();
                } else {
                    $assignment_ids = [$assignment_id];
                }
                $question_ids = DB::table('assignment_question')
                    ->select('question_id')
                    ->whereIn('assignment_id', $assignment_ids)
                    ->pluck('question_id')
                    ->toArray();
            } else {
                $question_ids = [$apply_to];
            }
            if ($author) {
                $user = DB::table('users')
                    ->where(DB::raw('CONCAT(first_name, " ", last_name)'), $author)
                    ->whereIn('role', [2, 5])
                    ->first();
                if (!$user) {
                    Question::whereIn('id', $question_ids)
                        ->update(['author' => $author,
                            'updated_at' => now()]);
                } else {
                    $saved_questions_folder = DB::table('saved_questions_folders')
                        ->where('user_id', $user->id)
                        ->where('type', 'my_questions')
                        ->orderBy('id')
                        ->first();
                    if (!$saved_questions_folder) {
                        $savedQuestionsFolder = new SavedQuestionsFolder();
                        $savedQuestionsFolder->user_id = $user->id;
                        $savedQuestionsFolder->name = 'Main';
                        $savedQuestionsFolder->type = 'my_questions';
                        $savedQuestionsFolder->save();
                        $folder_id = $savedQuestionsFolder->id;
                    } else {
                        $folder_id = $saved_questions_folder->id;
                    }
                    Question::whereIn('id', $question_ids)
                        ->update(['author' => $author,
                            'question_editor_user_id' => $user->id,
                            'folder_id' => $folder_id,
                            'updated_at' => now()]);
                }
            }
            if ($license) {
                Question::whereIn('id', $question_ids)
                    ->update(['license' => $license,
                        'license_version' => $license_version,
                        'updated_at' => now()]);
            }
            if ($tags_to_add) {
                $tags = explode(',', $tags_to_add);
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    $existing_tag = DB::table('tags')->where('tag', $tag)->first();
                    if (!$existing_tag) {
                        $new_tag = Tag::create(['tag' => $tag]);
                        $tag_id = $new_tag->id;

                    } else {
                        $tag_id = $existing_tag->id;
                    }
                    foreach ($question_ids as $question_id) {
                        $question_tag_exists = DB::table('question_tag')
                            ->where('question_id', $question_id)
                            ->where('tag_id', $tag_id)
                            ->first();
                        if (!$question_tag_exists) {
                            DB::table('question_tag')->insert([
                                'question_id' => $question_id,
                                'tag_id' => $tag_id,
                                'created_at' => now(),
                                'updated_at' => now()]);
                        }
                    }
                }
            }
            if ($tag_to_remove) {
                $tag_id_exists = DB::table('tags')
                    ->where('id', $tag_to_remove)
                    ->first();

                if (!$tag_id_exists) {
                    $response['message'] = "The tag that you are trying to remove does not exist.";
                    return $response;
                }

                foreach ($question_ids as $question_id) {
                    DB::table('question_tag')
                        ->where('question_id', $question_id)
                        ->where('tag_id', $tag_to_remove)
                        ->delete();
                    $question_tag_exists = DB::table('question_tag')
                        ->where('tag_id', $tag_to_remove)
                        ->first();
                    if (!$question_tag_exists) {
                        DB::table('tags')
                            ->where('id', $tag_to_remove)
                            ->delete();
                    }
                }
            }
            $response['type'] = 'success';
            $response['message'] = "The meta-tags have been updated.";

            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to update the meta-tags.";
        }
        return $response;


    }


    /**
     * @param $course_id
     * @param $assignment_id
     * @param int $per_page
     * @param int $current_page
     * @param MetaTag $metaTag
     * @return array
     * @throws Exception
     */
    public
    function getMetaTagsByCourseAssignment(
        $course_id,
        $assignment_id,
        int $per_page,
        int $current_page,
        MetaTag $metaTag): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getMetaTagsByCourseAssignment', $metaTag);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $level = $assignment_id === 'all' ? 'course' : 'assignment';
        try {
            if (!DB::table("courses")->where('id', $course_id)->first()) {
                $response['message'] = "There is no course with ID.";
                return $response;
            }
            if ($assignment_id !== 'all') {
                if (!DB::table("assignments")->where('id', $assignment_id)->first()) {
                    $response['message'] = "There is no assignment with ID in your chosen course.";
                    return $response;
                }
            }

            switch ($level) {
                case('assignment'):
                    $assignment_ids = [$assignment_id];
                    break;
                case('course'):
                    $assignment_ids = Course::find($course_id)->assignments->pluck('id')->toArray();
                    break;
                default:
                    $assignment_ids = [];
            }

            $total_num_questions = count(DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->select('questions.id')
                ->whereIn('assignment_question.assignment_id', $assignment_ids)
                ->get());

            $question_meta_tags = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->select('questions.id',
                    'questions.title',
                    'questions.author',
                    'questions.license',
                    'questions.license_version',
                    'questions.technology_iframe',
                    'questions.technology',
                    'questions.technology_id',
                    'questions.text_question')
                ->whereIn('assignment_question.assignment_id', $assignment_ids)
                ->orderBy('id', 'desc')
                ->skip($per_page * ($current_page - 1))
                ->take($per_page)
                ->get();


            $question_ids = $question_meta_tags->pluck('id')->toArray();
            $tags = DB::table('question_tag')
                ->join('tags', 'question_tag.tag_id', '=', 'tags.id')
                ->select('question_id', 'tag', 'tag_id')
                ->whereIn('question_id', $question_ids)
                ->get();
            $tags_by_question_id = [];
            $tag_to_remove_options = [['value' => null, 'text' => 'Choose a tag']];
            $used_tags_to_remove = [];
            foreach ($tags as $tag) {
                if (!in_array($tag->tag_id, $used_tags_to_remove)) {
                    $tag_to_remove_options[] = ['value' => $tag->tag_id, 'text' => $tag->tag];
                    $used_tags_to_remove[] = $tag->tag_id;
                }
                if (!isset($tags_by_question_id[$tag->question_id])) {
                    $tags_by_question_id[$tag->question_id] = [$tag->tag];
                } else {
                    if (!in_array($tag->tag, $tags_by_question_id[$tag->question_id])) {
                        $tags_by_question_id[$tag->question_id][] = $tag->tag;
                    }
                }
            }
            foreach ($question_meta_tags as $question) {
                $question->tags = $tags_by_question_id[$question->id] ?? [];
            }
            $response['question_meta_tags'] = $question_meta_tags;
            $response['tag_to_remove_options'] = $tag_to_remove_options;
            $response['total_num_questions'] = $total_num_questions;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the meta-tags by $level";
        }
        return $response;
    }

}
