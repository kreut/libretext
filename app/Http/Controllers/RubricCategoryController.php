<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Http\Requests\StoreRubricCategoryRequest;
use App\RubricCategory;
use App\RubricCategorySubmission;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class RubricCategoryController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param RubricCategory $rubricCategory
     * @return array
     * @throws Exception
     */
    public function order(Request $request,
                                          Assignment $assignment,
                                          RubricCategory $rubricCategory): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('order', [$rubricCategory, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::beginTransaction();
            $rubric_categories = $request->ordered_rubric_categories;
            foreach ($rubric_categories as $key => $rubric_category_id) {
                DB::table('rubric_categories')
                    ->where('id', $rubric_category_id)
                    ->update(['order' => $key + 1]);
            }
            DB::commit();
            $response['message'] = "The rubric categories have been re-ordered.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the order.  Please try again.";
        }
        return $response;
    }

    /**
     * @param StoreRubricCategoryRequest $request
     * @param RubricCategory $rubricCategory
     * @return array
     * @throws Exception
     */
    public function store(StoreRubricCategoryRequest $request, RubricCategory $rubricCategory): array
    {
        $response['type'] = 'error';
        $assignment = Assignment::find($request->assignment_id);

        $authorized = Gate::inspect('store', [$rubricCategory, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $rubricCategories = $assignment->rubricCategories;
            $data = $request->validated();
            $data['assignment_id'] = $request->assignment_id;
            //Log::info($rubricCategory->where('assignment_id', $request->assignment_id)->count() );
            $data['order'] = $rubricCategories->isEmpty() ? 1 : $rubricCategory->where('assignment_id', $request->assignment_id)->count() + 1;
            $rubricCategory->create($data);
            $response['message'] = "The rubric category has been created.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the rubric category.  Please try again.";
        }
        return $response;
    }

    public function update(StoreRubricCategoryRequest $request, RubricCategory $rubricCategory): array
    {
        $response['type'] = 'error';
        try {

            $authorized = Gate::inspect('update', $rubricCategory);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $data = $request->validated();
            $rubricCategory->update($data);
            $response['message'] = 'The rubric has been updated.';
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the rubric.  Please try again.";
        }
        return $response;
    }

    /**
     * @param RubricCategory $rubricCategory
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @return array
     * @throws Exception
     */
    public function destroy(RubricCategory $rubricCategory, RubricCategorySubmission $rubricCategorySubmission): array
    {
        $response['type'] = 'error';
        try {

            $authorized = Gate::inspect('destroy', $rubricCategory);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            DB::beginTransaction();
            $assignment = Assignment::find($rubricCategory->assignment_id);
            $rubricCategorySubmission->where('rubric_category_id', $rubricCategory->id)->delete();
            $rubricCategory->delete();
            $rubricCategories = $assignment->rubricCategories;
            foreach ($rubricCategories as $key => $rubric_category) {
                $rubric_category->order = $key + 1;
                $rubric_category->save();
            }
            $response['message'] = 'The rubric category has been deleted.';
            $response['type'] = 'info';
            DB::commit();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the rubric category.  Please try again.";
        }
        return $response;
    }
}
