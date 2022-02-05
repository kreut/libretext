<?php

namespace App\Http\Controllers\Settings;

use App\Exceptions\Handler;
use App\Password;
use App\Rules\IsValidPassword;
use \Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    public function update(Request $request): array
    {
        $this->validate($request, [
            'password' => ['required','confirmed', new IsValidPassword()]
        ]);
        $response['type'] = 'error';

        $authorized = Gate::inspect('update', new Password());

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $request->user()->update([
                'password' => bcrypt($request->password),
            ]);
            $response['type'] = 'success';
            $response['message'] = 'Your password has been updated.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating your password.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;
    }
}
