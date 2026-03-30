<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\User;
use App\WebworkMacro;
use App\WebworkMacroEditor;
use App\WebworkMacroRevision;
use App\Http\Requests\StoreWebworkMacroRequest;
use App\Exceptions\Handler;
use App\Services\WebworkMacroService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebworkMacroController extends Controller
{
    public function __construct()
    {
        $this->webwork_base_url = app()->environment('production')
            ? 'https://opl.libretexts.org'
            : 'https://staging-opl.libretexts.org';
    }

    /**
     * @param string $name
     * @param WebworkMacro $webworkMacro
     * @return array
     * @throws Exception
     */
    public function getSource(string $name, WebworkMacro $webworkMacro): array
    {

        try {
            $response['type'] = 'error';
            $response['macro'] = $webworkMacro->getSource($name);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "Error: " . $e->getMessage();
        }
        return $response;

    }

    /**
     * Determine if the authenticated user can create/edit/delete macros.
     */
    private function canEdit(): bool
    {
        return Helper::isAdmin() || WebworkMacroEditor::isEditor(request()->user()->id);
    }

    /**
     * List all macros.
     * - Any authenticated user can view the list (read-only for non-editors).
     * - Admin sees owner column and can filter by user.
     */
    public function index(Request $request): array
    {
        try {
            $response['type'] = 'error';
            $is_admin = Helper::isAdmin();
            $user = $request->user();
            $query = WebworkMacro::with('creator')
                ->where('is_retired', false)
                ->orderBy('name');

            // Admin can filter by user
            if ($is_admin && $request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }


            $http_response = Http::get("$this->webwork_base_url/api/macros");

            if ($http_response->successful()) {
                $apiMacros = collect($http_response->json());

                $dbMacros = $query->get()->keyBy('name');

                $macroNamesWithRevisions = DB::table('webwork_macro_revisions')
                    ->where('revision_number', 1)
                    ->distinct()
                    ->pluck('name')
                    ->flip();

                $macros = $apiMacros->map(function ($apiMacro) use ($dbMacros, $is_admin, $user, $macroNamesWithRevisions) {
                    $dbMacro = $dbMacros->get($apiMacro['name']);

                    if (!$dbMacro) {
                        $dbMacro = WebworkMacro::where('name', $apiMacro['name'])->first();

                        if (!$dbMacro) {
                            $macro = $apiMacro['source_type'] === 'custom'
                                ? (new WebworkMacro())->getSource($apiMacro['name'])
                                : "https://github.com/openwebwork/pg/blob/main/macros/{$apiMacro['name']}";

                            DB::table('webwork_macros')->insert([
                                'name' => $apiMacro['name'],
                                'source' => $apiMacro['source_type'],
                                'description' => '',
                                'macro' => $macro,
                                'is_retired' => false,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $dbMacro = WebworkMacro::where('name', $apiMacro['name'])->first();
                        }
                    }

                    return [
                        'id' => $dbMacro ? $dbMacro->id : null,
                        'name' => $apiMacro['name'],
                        'owner_id' => $dbMacro ? $dbMacro->user_id : null,
                        'description' => $dbMacro ? $dbMacro->description : 'None provided',
                        'source' => $apiMacro['source_type'],
                        'updated_at' => $dbMacro ? $dbMacro->updated_at : $apiMacro['created_at'],
                        'can_edit' => $is_admin || ($dbMacro && $dbMacro->user_id === $user->id),
                        'has_revisions' => isset($macroNamesWithRevisions[$apiMacro['name']]),
                        'owner_name' => $dbMacro && $dbMacro->creator
                            ? $dbMacro->creator->first_name . ' ' . $dbMacro->creator->last_name
                            : '—',
                    ];
                })
                    ->filter(function ($macro) {
                        return !str_starts_with($macro['name'], '._');
                    })
                    ->values();
            } else {
                throw new Exception($http_response->body());
            }
            $response['webwork_macros'] = $macros;
            $response['can_create'] = $this->canEdit();
            $response['is_admin'] = $is_admin;

            // For admin: return list of instructors who own macros (for filter dropdown)
            if ($is_admin) {
                $creator_ids = WebworkMacro::whereNotNull('user_id')->distinct()->pluck('user_id');
                $response['creators'] = User::whereIn('id', $creator_ids)
                    ->select('id', 'first_name', 'last_name', 'email')
                    ->orderBy('last_name')
                    ->get();
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the macros. Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * Create a new macro.
     * Only macro editors and admins may create.
     */
    public function store(StoreWebworkMacroRequest $request, WebworkMacro $webworkMacro): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('store', $webworkMacro);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            $webwork_response = $this->postToWebworkServer($request->name, $request->macro);
            if (!$webwork_response['success']) {
                $response['message'] = $webwork_response['message'];
                return $response;
            }

            $macro = WebworkMacro::create([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'description' => $request->description,
                'macro' => $request->macro,
            ]);

            // Snapshot revision 0 immediately on creation
            WebworkMacroRevision::create([
                'webwork_macro_id' => $macro->id,
                'name' => $macro->name,
                'description' => $macro->description,
                'macro' => $macro->macro,
                'edited_by_user_id' => $request->user()->id,
                'revision_number' => 0,
                'reason_for_edit' => null,
            ]);

            $response['type'] = 'success';
            $response['message'] = "The macro has been created.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to create the macro. Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * Update an existing macro.
     * - Admins can edit any macro.
     * - Editors can only edit macros they created.
     * Saves revision 0 (original) if none exists yet, then saves new revision.
     * Notifies WeBWork server.
     */
    public function update(StoreWebworkMacroRequest $request, WebworkMacro $webworkMacro): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('update', $webworkMacro);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        $is_admin = Helper::isAdmin();
        $user = $request->user();

        // Non-admin editors may only edit their own macros
        if (!$is_admin && $webworkMacro->user_id !== $user->id) {
            $response['message'] = "You may only edit macros that you created.";
            return $response;
        }

        try {
            $webwork_response = $this->postToWebworkServer($request->name, $request->macro, false);


            if (!$webwork_response['success']) {
                $response['message'] = $webwork_response['message'];
                return $response;
            }

            DB::beginTransaction();

            // If no revisions exist yet, snapshot the original state as revision 0
            $revision_exists = WebworkMacroRevision::where('webwork_macro_id', $webworkMacro->id)->exists();
            if (!$revision_exists) {
                WebworkMacroRevision::create([
                    'webwork_macro_id' => $webworkMacro->id,
                    'name' => $webworkMacro->name,
                    'description' => $webworkMacro->description,
                    'macro' => $webworkMacro->macro,
                    'edited_by_user_id' => $webworkMacro->user_id ?? $user->id,
                    'revision_number' => 0,
                    'reason_for_edit' => null,
                ]);
            }

            // Determine next revision number
            $next_revision = WebworkMacroRevision::where('webwork_macro_id', $webworkMacro->id)
                    ->max('revision_number') + 1;

            // Save new revision
            WebworkMacroRevision::create([
                'webwork_macro_id' => $webworkMacro->id,
                'name' => $request->name,
                'description' => $request->description,
                'macro' => $request->macro,
                'edited_by_user_id' => $user->id,
                'revision_number' => $next_revision,
                'reason_for_edit' => $request->reason_for_edit,
            ]);

            // Update the macro itself
            $webworkMacro->name = $request->name;
            $webworkMacro->description = $request->description;
            $webworkMacro->macro = $request->macro;
            $webworkMacro->save();
            DB::commit();

            $response['type'] = 'success';
            $response['message'] = "The macro has been updated.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "Error: {$e->getMessage()}";
        }
        return $response;
    }

    /**
     * Delete a macro — hard block if referenced in any question.
     * Admins can delete any; editors can only delete their own.
     */
    public function destroy(WebworkMacro $webworkMacro, WebworkMacroService $macroService): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('destroy', $webworkMacro);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        $is_admin = Helper::isAdmin();
        $user = request()->user();

        if (!$is_admin && $webworkMacro->user_id !== $user->id) {
            $response['message'] = "You may only delete macros that you created.";
            return $response;
        }

        try {
            if ($macroService->macroIsInUse($webworkMacro->id)) {
                $response['message'] = $macroService->usageSummary($webworkMacro->id);
                return $response;
            }

            $webworkMacro->is_retired = true;
            $webworkMacro->save();
            $response['type'] = 'info';
            $response['message'] = "The macro {$webworkMacro->name} has been retired and will no longer appear in the list.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to delete the macro. Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * Get revision history for a macro (admin only).
     */
    public function revisions(WebworkMacro $webworkMacro): array
    {
        $response['type'] = 'error';

        try {
            $authorized = Gate::inspect('revisions', $webworkMacro);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $response['revisions'] = WebworkMacroRevision::with('editor')
                ->where('webwork_macro_id', $webworkMacro->id)
                ->orderBy('revision_number')
                ->get()
                ->map(function ($rev) {
                    $rev->editor_name = $rev->editor
                        ? $rev->editor->first_name . ' ' . $rev->editor->last_name
                        : '—';
                    return $rev;
                });
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the revision history. Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function diffRevisions(
        WebworkMacro $webworkMacro,
        int          $revision1Id,
        int          $revision2Id
    ): array
    {
        $authorized = Gate::inspect('revisions', $webworkMacro);
        if (!$authorized->allowed()) {
            return ['type' => 'error', 'message' => $authorized->message()];
        }

        $revision1 = WebworkMacroRevision::with('editor')->findOrFail($revision1Id);
        $revision2 = WebworkMacroRevision::with('editor')->findOrFail($revision2Id);

        $fields = ['reason_for_edit', 'name', 'description', 'macro'];
        $labelMapping = [
            'reason_for_edit' => 'Reason for Edit',
            'name' => 'Name',
            'description' => 'Description',
            'macro' => 'Macro',
        ];

        $differences = [];
        foreach ($fields as $field) {
            $val1 = $revision1->$field ?? '';
            $val2 = $revision2->$field ?? '';
            if ($val1 === $val2) continue;

            // Line-level diff — far cheaper than char diff for large text
            $old = explode("\n", $val1);
            $new = explode("\n", $val2);

            $diffHtml = '';
            $noDiffHtml = '';

            $diff = array_udiff_assoc($old, $new, 'strcmp');

            // Use SebastianBergmann or a simple inline approach:
            foreach ($new as $i => $line) {
                $oldLine = $old[$i] ?? null;
                if ($oldLine === null) {
                    $diffHtml .= '<span style="color:green">' . htmlspecialchars($line) . '</span><br>';
                } elseif ($line !== $oldLine) {
                    $diffHtml .= '<span style="color:green">' . htmlspecialchars($line) . '</span><br>';
                } else {
                    $diffHtml .= '<span style="color:grey">' . htmlspecialchars($line) . '</span><br>';
                }
                $noDiffHtml .= htmlspecialchars($line) . '<br>';
            }

            $rev1Html = implode('<br>', array_map('htmlspecialchars', $old));

            $differences[] = [
                'property' => $labelMapping[$field] ?? $field,
                'revision1' => $rev1Html,
                'revision2' => $diffHtml,
                'revision2NoDiffs' => $noDiffHtml,
            ];
        }

        return [
            'type' => 'success',
            'differences' => $differences,
            'revision1' => [
                'id' => $revision1->id,
                'revision_number' => $revision1->revision_number,
                'created_at' => $revision1->created_at,
                'editor_name' => $revision1->editor
                    ? $revision1->editor->first_name . ' ' . $revision1->editor->last_name
                    : '—',
            ],
            'revision2' => [
                'id' => $revision2->id,
                'revision_number' => $revision2->revision_number,
                'created_at' => $revision2->created_at,
                'editor_name' => $revision2->editor
                    ? $revision2->editor->first_name . ' ' . $revision2->editor->last_name
                    : '—',
            ],
        ];
    }

    /**
     * Post macro to the WeBWork server.
     */
    private function postToWebworkServer(string $name,
                                         string $source_code,
                                         bool   $check_existence = true): array
    {
        try {
            $authorized = Gate::inspect('postToWebworkServer', new WebworkMacro());
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $check_url = "$this->webwork_base_url/api/macros";
            $webwork_token = config('myconfig.webwork_token');
            $existing = Http::withToken($webwork_token)->get($check_url, ['name' => $name]);
            if ($existing->successful()) {
                $macro_list = $existing->json();
                if ($check_existence) {
                    if (count($macro_list) > 0) {

                        //new one attempting to be created
                        if (!in_array($macro_list[0]['source_type'], ['custom', 'unknown'])) {
                            return [
                                'success' => false,
                                'message' => "A macro named $name already exists on the WeBWork server.",
                            ];
                        }
                    }
                }
            } else {
                throw new Exception("WeBWork server returned status {$existing->status()}.");
            }
            $post_url = "$this->webwork_base_url/api/authored/macros";
            $webwork_response = Http::withToken($webwork_token)->post($post_url, [
                'name' => $name,
                'source_code' => $source_code,
            ]);
            if (!$webwork_response->successful()) {
                if ($webwork_response->status() == 422) {
                    throw new Exception (json_decode($webwork_response->body())->details->diagnostic);
                }
                throw new Exception("WeBWork server returned status {$webwork_response->status()}.");
            }
            return ['success' => true];
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return [
                'success' => false,
                'message' => "Error saving macro: {$e->getMessage()}"
            ];
        }
    }
}
