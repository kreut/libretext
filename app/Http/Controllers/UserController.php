<?php

namespace App\Http\Controllers;


use App\Assignment;
use App\AssignToUser;
use App\Course;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Extension;
use App\ExtraCredit;
use App\Helpers\Helper;
use App\Http\Requests\AccountValidationCodeRequest;
use App\Http\Requests\InviteStudentRequest;
use App\Http\Requests\EmailLinkToAccountRequest;
use App\Http\Requests\IsValidEmailUpdateRequest;
use App\Http\Requests\UpdateStudentEmail;
use App\LinkedAccount;
use App\LinkToAccountValidationCode;
use App\LtiGradePassback;
use App\OIDC;
use App\PendingCourseInvitation;
use App\Score;
use App\Section;
use App\Seed;
use App\Submission;
use App\SubmissionFile;
use App\TesterStudent;
use App\User;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Generator;
use Snowfire\Beautymail\Beautymail;

class UserController extends Controller
{

    public function switchAccount(Request $request, User $account_to_switch_to)
    {

        dd($account_to_switch_to);


    }


    /**
     * @param IsValidEmailUpdateRequest $request
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function updateEmail(IsValidEmailUpdateRequest $request, User $user): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('updateEmail', $user);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $data = $request->validated();
            $user_to_update = User::find($request->user_id);
            $user_to_update->email = $data['email'];
            $user_to_update->save();
            $response['type'] = 'success';
            $response['message'] = "The email for $user_to_update->first_name $user_to_update->last_name has been changed to $user_to_update->email.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the email.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function updateRole(Request $request, User $user): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('updateRole', $user);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            if (!in_array($request->role, [2, 3, 4, 5])) {
                $response['message'] = "That is not a valid role.";
                return $response;
            }
            $user = User::find($request->user_id);
            $user->role = $request->role;
            $user->save();
            $formatted_roles = [2 => 'instructor', 3 => 'student', 4 => 'TA', 5 => 'non-instructor editor'];
            $new_role = $formatted_roles[$request->role];
            $response['message'] = "$user->first_name $user->last_name now has the role of <strong>$new_role</strong>.";
            $response['type'] = 'success';
            return $response;


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the role.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function getUserInfoByEmail(Request $request, User $user): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('getUserInfoByEmail', $user);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $user_info = explode(' --- ', $request->user);
            $email = $user_info[1];
            $user = User::where('email', $email)->first();
            if (!$user) {
                $response['message'] = "There is no user with the email address: $email.";
            } else {
                $response['user'] = $user;
                $response['type'] = 'success';
                return $response;

            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the user. Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     * @param Course $course
     * @param User $user
     * @param PendingCourseInvitation $pendingCourseInvitation
     * @return array
     * @throws Exception
     */
    public function revokeStudentInvitations(Course                  $course,
                                             User                    $user,
                                             PendingCourseInvitation $pendingCourseInvitation): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('revokeStudentInvitations', [$user, $course]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $pendingCourseInvitation->where('course_id', $course->id)->delete();
            $response['type'] = 'info';
            $response['message'] = "The course invitations have all been revoked.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "Error: {$e->getMessage()}";
        }

