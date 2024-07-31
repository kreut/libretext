<?php

namespace App\Http\Controllers;

use App\Course;
use App\DataShop;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Metrics;
use App\Question;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MetricsController extends Controller
{
    /**
     * @param Metrics $metrics
     * @param int $download
     * @return array|void
     * @throws Exception
     */
    public function cellData(Metrics $metrics, int $download)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('cellData', $metrics);

        $rows = [];
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $cell_data = Cache::get('cell_data');
            if (!$cell_data){
                $response['type'] = 'error';
                $response['message'] = 'Could not get the cell data from the cache.';
                return $response;
            }
            $response['type'] = 'success';
            $response['cell_data'] = $cell_data;
            if ($download) {
                $columns = ['Course Name', 'Discipline','Term', 'School Name', 'Instructor Name', 'Number of Enrolled Students'];
                $rows[0] = $columns;
                $keys = ['course_name', 'discipline','term', 'school_name', 'instructor_name', 'number_of_enrolled_students'];
                foreach ($cell_data as $data) {
                    $values = [];
                    foreach ($keys as $key) {
                        $values[] = $data->{$key};
                    }
                    $rows[] = $values;
                }
                $date = Carbon::now()->format('Y-m-d');
                Helper::arrayToCsvDownload($rows, "cell-data-$date");
            }


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the Cell Data.  Please try again.";
        }

        if (!$download) {
            return $response;
        }

    }

    /**
     * @param Metrics $metrics
     * @param int $download
     * @return array|void
     * @throws Exception
     */
    public function index(Metrics $metrics, int $download)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $metrics);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $metrics = Cache::get('metrics');
            if (!$metrics ){
                $response['type'] = 'error';
                $response['message'] = 'Could not get the metrics from the cache.';
                return $response;
            }
            $response['metrics'] = $metrics;
            $response['type'] = 'success';
            if ($download) {
                $rows = [];
                foreach ($metrics as $key => $metric) {
                    $rows[] = [ucwords(str_replace('_', ' ', $key)), $metric];
                }

                $date = Carbon::now()->format('Y-m-d');
                Helper::arrayToCsvDownload($rows, "metrics-$date");

            }

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to retrieve the metrics.  Please try again.";

        }
        if (!$download) {
            return $response;
        }
    }


}
