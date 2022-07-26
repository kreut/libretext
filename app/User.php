<?php

namespace App;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject //, MustVerifyEmail
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'time_zone', 'role'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    /**
     * Get the oauth providers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function oauthProviders()
    {
        return $this->hasMany(OAuthProvider::class);
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * @return int
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function enrollments()
    {
        return $this->belongsToMany('App\Course', 'enrollments')->withTimestamps();
    }

    public function extensions()
    {
        return $this->hasMany('App\Extension');
    }

    public function learningTrees()
    {
        return $this->hasMany('App\LearningTree');
    }

    public function assignmentsAndAssignToTimingsByCourse(int $course_id)
    {
        $assignments_info = DB::table('assign_to_timings')
            ->join('assignments', 'assign_to_timings.assignment_id', '=', 'assignments.id')
            ->where('assignments.course_id', $course_id)
            ->select('assignments.id AS assignment_id', 'assign_to_timings.id AS assign_to_timing_id')
            ->get();
        $assignments = [];
        if ($assignments_info->isNotEmpty()) {
            foreach ($assignments_info as $value) {
                $assignments[] = ['assignment_id' => $value->assignment_id,
                    'assign_to_timing_id' => $value->assign_to_timing_id];
            }
        }
        return $assignments;
    }

    /**
     * @return bool
     */
    public function isAdminWithCookie(): bool
    {
        $admins = ['adapt@libretexts.org', 'dlarsen@ucdavis.edu'];
        if (app()->environment('local', 'testing')) {
            $admins[] = 'me@me.com';
        }

        $isValidEmail = in_array(session()->get('original_email'), $admins);//get the original email since they may be in student view
        $isValidCookie = $this->isMe();
        return $isValidEmail && $isValidCookie;
    }

    /**
     * @return bool
     */
    public function isMe(): bool
    {
        return isset(request()->cookie()['IS_ME']) && (request()->cookie()['IS_ME'] === config('myconfig.is_me_cookie'));

    }


}
