<?php

namespace App\Http\Controllers;


use App\Exceptions\Handler;
use App\Framework;
use App\FrameworkDescriptor;
use App\FrameworkLevel;
use App\Helpers\Helper;
use App\Http\Requests\StoreFrameworkLevelRequest;
use App\Http\Requests\UpdateFrameworkLevelRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class FrameworkLevelController extends Controller
{
    /**
     * @param Request $request
     * @param FrameworkLevel $frameworkLevel
     * @param string $desciptorAction
     * @param int $levelToMoveTo
     * @param FrameworkDescriptor $frameworkDescriptor
     * @return array
     * @throws Exception
     */
    public function destroy(Request             $request,
                            FrameworkLevel      $frameworkLevel,
                            string              $desciptorAction,
                            int                 $levelToMoveTo,
                            FrameworkDescriptor $frameworkDescriptor): array

    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', [$frameworkLevel, $frameworkLevel->id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $framework_level_and_children_ids = $frameworkLevel->getAllChildren($frameworkLevel->framework_id);
        $framework_level_and_children_ids[] = $frameworkLevel->id;
        $synced_descriptor_ids = DB::table('framework_level_framework_descriptor')
            ->whereIn('framework_level_id', $framework_level_and_children_ids)
            ->get('framework_descriptor_id')
            ->pluck('framework_descriptor_id')
            ->toArray();
        DB::beginTransaction();
        try {
            switch ($desciptorAction) {
                case('delete'):
                    DB::table('framework_item_question')
                        ->whereIn('framework_item_id', $synced_descriptor_ids)
                        ->where('framework_item_type', 'descriptor')
                        ->delete();
                    DB::table('framework_level_framework_descriptor')
                        ->whereIn('framework_level_id', $framework_level_and_children_ids)
                        ->delete();
                    $frameworkDescriptor->whereIn('id', $synced_descriptor_ids)->delete();
                    $message = "$frameworkLevel->title and all associated descriptors have been deleted.";
                    break;
                case('move'):
                    $framework_level_to_move_to = DB::table('framework_levels')
                        ->join('frameworks', 'framework_levels.framework_id', '=', 'frameworks.id')
                        ->where('framework_levels.id', $levelToMoveTo)
                        ->where('user_id', $request->user()->id)
                        ->first();
                    if (!$framework_level_to_move_to) {
                        $response['message'] = "You cannot move the descriptors to that framework level.";
                        return $response;
                    }

                    $invalid_move_tos = $frameworkLevel->getAllChildren($frameworkLevel->framework_id);
                    $invalid_move_tos [] = $frameworkLevel->id;
                    if (in_array($levelToMoveTo, $invalid_move_tos)) {
                        $response['message'] = "You cannot move descriptors to a level that will be deleted.";
                        return $response;
                    }

                    if ($levelToMoveTo === $frameworkLevel->id) {
                        $response['message'] = "You cannot move the descriptors to the framework level that you are about to delete.";
                    }
                    DB::table('framework_level_framework_descriptor')
                        ->whereIn('framework_descriptor_id', $synced_descriptor_ids)
                        ->update(['framework_level_id' => $levelToMoveTo]);
                    $message = "'$frameworkLevel->title' has been deleted and the descriptors have been moved to  '$framework_level_to_move_to->title'.";
                    break;
                case('none-exist');
                    $message = "'$frameworkLevel->title' has been deleted.";
                    break;
                default:
                    throw new Exception ("$desciptorAction is not a valid descriptor action.");
            }
            $framework_id = $frameworkLevel->framework_id;
            $parent_id = $frameworkLevel->parent_id;
            $frameworkLevel->whereIn('id', $framework_level_and_children_ids)->delete();


            $framework_levels = $parent_id !== 0
                ? $frameworkLevel
                    ->where('framework_id', $framework_id)
                    ->where('parent_id', $parent_id)
                    ->orderBy('order', 'ASC')
                    ->orderBy('updated_at', 'ASC')
                    ->get()
                : $frameworkLevel
                    ->where('framework_id', $framework_id)
                    ->where('parent_id', 0)
                    ->orderBy('order', 'ASC')
                    ->orderBy('updated_at', 'ASC')
                    ->get();
            foreach ($framework_levels as $key => $framework_level) {
                $framework_level->order = $key + 1;
                $framework_level->save();
            }


            $response['type'] = 'info';
            $response['message'] = $message;
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to delete this framework level. Please try again or contact us for assistance.";

        }
        return $response;

    }

    /**
     * @param FrameworkLevel $frameworkLevel
     * @return array
     * @throws Exception
     */
    public function getAllChildren(FrameworkLevel $frameworkLevel): array
    {
        $authorized = Gate::inspect('getAllChildren', [$frameworkLevel, $frameworkLevel->id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['all_children'] = $frameworkLevel->getAllChildren($frameworkLevel->framework_id);
            $response['type'] = 'success';
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get all of the children. Please try again or contact us for assistance.";

        }
        return $response;

    }

    /**
     * @param FrameworkLevel $frameworkLevel
     * @return array
     * @throws Exception
     */
    public function getFrameworkLevelsWithSameParent(FrameworkLevel $frameworkLevel): array
    {
        $authorized = Gate::inspect('getFrameworkLevelsWithSameParent', $frameworkLevel);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $parent_id = $frameworkLevel->parent_id;
            $levels_with_same_parent = $frameworkLevel->where('framework_id', $frameworkLevel->framework_id)
                ->where('parent_id', $parent_id)
                ->orderBy('order')->get();

            $response['type'] = 'success';
            $response['levels_with_same_parent'] = $levels_with_same_parent;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the number in the current level. Please try again or contact us for assistance.";

        }
        return $response;

    }

    /**
     * @param Request $request
     * @param FrameworkLevel $frameworkLevel
     * @return array
     * @throws Exception
     */
    public function changePosition(Request $request, FrameworkLevel $frameworkLevel): array
    {
        $level_id = $request->level_id;
        $position = $request->position;
        $response['type'] = 'error';
        $authorized = Gate::inspect('changePosition', [$frameworkLevel, $level_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            DB::beginTransaction();
            $framework_level = $frameworkLevel->find($level_id);
            $max_position = $frameworkLevel
                ->where('framework_id', $framework_level->framework_id)
                ->where('parent_id', $framework_level->parent_id)
                ->max('order');
            $position = Max($position, 1);
            $position = Min($max_position, $position);
            $framework_level->order = $position;
            $framework_level->save();

            $framework_levels = $frameworkLevel
                ->where('framework_id', $framework_level->framework_id)
                ->where('parent_id', $framework_level->parent_id)
                ->orderBy('order', 'ASC')
                ->orderBy('updated_at', 'ASC')
                ->get();
            foreach ($framework_levels as $key => $framework_level) {
                $framework_level->order = $key + 1;
                $framework_level->save();
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The position has been updated.';
        } catch (Exception $e) {

            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to change the position.  Please try again or contact us if you are still having issues.";

        }
        return $response;

    }

    /**
     * @param Request $request
     * @param FrameworkLevel $frameworkLevel
     * @return array
     * @throws Exception
     */
    public function moveLevel(Request $request, FrameworkLevel $frameworkLevel): array

    {
        $level_from_id = $request->level_from_id;
        $level_to_id = $request->move_to_option_is_top_level ? 0 : $request->level_to_id;
        $response['type'] = 'error';
        $authorized = Gate::inspect('moveLevel', [$frameworkLevel, $level_from_id, $level_to_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $level_from = $frameworkLevel->find($level_from_id);
            $level_to = $level_to_id ? $frameworkLevel->find($level_to_id) : null;
            $level_difference = $level_to ? $level_from->level - $level_to->level : $level_from->level;
            $framework = Framework::find($level_from->framework_id);

            $children_ids = $level_from->getAllChildren($framework->id);

            if ($level_difference < 0) {
                //have to check that you're not adding too many levels
                if (in_array($level_to_id, $children_ids)) {
                    $response['message'] = "You can't move lower within the same level.";
                    return $response;
                }
            }
            if ($level_to_id === $level_from_id) {
                $response['message'] = "The to and from levels are the same.";
                return $response;
            }

            $num_children_levels = $frameworkLevel->whereIn('id', $children_ids)
                ->select('level')
                ->groupBy('level')
                ->count('level');
            if ($level_to_id && ($num_children_levels + $level_to->level > 4)) {
                $response['message'] = "Your original level has $num_children_levels children levels so it can't be added to the framework at level $level_to->level. Otherwise, the total number of levels would exceed 4.";
                $response['type'] = 'error';
                return $response;
            }

            DB::beginTransaction();
            //fix the children and the orders of the new parent
            foreach ($children_ids as $child_id) {
                $child = $frameworkLevel->find($child_id);
                $parent_id = $child->parent_id;
                $child_id = $child->id;
                $max_order = $frameworkLevel->where('parent_id', $parent_id)
                    ->where('framework_id', $framework->id)
                    ->max('order');
                //keep adding to the end.
                DB::table('framework_levels')
                    ->where('id', $child_id)
                    ->update(['level' => $child->level - $level_difference + 1,
                        'order' => $max_order + 1]);
            }

            $max_order = $frameworkLevel->where('parent_id', $level_to_id)
                ->where('framework_id', $framework->id)
                ->max('order');
            $level_from->parent_id = $level_to_id;
            $level_from->level = $level_from->level - $level_difference + 1;
            $level_from->order = $max_order + 1 ?: 1;
            $level_from->save();

            //clean up all the orders (some indices might be missing)
            $parent_ids = FrameworkLevel::where('framework_id', $framework->id)
                ->get('parent_id')
                ->pluck('parent_id')
                ->toArray();
            $parent_ids = array_unique($parent_ids);
            foreach ($parent_ids as $parent_id) {
                $level_tos = $frameworkLevel->where('framework_id', $framework->id)
                    ->where('parent_id', $parent_id)
                    ->orderBy('order')
                    ->get();
                foreach ($level_tos as $key => $value) {
                    $value->order = $key + 1;
                    $value->save();
                }
            }

            $title = $level_to_id ? "'$level_to->title'" : "the top level of the framework.";
            $response['message'] = "The framework level '$level_from->title' has been moved to $title</strong>'.";
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to move the framework levels.  Please try again or contact us if you are still having issues.";
        }

        return $response;
    }

    /**
     * @param Framework $framework
     * @param FrameworkLevel $frameworkLevel
     * @param int $parent_id
     * @return array
     * @throws Exception
     */
    public
    function getFrameworkLevelChildren(Framework      $framework,
                                       int            $parent_id,
                                       FrameworkLevel $frameworkLevel): array
    {

        $response['type'] = 'error';
        $framework_id = $framework->id;

        $authorized = Gate::inspect('getFrameworkLevelChildren', [$frameworkLevel, $framework->id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $framework_level_children = $frameworkLevel->getChildren($framework_id, $parent_id);
            $response['type'] = 'success';
            $response['framework_level_children'] = $framework_level_children;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "Unable to get the framework level children.";
        }

        return $response;

    }


    /**
     * @param Request $request
     * @param FrameworkLevel $frameworkLevel
     * @param FrameworkDescriptor $frameworkDescriptor
     * @return array
     * @throws Exception
     */
    public
    function storeWithDescriptors(Request $request, FrameworkLevel $frameworkLevel, FrameworkDescriptor $frameworkDescriptor): array
    {
        $response['type'] = 'error';
        $framework_id = $request->framework_id;

        $authorized = Gate::inspect('storeWithDescriptors', [$frameworkLevel, $framework_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $titles = [trim($request->title_1),
                trim($request->title_2),
                trim($request->title_3),
                trim($request->title_4)];
            $descriptor = trim($request->descriptor);
            $max_level = 1;
            foreach ($titles as $title_key => $value) {
                if ($value) {
                    $max_level = $title_key;
                }
            }
            foreach ($titles as $title_key => $value) {
                if (!$value && $title_key < $max_level) {
                    $response['message'] = "You have an empty level.";
                    return $response;
                }
            }
            $parent_id = 0;
            $level_1_match = $frameworkLevel->titleMatchByLevelAndParent($framework_id, 1, $parent_id, $titles[0]);
            DB::beginTransaction();
            if (!$level_1_match) {
                foreach ([1, 2, 3, 4] as $key => $level) {
                    $parent_id = $this->_addTitle($framework_id, $level, $parent_id, $titles[$key]);
                }
            } else {
                $parent_id = $level_1_match->id;
                $level_2_match = $frameworkLevel->titleMatchByLevelAndParent($framework_id, 2, $parent_id, $titles[1]);
                if (!$level_2_match) {
                    foreach ([2, 3, 4] as $key => $level) {
                        $parent_id = $this->_addTitle($framework_id, $level, $parent_id, $titles[$key + 1]);
                    }
                } else {
                    $parent_id = $level_2_match->id;
                    $level_3_match = $frameworkLevel->titleMatchByLevelAndParent($framework_id, 3, $parent_id, $titles[2]);
                    if (!$level_3_match) {
                        foreach ([3, 4] as $key => $level) {
                            $parent_id = $this->_addTitle($framework_id, $level, $parent_id, $titles[$key + 2]);
                        }
                    } else {
                        $parent_id = $level_3_match->id;
                        $level_4_match = $frameworkLevel->titleMatchByLevelAndParent($framework_id, 4, $parent_id, $titles[3]);
                        if (!$level_4_match) {
                            $this->_addTitle($framework_id, 4, $parent_id, $titles[3]);
                        }
                    }
                }
            }

            $framework_descriptor_exists = false;
            if ($descriptor) {
                $level_1 = $frameworkLevel->getFrameworkLevelbyCurrentLevelParentAndTitle($framework_id, 1, 0, $titles[0]);
                if (!$titles[1]) {
                    $level_id = $level_1->id;
                } else {
                    $level_2 = $frameworkLevel->getFrameworkLevelbyCurrentLevelParentAndTitle($framework_id, 2, $level_1->id, $titles[1]);
                    if (!$titles[2]) {
                        $level_id = $level_2->id;
                    } else {
                        $level_3 = $frameworkLevel->getFrameworkLevelbyCurrentLevelParentAndTitle($framework_id, 3, $level_2->id, $titles[2]);
                        if (!$titles[3]) {
                            $level_id = $level_3->id;
                        } else {
                            $level_4 = $frameworkLevel->getFrameworkLevelbyCurrentLevelParentAndTitle($framework_id, 4, $level_3->id, $titles[3]);
                            if ($level_4) {
                                $level_id = $level_4->id;
                            } else {
                                throw new Exception("Could not find level for descriptors.");

                            }
                        }
                    }
                }

                $framework_descriptor_exists = DB::table('framework_descriptors')
                    ->join('framework_level_framework_descriptor', 'framework_descriptors.id', '=', 'framework_level_framework_descriptor.framework_descriptor_id')
                    ->join('framework_levels', 'framework_level_framework_descriptor.framework_level_id', '=', 'framework_levels.id')
                    ->where(DB::raw("LOWER(descriptor)"), strtolower($descriptor))
                    ->where('framework_id', $framework_id)
                    ->select('framework_descriptors.*')
                    ->first();
                if (!$framework_descriptor_exists) {
                    $frameworkDescriptor->descriptor = $descriptor;
                    $frameworkDescriptor->save();
                    $framework_descriptor_id = $frameworkDescriptor->id;
                    $data = [
                        'framework_level_id' => $level_id,
                        'framework_descriptor_id' => $framework_descriptor_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    DB::table('framework_level_framework_descriptor')->insert($data);
                }
            }
            if ($framework_descriptor_exists) {
                $response['message'] = 'Descriptor already exists in this framework.';
                $response['type'] = 'info';
            } else {
                $response['message'] = 'Added';
                $response['type'] = 'success';
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "Error: unable to add to the framework.";
        }

        return $response;


    }

    /**
     * @param Request $request
     * @param FrameworkLevel $frameworkLevel
     * @return array
     * @throws Exception
     */
    public
    function upload(Request $request, FrameworkLevel $frameworkLevel): array
    {

        $response['type'] = 'error';
        $framework_id = $request->framework_id;
        $authorized = Gate::inspect('upload', [$frameworkLevel, $framework_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $messages = [];
        try {
            if (!app()->environment('testing')) {
                if (!$request->file('framework_level_file')) {
                    $response['message'] = ['No file was selected.'];
                    return $response;
                }
                $framework_level_file = $request->file('framework_level_file')
                    ->store("framework_level_file/" . $request->user()->id, 'local');
                $csv_file = Storage::disk('local')->path($framework_level_file);

                if (!in_array($request->file('framework_level_file')->getMimetype(), ['application/x-tex', 'application/csv', 'text/plain', 'text/x-tex'])) {
                    $response['message'] = ["This is not a .csv file: {$request->file('framework_level_file')->getMimetype()} is not a valid MIME type."];
                    return $response;
                }
                $handle = fopen($csv_file, 'r');
                $header = fgetcsv($handle);
                $correct_keys = ['Level 1', 'Level 2', 'Level 3', 'Level 4', 'Descriptor'];
                if ($header !== $correct_keys) {
                    $response['message'] = ["Your headings should be: " . implode(', ', $correct_keys) . "."];
                    return $response;
                }
                fclose($handle);
                $framework_levels = Helper::csvToArray($csv_file);
                foreach ($framework_levels as $row_key => $framework_level) {
                    $max_level = 1;
                    foreach ($framework_level as $level_key => $value) {
                        if ($level_key !== 'Descriptor') {
                            if ($value) {
                                $max_level = $level_key;
                            }
                        }
                    }
                    foreach ($framework_level as $level_key => $value) {
                        if ($level_key !== 'Descriptor') {
                            if (!$value && $level_key < $max_level) {
                                $row = $row_key + 1;
                                $messages[] = "$level_key needs an entry since it is not the highest level in row $row.";
                            }
                        }
                    }
                }

            } else {
                $framework_levels = $request->csv_file_array;
            }
            if (!$framework_levels) {
                $response['message'] = ['The .csv file has no data.'];
                return $response;
            }
            if ($messages) {
                $response['message'] = $messages;
                return $response;
            }
//get rid of empty rows.
            foreach ($framework_levels as $key => $framework_level) {
                $empty = true;
                foreach ($framework_level as $value) {
                    if (trim($value)) {
                        $empty = false;
                    }
                }
                if ($empty) {
                    unset($framework_levels[$key]);
                }
            }
            $response['type'] = 'success';
            $response['framework_levels'] = array_values($framework_levels);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error uploading this framework.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param StoreFrameworkLevelRequest $request
     * @param FrameworkLevel $frameworkLevel
     * @return array
     * @throws Exception
     */
    public
    function store(StoreFrameworkLevelRequest $request, FrameworkLevel $frameworkLevel): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', [$frameworkLevel, $request->framework_id]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $level_to_add = $request->level_to_add;
        if (!in_array($level_to_add, [1, 2, 3, 4])) {
            $response['message'] = "$level_to_add is not a valid level.";
            return $response;
        }

        $framework_level_to_add = $request->parent_id ? FrameworkLevel::find($request->parent_id)->level : 0;
        if ($framework_level_to_add + 1 !== $level_to_add) {
            $response['message'] = "The framework level to add does not seem be correct.  Please contact us.";
            return $response;
        }

        $data = $request->validated();
        try {
            DB::beginTransaction();
            $frameworkLevel->level = $request->level_to_add;
            $frameworkLevel->order = $request->order ?: $frameworkLevel->getMaxOrderPlusOne($request->framework_id, $request->parent_id);
            $frameworkLevel->framework_id = $request->framework_id;
            $frameworkLevel->parent_id = $request->parent_id;
            $frameworkLevel->title = $data['title'];
            $frameworkLevel->description = $request->description;
            $frameworkLevel->save();
            $same_level_framework_levels = $frameworkLevel->where('parent_id', $request->parent_id)
                ->where('framework_id', $frameworkLevel->framework_id)
                ->orderBy('order')
                ->get();
            foreach ($same_level_framework_levels as $key => $value) {
                $value->order = $key + 1;
                $value->save();
            }
            $response['message'] = "{$data['title']} has been added at level $level_to_add.";
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the framework level.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function update(UpdateFrameworkLevelRequest $request, FrameworkLevel $frameworkLevel): array
    {
        $response['type'] = 'error';
        $framework_level_id = $request->framework_level_id;
        $authorized = Gate::inspect('update', [$frameworkLevel, $framework_level_id]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $data = $request->validated();
        try {
            $old_title = FrameworkLevel::find($framework_level_id)->title;
            $frameworkLevel->where('id', $framework_level_id)
                ->update(['title' => $data['title'], 'description' => $request->description]);
            $response['message'] = $old_title !== $data['title']
                ? "$old_title has been changed to {$data['title']}."
                : "$old_title has been updated.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the framework level.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function getTemplate(FrameworkLevel $frameworkLevel)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getTemplate', $frameworkLevel);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $efs_dir = '/mnt/local/';
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();
            $file = "{$storage_path}framework-template.csv";
            $fp = fopen($file, 'w');

            $fields = ['Level 1', 'Level 2', 'Level 3', 'Level 4', 'Descriptor'];
            fputcsv($fp, $fields);
            fclose($fp);
            Storage::disk('s3')->put("templates/framework-template.csv", file_get_contents($file));
            return Storage::disk('s3')->download("templates/framework-template.csv");
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the framework template.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    private
    function _addTitle($framework_id, $level, $parent_id, $title)
    {
        if ($title) {
            $frameworkLevel = new FrameworkLevel();
            $frameworkLevel->framework_id = $framework_id;
            $frameworkLevel->level = $level;
            $frameworkLevel->order = $frameworkLevel->getMaxOrderPlusOne($framework_id, $parent_id);
            $frameworkLevel->title = $title;
            $frameworkLevel->parent_id = $parent_id;
            $frameworkLevel->save();
            return $frameworkLevel->id;
            //TODO: Also do the descriptors......
        } else return 0;
    }
}
