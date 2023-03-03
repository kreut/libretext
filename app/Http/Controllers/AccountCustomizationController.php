<?php

namespace App\Http\Controllers;

use App\AccountCustomization;
use App\Exceptions\Handler;
use App\Http\Requests\UpdateAccountCustomizationRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AccountCustomizationController extends Controller
{
    /**
     * @param Request $request
     * @param AccountCustomization $accountCustomization
     * @return array
     * @throws Exception
     */
    public function show(Request $request, AccountCustomization $accountCustomization): array
    {
        try {
            $response['type'] = 'error';
            $account_customizations_by_user = $accountCustomization->where('user_id', $request->user()->id)->first();
            $account_customizations = ['case_study_notes' => 0];
            if ($account_customizations_by_user) {
                foreach ($account_customizations_by_user->toArray() as $key => $value) {
                    $account_customizations[$key] = $value;
                }
            }
            $response['account_customizations'] = $account_customizations;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get your account customizations.  Please try again or contact support for assistance.";
        }
        return $response;
    }

    /**
     * @param UpdateAccountCustomizationRequest $request
     * @param AccountCustomization $accountCustomization
     * @return array
     * @throws Exception
     */
    public function update(UpdateAccountCustomizationRequest $request, AccountCustomization $accountCustomization): array
    {
        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('update', $accountCustomization);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $data = $request->validated();
            DB::table('account_customizations')
                ->updateOrInsert(['user_id'=> $request->user()->id], ['case_study_notes' => $data['case_study_notes']]);
            $response['type'] = 'info';
            $response['message'] = "Your account customizations have been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update your account customizations.  Please try again or contact support for assistance.";
        }
        return $response;

    }
}
