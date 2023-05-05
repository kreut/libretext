<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\RubricCategory;
use App\RubricCategorySubmission;
use App\RubricCriteriaTest;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Snowfire\Beautymail\Beautymail;

class OpenAIController extends Controller
{
    /**
     * @param Request $request
     * @param string $type
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @param RubricCriteriaTest $rubricCriteriaTest
     * @return array
     * @throws Exception
     */
    public function results(Request                  $request, string $type,
                            RubricCategorySubmission $rubricCategorySubmission,
                            RubricCriteriaTest       $rubricCriteriaTest): array
    {
        $response['type'] = 'error';
        $token = $request->bearerToken();
        if ($token && ($token === config('myconfig.my_essay_feedback_token'))) {
            try {
                switch ($request->type) {
                    case('lab-report'):
                        DB::beginTransaction();
                        $rubricCategorySubmission->where('user_id', $request->user_id)
                            ->where('rubric_category_id', $request->rubric_category_id)
                            ->where('assignment_id', $request->batch_id)
                            ->update(['status' => $request->status, 'message' => $request->message]);
                        DB::table('rubric_category_criteria_pendings')
                            ->where('user_id', $request->user_id)
                            ->where('rubric_category_id', $request->rubric_category_id)
                            ->where('assignment_id', $request->batch_id)
                            ->update(['processed' => 1]);
                        if (!DB::table('rubric_category_criteria_pendings')
                            ->where('rubric_category_id', $request->rubric_category_id)
                            ->where('assignment_id', $request->batch_id)
                            ->where('processed', 0)
                            ->first()) {

                            $assignment = Assignment::find($request->batch_id);
                            $rubric_category = RubricCategory::find($request->rubric_category_id);
                            $notifiy_user_id = DB::table('rubric_category_criteria_pendings')
                                ->where('user_id', $request->user_id)
                                ->where('rubric_category_id', $request->rubric_category_id)
                                ->where('assignment_id', $request->batch_id)
                                ->first()->notify_user_id;
                            $to_user = User::find($notifiy_user_id);
                            $email_info = [
                                'email' => $to_user->email,
                                'name' => $to_user->first_name,
                                'assignment' => $assignment->name,
                                'course' => $assignment->course->name,
                                'category' => $rubric_category->category,
                                'url' => request()->getSchemeAndHttpHost() . "/assignments/$assignment->id/grading"
                            ];
                            $beauty_mail = app()->make(Beautymail::class);
                            $beauty_mail->send('emails.notify_user_of_processed_ai', $email_info, function ($message)
                            use ($email_info) {
                                $message
                                    ->from('adapt@noreply.libretexts.org', 'ADAPT')
                                    ->to($email_info['email'])
                                    ->subject('AI processed new criteria');
                            });
                            DB::table('rubric_category_criteria_pendings')
                                ->where('rubric_category_id', $request->rubric_category_id)
                                ->where('assignment_id', $request->batch_id)
                                ->delete();
                        }
                        DB::commit();
                        break;
                    case('testing'):
                        $id = str_replace('criteria-test-', '', $request->batch_id);
                        $rubricCriteriaTest->where('id', $id)->update(['status' => $request->status, 'message' => $request->message]);
                        break;
                    default:
                        throw new Exception ("$type is not a valid type to process.");

                }
                $response['type'] = 'success';
                $response['message'] = 'received';
            } catch (Exception $e) {
                if (DB::transactionLevel()) {
                    DB::rollback();
                }
                $h = new Handler(app());
                $h->report($e);
                $response['type'] = 'error';
                $response['message'] = $e->getMessage();
            }
        } else {
            $response['message'] = "Not authorized for processing the AI results using token: $token";
        }
        return $response;
    }
}
