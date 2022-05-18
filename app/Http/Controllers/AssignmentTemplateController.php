<?php

namespace App\Http\Controllers;

use App\AssignmentTemplate;
use App\Exceptions\Handler;
use App\Http\Requests\StoreAssignmentProperties;
use App\Traits\AssignmentProperties;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AssignmentTemplateController extends Controller
{
    use AssignmentProperties;

    public function index(AssignmentTemplate $assignmentTemplate)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $assignmentTemplate);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $response['assignment_templates'] = $assignmentTemplate
                ->where('user_id', auth()->user()->id)
                ->orderBy('order')
                ->get();
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment templates.";
        }
        return $response;

    }

    public
    function store(StoreAssignmentProperties $request, AssignmentTemplate $assignmentTemplate): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('store', $assignmentTemplate);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $data = $request->validated();
            $assignment_template_info = $this->getAssignmentProperties($data, $request);
            $assignment_template_info['template_name'] = $data['template_name'];
            $assignment_template_info['template_description'] = $data['template_description'];
            $assignment_template_info['assign_to_everyone'] = $data['assign_to_everyone'];
            $assignment_template_info['user_id'] = $request->user()->id;
            $assignment_template_info['order'] = 1 + $assignmentTemplate->where('user_id', $request->user()->id)->count();
            AssignmentTemplate::create($assignment_template_info);
            $response['type'] = 'success';
            $response['message'] = "The assignment template <strong>{$data['template_name']}</strong> has been created.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the assignment template <strong>{$data['template_name']}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    public function show(AssignmentTemplate $assignmentTemplate)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('show', $assignmentTemplate);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $response['assignment_template'] = $assignmentTemplate;
            $response['message'] = "The assignment properties have been populated with $assignmentTemplate->template_name";
            $response['type'] = 'info';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment template.";
        }
        return $response;
    }

    public function destroy(Request $request, AssignmentTemplate $assignmentTemplate)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', $assignmentTemplate);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignmentTemplate->delete();
            $assignment_templates = $assignmentTemplate
                ->where('user_id', $request->user()->id)
                ->orderBy('order')
                ->get();
            foreach ($assignment_templates as $key => $assignment_template) {
                $assignment_template->order = $key + 1;
                $assignment_template->save();
            }
            $response['type'] = 'info';
            $response['message'] = "$assignmentTemplate->template_name has been deleted.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the assignment template.";
        }
        return $response;
    }

    public function update(StoreAssignmentProperties $request, AssignmentTemplate $assignmentTemplate)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignmentTemplate);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();

        try {
            $data_to_update = $this->getDataToUpdate($data, $request);
            foreach ($data_to_update as $key => $value) {
                $data[$key] = $value;
            }
            $data['late_deduction_application_period'] = $this->getLateDeductionApplicationPeriod($request, $data);
            $assignmentTemplate->update($data);
            $response['type'] = 'success';
            $response['message'] = "The assignment template has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the assignment template.";
        }
        return $response;
    }

    public function copy(AssignmentTemplate $assignmentTemplate)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('copy', $assignmentTemplate);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $new_assignment_template = $assignmentTemplate->replicate();
            $new_assignment_template->template_name = "$assignmentTemplate->template_name copy";
            $new_assignment_template->order = $assignmentTemplate->where('user_id', request()->user()->id)->count() + 1;
            $new_assignment_template->save();
            $response['type'] = 'success';
            $response['message'] = "The assignment template has been copied.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error copying the assignment template.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param AssignmentTemplate $assignmentTemplate
     * @return array
     * @throws Exception
     */
    public function order(Request $request, AssignmentTemplate $assignmentTemplate): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('order', [$assignmentTemplate, $request->ordered_assignment_templates]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            DB::beginTransaction();
            foreach ($request->ordered_assignment_templates as $key => $assignment_template_id) {
                DB::table('assignment_templates')
                    ->where('id', $assignment_template_id)//validation step!
                    ->update(['order' => $key + 1]);
            }
            DB::commit();
            $response['message'] = 'Your assignment templates have been re-ordered.';
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error re-ordering your assignment templates.  Please try again or contact us for assistance.";
        }
        return $response;


    }
}