        return $response;


    }

    /**
     * @param InviteStudentRequest $request
     * @param User $user
     * @param OIDC $OIDC
     * @return array
     * @throws Exception
     */
    public function inviteStudent(InviteStudentRequest $request,
                                  User                 $user,
                                  OIDC                 $OIDC): array
    {

        try {
            $student_to_invite = $request->all();
            $response['type'] = 'error';
            $section_id = $student_to_invite['section_id'];
            $course_id = $student_to_invite['course_id'];
            $authorized = Gate::inspect('inviteStudent', [$user, $course_id, $section_id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $errors = [];;
            $last_name = '';
            $first_name = '';
            $email = '';
            $student_id = $student_to_invite["Student ID"] ?? 'None provided.';

            switch ($request->invitation_type) {
                case('student_from_roster_invitation'):

                    $email = trim($student_to_invite['Email']);
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Not a valid email.";
                    }
                    if (isset($student_to_invite['Last Name, First Name'])) {
                        $name_parts = explode(',', $student_to_invite['Last Name, First Name']);
                        if (isset($name_parts[0])) {
                            $last_name = trim($name_parts[0]);
                        } else {
                            $errors[] = "No last name.";
                        }
                        if (isset($name_parts[1])) {
                            $first_name = trim($name_parts[1]);
                        } else {
                            $errors[] = "No first name.";
                        }
                    } elseif (isset($student_to_invite['First Name Last Name'])) {
                        $name_parts = explode(' ', $student_to_invite['First Name Last Name']);
                        if (isset($name_parts[0])) {
                            $first_name = $name_parts[0];
                        } else {
                            $errors[] = "No first name.";
                        }
                        if (isset($name_parts[1])) {
                            $last_name = $name_parts[1];
                        } else {
                            $errors[] = "No last name.";
                        }


                    } else {
                        $last_name = $student_to_invite['Last Name'];
                        if (!$last_name) {
                            $errors[] = "No last name.";
                        }
                        $first_name = $student_to_invite['First Name'];
                        if (!$first_name) {
                            $errors[] = "No first name.";
                        }
                    }


                    $errors = implode(' ', $errors);
                    break;

                case('single_student'):
                    $request->validated();
                    $section = DB::table('sections')->where('id', $request->section_id)->first();
                    $course_id = $section->course_id;
                    $instructor = DB::table('courses')
                        ->join('users', 'courses.user_id', '=', 'users.id')
                        ->where('course_id', $course_id)
                        ->first();
                    $section_id = $request->section_id;
                    $email = trim($request->email);
                    $last_name = $request->last_name;
                    $first_name = $request->first_name;
                    $data = ['email' => $email,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'user_type' => 'student',
                        'time_zone' => $instructor->time_zone];
                    try {
                        $OIDC->autoProvision($data);
                    } catch (Exception $e) {
                        $h = new Handler(app());
                        $h->report($e);
                    }
                    break;
                case('email_list'):
                    $email = trim($request->email);
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors = "Not a valid email.";
                    }
                    break;
                default:
                    $errors = "$request->inivitation_type is not a valid invitation type.";
            }
            if ($errors) {
                $response['type'] = 'error';
                $response['message'] = "Error: $errors";
            } else {
                $access_code = Helper::createAccessCode(8);
                $pendingCourseInvitation = PendingCourseInvitation::updateOrCreate(
                    [
                        'course_id' => $course_id,
                        'section_id' => $section_id,
                        'email' => $email
                    ],
                    [
                        'last_name' => $last_name,
                        'first_name' => $first_name,
                        'student_id' => $student_id,
                        'status' => 'Pending',
                        'access_code' => $access_code,
                    ]
                );

                $email_sent_response = $this->_sendStudentCourseInvitationEmail($course_id,
                    $section_id,
                    $last_name,
                    $first_name, $email, $access_code);
                $status = $email_sent_response === true ? 'Invitation Sent' : $email_sent_response;
                $pendingCourseInvitation->status = $status;
                $pendingCourseInvitation->save();
                $response['message'] = $request->invitation_type === 'student_from_roster_invitation'
                    ? $status
                    : "The invitation has been sent to $request->first_name $request->last_name.";
                if ($email_sent_response === true) {
                    $response['type'] = 'success';
                }

            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "Error: {$e->getMessage()}";
        }

        return $response;

    }

    /**
     * @param int $course_id
     * @param int $section_id
     * @param string $last_name
     * @param string $first_name
     * @param string $email
     * @param string $access_code
     * @return string|true
     */
    private function _sendStudentCourseInvitationEmail(int    $course_id,
                                                       int    $section_id,
                                                       string $last_name,
                                                       string $first_name,
                                                       string $email,
                                                       string $access_code)
    {
        try {
            $course = Course::find($course_id);
            $section = Section::find($section_id);
            $instructor = User::find($course->user_id);
            $instructor_name = "$instructor->first_name $instructor->last_name";
            $instructor_email = $instructor->email;
            $beautymail = app()->make(Beautymail::class);
            $beautymail->send('emails.student_course_invitation', [
                'access_code' => $access_code,
                'first_name' => $first_name,
                'course_name' => $course->name,
                'section_name' => $section->name,
                'instructor_name' => $instructor_name],
                function ($message) use ($email, $first_name, $last_name, $instructor_email, $instructor_name) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'ADAPT')
                        ->to($email, $first_name . ' ' . $last_name)
                        ->replyTo($instructor_email, $instructor_name)
                        ->subject('Your Course Registration Invitation');
                });
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }

    }

    /**
     * @param Request $request
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function getStudentsToInvite(Request $request, User $user): array
    {

        try {
            $authorized = Gate::inspect('getStudentsToInvite', $user);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $response['type'] = 'error';
            $s3_key = $request->s3_key;
            if (!Storage::disk('s3')->exists($s3_key)) {
                $response['message'] = 'We were unable to access your uploaded roster. Please try again or contact us for assistance.';
                return $response;
            }
            $s3_data = Storage::disk('s3')->get($s3_key);
            $students_to_invite = [];
            $fileHandle = fopen('php://memory', 'r+');
            fwrite($fileHandle, $s3_data);
            rewind($fileHandle);
            $headers = fgetcsv($fileHandle);
            $header_errors = [];
            foreach ($headers as $header) {
                if (!in_array($header, ['Last Name', 'First Name', 'First Name Last Name', 'Last Name, First Name', 'Email', 'Student ID'])) {
                    $header_errors[] = $header;
                }
            }
            if ($header_errors) {
                $response['type'] = 'error';
                $response['message'] = 'Invalid headers in your .csv: ' . implode(', ', $header_errors);
                return $response;
            }
            $header_errors = ['Email' => true, 'Last Name' => true, 'First Name' => true];
            foreach ($headers as $header) {
                if ($header === 'Email') {
                    $header_errors['Email'] = false;
                }
                if (in_array($header, ['First Name', 'First Name Last Name', 'Last Name, First Name'])) {
                    $header_errors['First Name'] = false;
                }
                if (in_array($header, ['Last Name', 'First Name Last Name', 'Last Name, First Name'])) {
                    $header_errors['Last Name'] = false;
                }
            }
            $missing_headers = [];
            foreach ($header_errors as $header => $header_error) {
                if ($header_error) {
                    $missing_headers[] = $header;
                }
                if ($missing_headers) {
                    $response['type'] = 'error';
                    $response['message'] = 'Missing headers in .csv: ' . implode(', ', $missing_headers);
                    return $response;
                }
            }

            while (($student_to_invite = fgetcsv($fileHandle)) !== false) {
                $student_to_invite = array_combine($headers, $student_to_invite);
                $student_to_invite['Status'] = 'Pending';

                $students_to_invite[] = $student_to_invite;
            }
            fclose($fileHandle);
            $response['type'] = 'success';
            $response['students_to_invite'] = $students_to_invite;
            $headers[] = 'Status';
            $response['headers'] = $headers;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the list of students to invite.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param User $user
     * @return array|void
     */
    public
    function getStudentRosterUploadTemplate(Request $request, User $user)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getStudentRosterUploadTemplate', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        Helper::arrayToCsvDownload([$request->student_roster_upload_template_headers], 'roster-template.csv');
    }

    /**
     * @param Request $request
     * @param User $user
     * @param Course $course
     * @return array
     */
    public
    function getSignedUserId(Request $request, User $user, Course $course): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('getSignedUserId', $user);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $key = new HmacKey(config('myconfig.analytics_user_id_api_key'));
            $signer = new HS256($key);
            $generator = new Generator($signer);
            $jwt = $generator->generate(['user_id' => $request->user()->id, 'role' => $request->user()->role, 'course_id' => $course->id]);
            $response['token'] = $jwt;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * @param User $student
     * @param Course $course
     * @param AssignToUser $assignToUser
     * @param Assignment $assignment
     * @param Submission $submission
     * @param SubmissionFile $submissionFile
     * @param Score $score
     * @param Extension $extension
     * @param LtiGradePassback $ltiGradePassback
     * @param Seed $seed
     * @param ExtraCredit $extraCredit
     * @param Section $section
     * @param Enrollment $enrollment
     * @param TesterStudent $testerStudent
     * @return array
     * @throws Exception
     */
    public
    function destroy(User             $student,
                     Course           $course,
                     AssignToUser     $assignToUser,
                     Assignment       $assignment,
                     Submission       $submission,
                     SubmissionFile   $submissionFile,
                     Score            $score,
                     Extension        $extension,
                     LtiGradePassback $ltiGradePassback,
                     Seed             $seed,
                     ExtraCredit      $extraCredit,
                     Section          $section,
                     Enrollment       $enrollment,
                     TesterStudent    $testerStudent): array
    {

        $authorized = Gate::inspect('destroy', $student);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $name = "$student->first_name $student->last_name";
            $section_id = $testerStudent->where('student_user_id', $student->id)->first()->section_id;
            $Section = $section->where('id', $section_id)->first();
            DB::beginTransaction();
            $enrollment->removeAllRelatedEnrollmentInformation($student,
                $course->id,
                $assignToUser,
                $assignment,
                $submission,
                $submissionFile,
                $score,
                $extension,
                $ltiGradePassback,
                $seed,
                $extraCredit,
                $Section);
            DB::table('tester_students')->where('student_user_id', $student->id)->delete();
            DB::table('users')->where('id', $student->id)->delete();
            DB::commit();
            $response['type'] = 'info';
            $response['message'] = "$name has been removed from the system.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We are unable to delete $name from the system.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param UpdateStudentEmail $request
     * @param User $student
     * @return array
     * @throws Exception
     */
    public
    function updateStudentEmail(UpdateStudentEmail $request, User $student): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('updateStudentEmail', [$request->user(), $student->id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $data = $request->validated();
            $student->email = $data['email'];
            $student->save();
            $beauty_mail = app()->make(\Snowfire\Beautymail\Beautymail::class);
            $to_email = $data['email'];

            $email_info = ['student_first_name' => $student->first_name,
                'instructor_name' => $request->user()->first_name . ' ' . $request->user()->last_name
            ];
            if (!app()->environment('testing')) {
                $beauty_mail->send('emails.email_changed', $email_info, function ($message)
                use ($to_email) {
                    $message
                        ->from('adapt@noreply.libretexts.org', 'ADAPT')
                        ->to($to_email)
                        ->subject("ADAPT email changed");
                });
            }

            $response['type'] = 'success';
            $response['message'] = "$student->first_name $student->last_name's email has updated and they have been notified by email.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating this student's email. Please try again or contact us.";

        }
        return $response;
    }

    public
    function getAllQuestionEditors(User $user): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAllQuestionEditors', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $response['question_editors'] = DB::table('users')
                ->select('users.id AS value', DB::raw('CONCAT(first_name, " " , last_name) AS label'))
                ->orderBy('label')
                ->whereIn('role', [2, 5])
                ->where('id', '<>', $user->id)
                ->get();

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'We could not get all the potential question owners.';
        }
        return $response;


    }

    /**
     * @param User $user
     * @return array
     * @throws Exception
     */
    public
    function setAnonymousUserSession(User $user): array
    {
        $response['type'] = 'error';


        $authorized = Gate::inspect('setAnonymousUserSession', $user);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            session()->put('anonymous_user', true);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We could not set you as an anonymous user.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     * @param Request $request
     * @param User $user
     * @param School $school
     * @return array
     * @throws Exception
     */
    public
    function getInstructorsWithPublicCourses(Request $request, User $user, School $school): array
    {

        $school_id = $request->name
            ? $school->where('name', $request->name)
                ->first()
                ->id
            : 0;

        try {
            $instructors = DB::table('users')
                ->join('courses', 'users.id', '=', 'courses.user_id')
                ->where('users.role', 2)
                ->where('public', 1);

            if ($school_id) {
                $instructors = $instructors->where('courses.school_id', $school_id);
            }
            $instructors = $instructors->groupBy('users.id')
                ->orderBy('users.last_name')
                ->select('user_id', DB::raw("CONCAT(first_name, ' ',last_name) AS name"))
                ->get();

            $response['type'] = 'success';
            $response['instructors'] = $instructors;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the instructors.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public
    function setCookieUserJWT()
    {
        $cookie = cookie('user_jwt', (string)Auth::guard()->getToken(), 2);
        $response['type'] = 'success';
        return response($response)->withCookie($cookie);
    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public
    function getCookieUserJWT()
    {
        $response['type'] = 'success';
        $response['user_jwt'] = request()->cookie()['user_jwt'] ?? 'None';
        return response($response)->withCookie(cookie('clicker_app', 1));
    }

}
