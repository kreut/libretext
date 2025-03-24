<?php

namespace App\Console\Commands\LMS;


use App\Course;
use App\Custom\LTIDatabase;
use App\Enrollment;
use App\Exceptions\Handler;
use App\LtiNamesAndRoles;
use App\OIDC;
use App\Section;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Overrides\IMSGlobal\LTI\LTI_Names_Roles_Provisioning_Service;
use Overrides\IMSGlobal\LTI\LTI_Service_Connector;
use Snowfire\Beautymail\Beautymail;
use Telegram\Bot\Laravel\Facades\Telegram;

class autoRegisterAndEnrollForNonLMSOnlyCourse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:registerAndEnrollForNonLMSOnlyCourse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @throws Exception
     */
    public function handle(OIDC $OIDC)
    {
        try {
            $course_names_and_roles_urls = DB::table('courses')
                ->join('lti_names_and_roles_urls', 'courses.id', '=', 'lti_names_and_roles_urls.course_id')
                ->where('lms', 1)
                ->where('lms_only_entry', 0)
                ->where('adapt_enrollment_notification_date', '<=', Carbon::now())
                ->where('end_date', '>=', Carbon::now())
                ->select('lti_names_and_roles_urls.*')
                ->get();
            $course_names_and_roles_urls_by_course = [];
            $LTIDatabase = new LTIDatabase();
            foreach ($course_names_and_roles_urls as $value) {
                $course_names_and_roles_urls_by_course[$value->course_id] = $value->url;
            }
            foreach ($course_names_and_roles_urls_by_course as $course_id => $url) {
                $course_info = DB::table('courses')
                    ->join('users', 'courses.user_id', '=', 'users.id')
                    ->where('courses.id', $course_id)
                    ->select('time_zone',
                        'email AS instructor_email',
                        'name AS course_name',
                        DB::raw('CONCAT(first_name, " " , last_name) AS instructor_name'))
                    ->first();
                $course = Course::find($course_id);
                $lti_registration = $course->getLtiRegistration();

                $service_connector = new LTI_Service_Connector($LTIDatabase->find_registration_by_client_id($lti_registration->client_id));
                $names_and_roles = new LTI_Names_Roles_Provisioning_Service($service_connector,
                    ['context_memberships_url' => $url]);
                $members = $names_and_roles->get_members();

                $lms_user_ids_in_course = [];
                $lti_names_and_roles_by_course = LtiNamesAndRoles::where('course_id', $course_id)->get();
                foreach ($lti_names_and_roles_by_course as $value) {
                    $lms_user_ids_in_course[] = $value->lms_user_id;
                }
                $sections = Section::where('course_id', $course_id)->get();
                $enrolled_user_ids = [];
                $section = null;
                $single_section_course = count($sections) === 1;
                if ($single_section_course) {
                    $section = $sections[0];
                    $enrolled_user_ids = $section->course->enrollments->pluck('user_id')->toArray();
                }

                foreach ($members as $member) {
                    $roles = $member['roles'];
                    $is_real_student = true;
                    foreach ($roles as $role) {
                        if (strpos($role, 'TestUser') !== false || strpos($role, 'Instructor') !== false) {
                            $is_real_student = false;
                        }
                    }
                    if ($is_real_student && !in_array($member['user_id'], $lms_user_ids_in_course)) {
                        try {
                            DB::beginTransaction();
                            $ltiNamesAndRoles = new LtiNamesAndRoles();
                            $ltiNamesAndRoles->lms_user_id = $member['user_id'];
                            $ltiNamesAndRoles->course_id = $course_id;
                            $ltiNamesAndRoles->first_name = $member['given_name'];
                            $ltiNamesAndRoles->last_name = $member['family_name'];
                            $ltiNamesAndRoles->email = $member['email'];
                            $ltiNamesAndRoles->status = $member['status'];
                            $ltiNamesAndRoles->emailed_about_account = null;
                            $ltiNamesAndRoles->auto_provisioned_message = null;
                            $ltiNamesAndRoles->save();
                            $user = User::where('lms_user_id', $member['user_id'])->first();
                            if (!$user) {
                                $data = ['email' => $member['email'],
                                    'first_name' => $user->first_name,
                                    'last_name' => $user->last_name,
                                    'user_type' => 'student',
                                    'time_zone' => $course_info->time_zone];
                                if (!app()->environment('local')) {
                                    $oidc_response = $OIDC->autoProvision($data);
                                    if ($oidc_response['type'] === 'success') {
                                        $user->central_identity_id = $oidc_response['central_identity_id'];
                                        $user->save();
                                    }
                                    $ltiNamesAndRoles->auto_provisioned_message = json_encode($oidc_response);
                                } else {
                                    $user->central_identity_id = (string)Str::uuid();
                                    $user->save();
                                }
                            } else {
                                $ltiNamesAndRoles->auto_provisioned_message = "user already existed.";
                            }
                            if (!in_array($user->id, $enrolled_user_ids) && $single_section_course) {
                                $enrollment = new Enrollment();
                                $enrollment->completeEnrollmentDetails($user->id, $section, $course_id, 1);
                            }
                            $beauty_mail = app()->make(Beautymail::class);
                            $to_email = $user->email;
                            $instructor_email = $course_info->instructor_email;
                            $email_info = ['first_name' => $user->first_name,
                                'to_email' => $to_email,
                                'course_name' => $course_info->course_name,
                                'instructor_name' => $course_info->instructor_name,
                                'single_section_course' => $single_section_course,
                                'already_enrolled' => in_array($user->id, $enrolled_user_ids)];
                            $beauty_mail->send('emails.adapt_account_created', $email_info, function ($message)
                            use ($to_email, $instructor_email) {
                                $message
                                    ->from('adapt@noreply.libretexts.org', 'ADAPT')
                                    ->to($to_email)
                                    ->replyTo($instructor_email)
                                    ->subject('ADAPT Registration');
                            });
                            $ltiNamesAndRoles->emailed_about_account = now();
                            $ltiNamesAndRoles->save();
                            DB::commit();
                        } catch (Exception $e) {
                            DB::rollback();
                            echo $e->getMessage();
                            $h = new Handler(app());
                            $h->report($e);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            $h = new Handler(app());
            $h->report($e);
            return 1;
        }
        return 0;
    }
}
