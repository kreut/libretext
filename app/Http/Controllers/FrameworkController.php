<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Framework;
use App\FrameworkLevel;
use App\Http\Requests\StoreFrameworkRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FrameworkController extends Controller
{
    /**
     * @param Framework $framework
     * @param FrameworkLevel $frameworkLevel
     * @return array|StreamedResponse
     * @throws Exception
     */
    public function export(Framework $framework, FrameworkLevel $frameworkLevel)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('export', $framework);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $rows = [];
            $level_1s = $frameworkLevel->getByLevel($framework->id, 1);
            $level_2s = $frameworkLevel->getByLevel($framework->id, 2);
            $level_3s = $frameworkLevel->getByLevel($framework->id, 3);
            $framework_levels = $frameworkLevel->where('framework_id', $framework->id)->get();
            $framework_level_ids = [];
            foreach ($framework_levels as $framework_level) {
                $row = [];
                switch ($framework_level->level) {
                    case(1):
                        $row = ['level_1' => $framework_level->title,
                            'level_1_order' => $framework_level->order,
                            'level_2' => '',
                            'level_2_order' => 0,
                            'level_3' => '',
                            'level_3_order' => 0,
                            'level_4' => '',
                            'level_4_order' => 0];
                        break;
                    case(2):
                        $level_1_id = $framework_level->parent_id;
                        $row = ['level_1' => $level_1s[$level_1_id]->title,
                            'level_1_order' => $level_1s[$level_1_id]->order,
                            'level_2' => $framework_level->title,
                            'level_2_order' => $framework_level->order,
                            'level_3' => '',
                            'level_3_order' => 0,
                            'level_4' => '',
                            'level_4_order' => 0];
                        break;
                    case(3):
                        $level_2_id = $framework_level->parent_id;
                        $level_1_id = $level_2s[$level_2_id]->parent_id;
                        $row = ['level_1' => $level_1s[$level_1_id]->title,
                            'level_1_order' => $level_1s[$level_1_id]->order,
                            'level_2' => $level_2s[$level_2_id]->title,
                            'level_2_order' => $level_2s[$level_2_id]->order,
                            'level_3' => $framework_level->title,
                            'level_3_order' => $framework_level->order,
                            'level_4' => '',
                            'level_4_order' => 0];
                        break;
                    case(4):
                        $level_3_id = $framework_level->parent_id;
                        $level_2_id = $level_3s[$level_3_id]->parent_id;
                        $level_1_id = $level_2s[$level_2_id]->parent_id;
                        $row = [
                            'level_1' => $level_1s[$level_1_id]->title,
                            'level_1_order' => $level_1s[$level_1_id]->order,
                            'level_2' => $level_2s[$level_2_id]->title,
                            'level_2_order' => $level_2s[$level_2_id]->order,
                            'level_3' => $level_3s[$level_3_id]->title,
                            'level_3_order' => $level_3s[$level_3_id]->order,
                            'level_4' => $framework_level->title,
                            'level_4_order' => $framework_level->order];
                        break;
                }
                $row['id'] = $framework_level->id;
                $framework_level_ids[] = $framework_level->id;
                $rows[] = $row;
            }
            array_multisort(array_column($rows, 'level_1_order'), SORT_ASC,
                array_column($rows, 'level_2_order'), SORT_ASC,
                array_column($rows, 'level_3_order'), SORT_ASC,
                array_column($rows, 'level_4_order'), SORT_ASC,
                $rows);

            ///now that it's sorted, let's remove any items that are "empty"
            /// level_1
            ///   level_2
            ///
            /// will produce [level_1, [level_1,level_2]] but we just need [[level_1,level_2]]
            $level_1s_with_sub_levels = [];
            $level_2s_with_sub_levels = [];
            $level_3s_with_sub_levels = [];
            foreach ($rows as $key => $row) {
                if ($row['level_2']) {
                    $level_1s_with_sub_levels[] = $row['level_1'];
                }
                if ($row['level_3']) {
                    $level_2s_with_sub_levels[] = [[$row['level_1'], $row['level_2']]];
                }
                if ($row['level_4']) {
                    $level_3s_with_sub_levels[] = [[$row['level_1'], $row['level_2'], $row['level_3']]];
                }
                foreach ($row as $item => $value) {
                    if (strpos($item, '_order') !== false) {
                        unset($rows[$key][$item]);
                    }
                }
            }
            $descriptors = DB::table('framework_descriptors')
                ->join('framework_level_framework_descriptor',
                    'framework_descriptors.id', '=',
                    'framework_level_framework_descriptor.framework_descriptor_id')
                ->whereIn('framework_level_framework_descriptor.framework_level_id', $framework_level_ids)
                ->get();
            $descriptors_by_framework_level = [];
            $framework_levels_with_descriptors = [];
            foreach ($descriptors as $value) {
                $framework_levels_with_descriptors[] = $value->framework_level_id;
                $descriptors_by_framework_level[$value->framework_level_id][] = $value->descriptor;
            }

            //remove if empty and has sub-levels expressed
            foreach ($rows as $key => $row) {
                if (!in_array($row['id'], $framework_levels_with_descriptors)) {
                    ///potentially remove if it has no descriptors at that level
                    if (in_array($row['level_1'], $level_1s_with_sub_levels) && !$row['level_2']) {
                        unset($rows[$key]);
                    }
                    if (in_array([$row['level_1'], $row['level_2']], $level_2s_with_sub_levels) && !$row['level_3']) {
                        unset($rows[$key]);
                    }
                    if (in_array([$row['level_1'], $row['level_2'], $row['level_3']], $level_3s_with_sub_levels) && !$row['level_4']) {
                        unset($rows[$key]);
                    }
                }
            }
            $rows = array_values($rows);
            //add the descriptors
            $rows_with_descriptors = [];
            foreach ($rows as $row) {
                $row['descriptor'] = '';
                if (isset($descriptors_by_framework_level[$row['id']])) {
                    foreach ($descriptors_by_framework_level[$row['id']] as $descriptor) {
                        $row['descriptor'] = $descriptor;
                        $rows_with_descriptors[] = $row;
                    }
                } else {
                    $rows_with_descriptors[] = $row;
                }
            }
            //create the csv
            $rows = [['Level 1', 'Level 2', 'Level 3', 'Level 4', 'Descriptor']];
            foreach ($rows_with_descriptors as $row) {
                $rows[] = [$row['level_1'], $row['level_2'], $row['level_3'], $row['level_4'], $row['descriptor']];
            }
            $efs_dir = '/mnt/local/';
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();

            if (!is_dir("{$storage_path}frameworks")) {
                mkdir("{$storage_path}frameworks");
            }
            $file_name = "frameworks/$framework->title-$framework->author-framework.csv";
            $file = "$storage_path$file_name";
            $fp = fopen($file, 'w');
            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);

            Storage::disk('s3')->put($file_name, "\xEF\xBB\xBF" . file_get_contents($file));
            return Storage::disk('s3')->download($file_name);

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error exporting the framework.  Please try again or contact us for assistance.";
        }

        return $response;


    }

    function destroy(Framework $framework, int $deleteProperties, FrameworkLevel $frameworkLevel): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', $framework);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $framework_title = $framework->title;
            $framework_levels = $frameworkLevel->where('framework_id', $framework->id)->get();
            DB::beginTransaction();
            foreach ($framework_levels as $framework_level) {
                $framework_level_framework_descriptors = DB::table('framework_level_framework_descriptor')
                    ->where('framework_level_id', $framework_level->id)
                    ->get();

                //unsync all descriptors tied to questions
                ///get rid of all descriptors tied to the levels
                /// unsync all levels tied to the questions

                foreach ($framework_level_framework_descriptors as $framework_level_framework_descriptor) {
                    DB::table('framework_item_question')
                        ->where('framework_item_type', 'descriptor')
                        ->where('framework_item_id', $framework_level_framework_descriptor->framework_descriptor_id)
                        ->delete();
                    DB::table('framework_level_framework_descriptor')
                        ->where('framework_descriptor_id', $framework_level_framework_descriptor->framework_descriptor_id)
                        ->delete();

                    DB::table('framework_item_question')
                        ->where('framework_item_type', 'level')
                        ->where('framework_item_id', $framework_level_framework_descriptor->framework_level_id)
                        ->delete();

                }
                $framework_level->delete();
            }
            if ($deleteProperties) {
                $message = "$framework_title has been deleted.";
                $framework->delete();
            } else {
                $message = "All of the framework's levels and descriptors have been deleted.";
            }

            $response['type'] = 'info';
            $response['message'] = $message;
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the framework.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param StoreFrameworkRequest $request
     * @param Framework $framework
     * @return array
     * @throws Exception
     */
    public
    function update(StoreFrameworkRequest $request, Framework $framework): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $framework);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();
        $old_title = $framework->title;
        try {
            $framework->setProperties($request, $data);
            $framework->save();
            $response['type'] = 'success';
            $response['message'] = "$old_title has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the framework properties.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param StoreFrameworkRequest $request
     * @param Framework $framework
     * @return array
     * @throws Exception
     */
    public
    function store(StoreFrameworkRequest $request, Framework $framework): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $framework);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $data = $request->validated();
        try {
            $framework->setProperties($request, $data);
            $framework->save();
            $response['type'] = 'success';
            $response['message'] = "{$data['title']} has been created.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the framework.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     * @param Framework $framework
     * @return array
     * @throws Exception
     */
    public
    function index(Framework $framework): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $framework);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $response['frameworks'] = $framework->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the frameworks.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Framework $framework
     * @return array
     * @throws Exception
     */
    public
    function show(Request $request, Framework $framework): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('show', $framework);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $framework_level_ids = [];
        $properties = DB::table('frameworks')->where('id', $framework->id)->first();
        $framework_levels = DB::table('framework_levels')
            ->where('framework_id', $framework->id)
            ->orderBy('order')
            ->get();

        foreach ($framework_levels as $framework_level) {
            $framework_level_ids[] = $framework_level->id;
        }

        $framework_descriptors = DB::table('framework_level_framework_descriptor')
            ->join('framework_descriptors', 'framework_level_framework_descriptor.framework_descriptor_id', '=', 'framework_descriptors.id')
            ->whereIn('framework_level_id', $framework_level_ids)
            ->select('framework_descriptors.id',
                'descriptor',
                'framework_level_id')
            ->get();
        try {
            $response['properties'] = $properties;
            $response['framework_levels'] = $framework_levels;
            $response['descriptors'] = $framework_descriptors;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the framework and learning objectives information.  Please try again or contact us for assistance.";
        }
        return $response;


    }
}
