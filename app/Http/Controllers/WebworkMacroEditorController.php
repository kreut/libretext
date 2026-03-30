<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\User;
use App\WebworkMacroEditor;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WebworkMacroEditorController extends Controller
{
    /**
     * List all current macro editors with their user info.
     * Admin only.
     */
    public function index(WebworkMacroEditor $webworkMacroEditor): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('index', $webworkMacroEditor);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['editors'] = WebworkMacroEditor::with(['user', 'grantedBy'])
                ->get()
                ->map(function ($editor) {
                    return [
                        'id'               => $editor->id,
                        'user_id'          => $editor->user_id,
                        'name'             => $editor->user->first_name . ' ' . $editor->user->last_name,
                        'email'            => $editor->user->email,
                        'granted_by_name'  => $editor->grantedBy->first_name . ' ' . $editor->grantedBy->last_name,
                        'created_at'       => $editor->created_at,
                    ];
                });
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the macro editors. Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * Grant a user the macro editor role.
     * Admin only.
     */
    public function store(Request $request, WebworkMacroEditor $webworkMacroEditor): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('store', $webworkMacroEditor);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $user_info = explode(' --- ', $request->user);
        $email = $user_info[1];


        try {
            $user = User::where('email', $email)->first();


            if (WebworkMacroEditor::where('user_id', $request->user_id)->exists()) {
                $response['message'] = "{$user->first_name} {$user->last_name} is already a macro editor.";
                return $response;
            }

            WebworkMacroEditor::create([
                'user_id'           => $user->id,
                'granted_by_user_id'=> $request->user()->id
            ]);

            $response['type']    = 'success';
            $response['message'] = "{$user->first_name} {$user->last_name} has been granted the macro editor role.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to grant the macro editor role. Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * Revoke a user's macro editor role.
     * Admin only.
     */
    public function destroy(WebworkMacroEditor $webworkMacroEditor): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('destroy', $webworkMacroEditor);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $user = $webworkMacroEditor->user;
            $webworkMacroEditor->delete();

            $response['type']    = 'info';
            $response['message'] = "{$user->first_name} {$user->last_name}'s macro editor role has been revoked.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to revoke the macro editor role. Please try again or contact us for assistance.";
        }
        return $response;
    }
}
