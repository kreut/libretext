<?php

namespace App\Http\Controllers;

use App\Course;
use App\Exceptions\Handler;
use App\MetaTag;
use App\PendingQuestionOwnershipTransfer;
use App\Question;
use App\SavedQuestionsFolder;
use App\Tag;
use App\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class MetaTagController extends Controller
{

    /**
     * @param Request $request
     * @param MetaTag $metaTag
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @param PendingQuestionOwnershipTransfer $pendingQuestionOwnershipTransfer
     * @return array
     * @throws Exception
     */
    public function update(Request                          $request,
                           MetaTag                          $metaTag,
                           SavedQuestionsFolder             $savedQuestionsFolder,
                           PendingQuestionOwnershipTransfer $pendingQuestionOwnershipTransfer): array
    {
        $response['type'] = 'error';
        $filter_by = $request->filter_by;
        $authorized = Gate::inspect('update', [$metaTag, $filter_by]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $apply_to = $request->apply_to;
            $author = $request->author;
            $owner = $request->owner;
            $license = $request->license;
            $license_version = $request->license_version;
            $tags_to_add = $request->tags_to_add;
            $tag_to_remove = $request->tag_to_remove;
            $source_url = $request->source_url;
            $public = $request->public;
            DB::beginTransaction();

            if ($apply_to === 'all') {
                if (isset($filter_by['course_id'])) {
                    $course_id = $filter_by['course_id'];
                    $assignment_id = $filter_by['assignment_id'];
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
                    $folder_id = $filter_by['folder_id'];
                    if ($folder_id === 'all') {
                        $question_ids = DB::table('questions')
                            ->where('question_editor_user_id', $request->user()->id)
                            ->get('id')
                            ->pluck('id')
                            ->toArray();
                    } else {
                        $question_ids = DB::table('questions')
                            ->where('question_editor_user_id', $request->user()->id)
                            ->where('folder_id', $folder_id)
                            ->get('id')
                            ->pluck('id')
                            ->toArray();
                    }
                }
            } else {
                $owns_question = DB::table('questions')->where('id', $apply_to)
                    ->where('question_editor_user_id', $request->user()->id)
                    ->first();
                if (!$request->user()->isMe() && !$owns_question) {
                    $response['message'] = "You do not own that question.  You cannot update the meta-tags.";
                    return $response;
                }
                $question_ids = [$apply_to];
            }
            if ($public !== null) {
                Question::whereIn('id', $question_ids)
                    ->update(['public' => $public,
                        'updated_at' => now()]);
            }
            if ($author) {
                Question::whereIn('id', $question_ids)
                    ->update(['author' => $author,
                        'updated_at' => now()]);
            }

            if ($owner) {
                if (!User::where('id', $owner['value'])
                    ->whereIn('role', [2, 5])
                    ->first()) {
                    $response['message'] = "The user new owner must be either an instructor or a non-instructor question editor.";
                    return $response;
                } else {
                    if (!$request->user()->isMe()) {
                        $number_owned_questions = Question::whereIn('id', $question_ids)
                            ->where('question_editor_user_id', $request->user()->id)
                            ->count();
                        if ($number_owned_questions !== count($question_ids)) {
                            $response['message'] = "You do not own all of those questions so you cannot change the ownership.";
                            return $response;
                        }
                    }
                }
                if ($request->user()->isMe()) {
                    $savedQuestionsFolder->moveQuestionsToNewOwnerInTransferredQuestions($owner['value'], $question_ids);
                } else {
                    $response = $pendingQuestionOwnershipTransfer->createPendingOwnershipTransferRequest(User::where('id', $owner['value'])->first(), $request->user(), $question_ids);
                    if ($response['type'] === 'error') {
                        return $response;
                    }
                }
            }

            if ($license) {
                Question::whereIn('id', $question_ids)
                    ->update(['license' => $license,
                        'license_version' => $license_version,
                        'updated_at' => now()]);
            }

            if ($source_url) {
                if (!filter_var($source_url, FILTER_VALIDATE_URL)) {
                    $response['message'] = "$source_url is not a valid URL.";
                    return $response;
                }
                Question::whereIn('id', $question_ids)
                    ->update(['source_url' => $source_url,
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

            if ($owner && !$request->user()->isMe()) {
                $response['message'] = "All meta-tags have been updated except for the owner.  Once the new owner accepts ownership via email, ownership will be transferred.";
            } else {
                $response['message'] = "The meta-tags have been updated.";
            }

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
     * @param Request $request
     * @param bool $admin_view
     * @param int $per_page
     * @param int $current_page
     * @param MetaTag $metaTag
     * @return array
     * @throws Exception
     */
    public
    function getMetaTagsByFilter(
        Request $request,
        bool    $admin_view,
        int     $per_page,
        int     $current_page,
        MetaTag $metaTag): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getMetaTagsByFilter', [$metaTag, $admin_view]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $course_id = $request->course_id;
        $assignment_id = $request->assignment_id;
        $folder_id = $request->folder_id;
        if ($course_id) {
            $level = $assignment_id === 'all' ? 'course' : 'assignment';
        } else {
            $level = $folder_id === 'all' ? 'my_questions' : 'folder';
        }
        try {
            if ($course_id) {
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
            }

            if ($folder_id) {
                if ($folder_id !== 'all') {
                    if (!DB::table("saved_questions_folders")
                        ->where('id', $folder_id)
                        ->where('user_id', $request->user()->id)
                        ->where('type', 'my_questions')
                        ->first()) {
                        $response['message'] = "There is no My Questions folder that you own with that ID.";
                        return $response;
                    }
                }
            }

            if ($course_id) {
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
            } else {
                switch ($level) {
                    case('folder'):
                        $folder_ids = [$folder_id];
                        break;
                    case('my_questions'):
                        $folder_ids = DB::table("saved_questions_folders")
                            ->where('user_id', $request->user()->id)
                            ->where('type', 'my_questions')
                            ->get('id')
                            ->pluck('id')
                            ->toArray();
                        $transferred_questions_folder = DB::table('saved_questions_folders')
                            ->where('name', 'H5P Imports')
                            ->where('user_id', $request->user()->id)
                            ->where('type', 'Transferred Questions')
                            ->first();
                        if ($transferred_questions_folder) {
                            array_unshift($folder_ids, $transferred_questions_folder->id);
                        }
                        $h5p_imports_folder = DB::table('saved_questions_folders')
                            ->where('name', 'H5P Imports')
                            ->where('user_id', $request->user()->id)
                            ->where('type', ',my_favorites')
                            ->first();
                        if ($h5p_imports_folder) {
                            array_unshift($folder_ids, $h5p_imports_folder->id);
                        }
                        break;
                    default:
                        $folder_ids = [];
                }

            }
            if ($course_id) {
                $total_num_questions = count(DB::table('assignment_question')
                    ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                    ->select('questions.id')
                    ->whereIn('assignment_question.assignment_id', $assignment_ids)
                    ->get());
            } else {
                $total_num_questions = count(DB::table('questions')
                    ->select('id')
                    ->whereIn('folder_id', $folder_ids)
                    ->get());
            }

            if ($course_id) {
                $question_meta_tags = DB::table('assignment_question')
                    ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                    ->select('questions.id',
                        'questions.title',
                        'questions.author',
                        'questions.public',
                        'questions.license',
                        'questions.license_version',
                        'questions.source_url')
                    ->whereIn('assignment_question.assignment_id', $assignment_ids);

            } else {
                $question_meta_tags = DB::table('questions')
                    ->select('questions.id',
                        'questions.title',
                        'questions.author',
                        'questions.public',
                        'questions.license',
                        'questions.license_version',
                        'questions.source_url')
                    ->whereIn('folder_id', $folder_ids);
            }

            if (!$admin_view) {
                $question_meta_tags = $question_meta_tags->where('question_editor_user_id', request()->user()->id);
            }

            $non_skipped_question_meta_tags = $question_meta_tags->get();

            $question_meta_tags = $question_meta_tags->orderBy('id', 'desc')
                ->skip($per_page * ($current_page - 1))
                ->take($per_page)
                ->get();


            $non_skipped_question_ids = $non_skipped_question_meta_tags->pluck('id')->toArray();

            $tags = DB::table('question_tag')
                ->join('tags', 'question_tag.tag_id', '=', 'tags.id')
                ->select('question_id', 'tag', 'tag_id')
                ->whereIn('question_id', $non_skipped_question_ids)
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
            $response['message'] = "We were unable to get the meta-tags by $level.";
        }
        return $response;
    }

}
