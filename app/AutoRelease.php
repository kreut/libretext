<?php

namespace App;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoRelease extends Model
{
    protected $guarded = [];

    /**
     * @return string[]
     */
    public function keys(): array
    {
        return ['shown', 'show_scores', 'solutions_released', 'students_can_view_assignment_statistics'];
    }

    /**
     * @return array
     */
    public function requestMessages(): array
    {
        $messages["auto_release_students_can_view_assignment_statistics.required"] = "Either remove the auto-release statistics condition or add a time frame.";
        $messages["auto_release_solutions_released.required"] = "Either remove the auto-release solutions condition or add a time frame.";
        $messages["auto_release_show_scores.required"] = "Either remove the show scores condition or add a time frame for auto-release.";
        $messages["auto_release_students_can_view_assignment_statistics_after.required"] = "The auto-release statistics condition is required.";
        $messages["auto_release_solutions_released_after.required"] = "The auto-release solutions condition is required.";
        $messages["auto_release_show_scores_after.required"] = "The auto-release show scores condition is required.";
        return $messages;
    }

    /**
     * @param $auto_release
     * @param $key_after
     * @return mixed
     */
    public function lastDue($auto_release, $key_after)
    {
        return $auto_release->{$key_after} === 'final submission deadline' && $auto_release->final_submission_deadline
            ? $auto_release->final_submission_deadline
            : $auto_release->due;

    }

    /**
     * @param $item
     * @return array
     * @throws Exception
     */
    public function timingAndAfterArr($item): array
    {

        $before_after_pos = strpos($item, 'after');
        $timing = substr($item, 0, $before_after_pos);
        $timing = rtrim($timing);
        if (strpos($item, 'due date') !== false) {
            $after = 'due date';
        } else if (strpos($item, 'final submission deadline') !== false) {
            $after = 'final submission deadline';
        } else throw new Exception("The after should either be 'due date' or 'final submission deadline'");

        return compact('timing', 'after');
    }

    /**
     * @param array $data
     * @param string $type
     * @param int $type_id
     * @param string $assessment_type
     * @return array
     */
    public function handleUpdateOrCreate(array $data, string $type, int $type_id, string $assessment_type): array
    {
        $auto_release_data = [];
        $auto_releases = ['auto_release_shown',
            'auto_release_show_scores',
            'auto_release_solutions_released',
            'auto_release_students_can_view_assignment_statistics'];
        foreach ($auto_releases as $auto_release) {
            $auto_release_data[str_replace('auto_release_', '', $auto_release)] = null;
        }
        $auto_release_afters = [
            'auto_release_show_scores_after',
            'auto_release_solutions_released_after',
            'auto_release_students_can_view_assignment_statistics_after'];
        foreach ($auto_release_afters as $auto_release_after) {
            $auto_release_data[str_replace('auto_release_', '', $auto_release_after)] = null;
        }
        foreach ($auto_release_afters as $auto_release_after) {
            $auto_release = str_replace('_after', '', $auto_release_after);
            if ($data['late_policy'] === 'not accepted') {
                $auto_release_data[str_replace('auto_release_', '', $auto_release_after)] = isset($data[$auto_release])
                    ? 'due date'
                    : null;
            } else {
                $auto_release_data[str_replace('auto_release_', '', $auto_release_after)] = isset($data[$auto_release])
                    ? $data[$auto_release_after]
                    : null;
            }
            unset($data[$auto_release_after]);
        }
        foreach ($auto_releases as $auto_release) {
            if (isset($data[$auto_release])) {
                $auto_release_data[str_replace('auto_release_', '', $auto_release)] = $data[$auto_release];
            }
            unset($data[$auto_release]);
        }

        if ($type === 'assignment') {
            if ($assessment_type === 'real time') {
                $auto_release_data['show_scores'] = null;
                $auto_release_data['show_scores_after'] = null;
            }
            $assignment = Assignment::find($type_id);
            if ($assignment->assessment_type === 'clicker') {
                $this->where('type', 'assignment')->where('type_id', $type_id)->delete();
                return $data;
            }
        }
        $autoRelease = $this->where('type', $type)->where('type_id', $type_id)->first();
        $delete = true;
        foreach ($auto_release_data as $value) {
            if ($value) {
                $delete = false;
            }
        }
        if ($delete) {
            $this->where('type', $type)->where('type_id', $type_id)->delete();
        } else {
            if ($autoRelease) {
                $autoRelease->update($auto_release_data);
            } else {
                $auto_release_data['type'] = $type;
                $auto_release_data['type_id'] = $type_id;
                DB::table('auto_releases')->insert($auto_release_data);
            }
        }
        return $data;
    }

    /**
     * @param int $assignment_id
     * @param string $property
     * @param $remove_auto_release
     * @return false
     */
    public function removeFromAssignment(int $assignment_id, string $property, $remove_auto_release): bool
    {
        $auto_release_removed = false;
        if ($remove_auto_release) {
            $auto_release = $this->where('type', 'assignment')->where('type_id', $assignment_id)->first();
            if ($auto_release) {
                $auto_release->{$property} = null;
                if ($property !== 'shown') {
                    $after = $property . "_after";
                    $auto_release->{$after} = null;
                }
                $auto_release->save();
                $auto_release_removed = true;
            }
        }
        return $auto_release_removed;
    }

}
