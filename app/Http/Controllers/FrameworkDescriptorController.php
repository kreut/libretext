<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\FrameworkDescriptor;
use App\FrameworkLevel;
use App\Http\Requests\FrameworkDescriptorRequest;
use App\Http\Requests\UpdateDescriptorRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Exception;
use Illuminate\Http\Request;


class FrameworkDescriptorController extends Controller
{
    public function destroy(FrameworkDescriptor $frameworkDescriptor)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', $frameworkDescriptor);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $descriptor_message = $frameworkDescriptor->descriptor;
            DB::beginTransaction();
            DB::table('framework_item_question')
                ->where('framework_item_type', 'descriptor')
                ->where('framework_item_id', $frameworkDescriptor->id)
                ->delete();
            DB::table('framework_level_framework_descriptor')
                ->where('framework_descriptor_id', $frameworkDescriptor->id)
                ->delete();
            DB::table('framework_descriptors')->where('id', $frameworkDescriptor->id)->delete();
            DB::commit();
            $response['type'] = 'info';
            $response['message'] = "'$descriptor_message' has been removed from your framework.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the framework descriptor.  Please try again or contact us for assistance.";

        }
        return $response;


    }

    /**
     * @param Request $request
     * @param FrameworkDescriptor $frameworkDescriptor
     * @param FrameworkLevel $frameworkLevel
     * @return array
     * @throws Exception
     */
    public function move(Request             $request,
                         FrameworkDescriptor $frameworkDescriptor,
                         FrameworkLevel      $frameworkLevel): array
    {

        $response['type'] = 'error';
        $level_from_id = $request->level_from_id;
        $level_to_id = $request->level_to_id;
        $descriptor_id = $request->descriptor_id;
        $authorized = Gate::inspect('move', [$frameworkDescriptor, $level_from_id, $level_to_id, $descriptor_id]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $framework_level_framework_descriptor = DB::table('framework_level_framework_descriptor')
                ->where('framework_level_id', $level_from_id)
                ->where('framework_descriptor_id', $descriptor_id)
                ->first();
            DB::table('framework_level_framework_descriptor')
                ->where('id', $framework_level_framework_descriptor->id)
                ->update(['framework_level_id' => $level_to_id, 'updated_at' => now()]);
            $level_to = $frameworkLevel->find($level_to_id)->title;
            $descriptor = FrameworkDescriptor::find($descriptor_id)->descriptor;
            $response['message'] = "The descriptor '$descriptor' has been moved to $level_to.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error moving the framework descriptor.  Please try again or contact us for assistance.";

        }
        return $response;

    }

    /**
     * @param FrameworkDescriptorRequest $request
     * @param FrameworkDescriptor $frameworkDescriptor
     * @return array
     * @throws Exception
     */
    public function update(FrameworkDescriptorRequest $request, FrameworkDescriptor $frameworkDescriptor): array

    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $frameworkDescriptor);

        $current_descriptor = $frameworkDescriptor->descriptor;
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $data = $request->validated();
        try {
            $current_descriptors =
                DB::table('framework_descriptors')
                    ->join('framework_level_framework_descriptor', 'framework_descriptors.id', '=', 'framework_level_framework_descriptor.framework_descriptor_id')
                    ->join('framework_levels', 'framework_level_framework_descriptor.framework_level_id', '=', 'framework_levels.id')
                    ->where('framework_descriptors.id', $frameworkDescriptor->id)
                    ->select(DB::raw("LOWER(descriptor) as descriptor"))
                    ->get()
                    ->pluck('descriptor')
                    ->toArray();
            if (!in_array(strtolower($data['descriptor']), $current_descriptors)) {
                $frameworkDescriptor->descriptor = $data['descriptor'];
                $frameworkDescriptor->save();

            } else {
                $response['message'] = "{$data['descriptor']} already exists at this level.";
                return $response;
            }

            $response['type'] = 'success';
            $response['message'] = "'$current_descriptor' has been updated to '{$data['descriptor']}'.";
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the descriptor.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function store(FrameworkDescriptorRequest $request, FrameworkDescriptor $frameworkDescriptor): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', [$frameworkDescriptor, $request->framework_level_id]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $data = $request->validated();
        $current_descriptors = DB::table('framework_descriptors')
            ->join('framework_level_framework_descriptor', 'framework_descriptors.id', '=', 'framework_level_framework_descriptor.framework_descriptor_id')
            ->where('framework_level_id', $request->framework_level_id)
            ->select(DB::raw("LOWER(descriptor) as descriptor"))
            ->get()
            ->pluck('descriptor')
            ->toArray();

        try {
            if (!in_array(strtolower($data['descriptor']), $current_descriptors)) {
                $frameworkDescriptor->descriptor = $data['descriptor'];
                $frameworkDescriptor->save();
                DB::table('framework_level_framework_descriptor')->insert([
                    'framework_level_id' => $request->framework_level_id,
                    'framework_descriptor_id' => $frameworkDescriptor->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $response['message'] = "{$data['descriptor']} already exists at this level.";
                return $response;
            }

            $response['type'] = 'success';
            $response['message'] = "The descriptor has been added.";
        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error adding the framework descriptors.  Please try again or contact us for assistance.";
        }
        return $response;


    }
}
