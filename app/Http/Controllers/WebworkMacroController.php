<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\User;
use App\WebworkMacro;
use App\WebworkMacroCoEditor;
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

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * True if the current user may create/edit macros at all
     * (global editor permission — separate from per-macro co-editor).
     */
    private function canEdit(): bool
    {
        return Helper::isAdmin() || WebworkMacroEditor::isEditor(request()->user()->id);
    }

    /**
     * True if the current user may edit THIS specific macro:
     *   - admin, OR
     *   - owner, OR
     *   - listed as a co-editor for this macro.
     */
    private function canEditMacro(WebworkMacro $macro): bool
    {
        $userId = request()->user()->id;
        return Helper::isAdmin()
            || $macro->user_id === $userId
            || WebworkMacroCoEditor::isCoEditor($userId, $macro->id);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Source
    // ──────────────────────────────────────────────────────────────────────────

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

    // ──────────────────────────────────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request): array
    {
        try {
            $response['type'] = 'error';
            $is_admin        = Helper::isAdmin();
            $user            = $request->user();

            $query = WebworkMacro::with('creator')
                ->where('is_retired', false)
                ->orderBy('name');

            $http_response = Http::get("$this->webwork_base_url/api/macros");

            if ($http_response->successful()) {
                $apiMacros = collect($http_response->json());
                $dbMacros  = $query->get()->keyBy('name');

                $macroNamesWithRevisions = DB::table('webwork_macro_revisions')
                    ->where('revision_number', 1)
                    ->distinct()
                    ->pluck('name')
                    ->flip();

                // Co-editor macro ids for the current user (one query, not N)
                $coEditorMacroIds = DB::table('webwork_macro_co_editors')
                    ->where('user_id', $user->id)
                    ->pluck('webwork_macro_id')
                    ->flip();

                // Pre-load co-editor counts for all macros in one query
                $coEditorCounts = DB::table('webwork_macro_co_editors')
                    ->select('webwork_macro_id', DB::raw('count(*) as cnt'))
                    ->groupBy('webwork_macro_id')
                    ->pluck('cnt', 'webwork_macro_id');

                // Pre-load the most recent editor name per macro (one query)
                $latestRevisions = DB::table('webwork_macro_revisions as r')
                    ->join(DB::raw('(SELECT webwork_macro_id, MAX(revision_number) as max_rev FROM webwork_macro_revisions GROUP BY webwork_macro_id) as latest'),
                        function ($join) {
                            $join->on('r.webwork_macro_id', '=', 'latest.webwork_macro_id')
                                ->on('r.revision_number', '=', 'latest.max_rev');
                        })
                    ->join('users', 'users.id', '=', 'r.edited_by_user_id')
                    ->select('r.webwork_macro_id', DB::raw("CONCAT(users.first_name, ' ', users.last_name) as editor_name"))
                    ->pluck('editor_name', 'webwork_macro_id');

                $macros = $apiMacros->map(function ($apiMacro) use (
                    $dbMacros, $is_admin, $user, $macroNamesWithRevisions, $coEditorMacroIds, $coEditorCounts, $latestRevisions
                ) {
                    $dbMacro = $dbMacros->get($apiMacro['name']);

                    if (!$dbMacro) {
                        $dbMacro = WebworkMacro::where('name', $apiMacro['name'])->first();

                        if (!$dbMacro) {
                            $macro = $apiMacro['source_type'] === 'custom'
                                ? (new WebworkMacro())->getSource($apiMacro['name'])
                                : "https://github.com/openwebwork/pg/blob/main/macros/{$apiMacro['name']}";

                            DB::table('webwork_macros')->insert([
                                'name'        => $apiMacro['name'],
                                'source'      => $apiMacro['source_type'],
                                'description' => '',
                                'macro'       => $macro,
                                'is_retired'  => false,
                                'created_at'  => now(),
                                'updated_at'  => now(),
                            ]);

                            $dbMacro = WebworkMacro::where('name', $apiMacro['name'])->first();
                        }
                    }

                    $isCoEditor = $dbMacro && isset($coEditorMacroIds[$dbMacro->id]);
                    $isOwner    = $dbMacro && $dbMacro->user_id === $user->id;
                    $canEdit    = $is_admin || $isOwner || $isCoEditor;
                    $canManageCoEditors = $is_admin || $isOwner;
                    $coEditorCount = $dbMacro ? ($coEditorCounts[$dbMacro->id] ?? 0) : 0;

                    return [
                        'id'                    => $dbMacro ? $dbMacro->id : null,
                        'name'                  => $apiMacro['name'],
                        'owner_id'              => $dbMacro ? $dbMacro->user_id : null,
                        'description'           => $dbMacro ? $dbMacro->description : 'None provided',
                        'source'                => $apiMacro['source_type'],
                        'updated_at'            => $dbMacro ? $dbMacro->updated_at : $apiMacro['created_at'],
                        'can_edit'              => $canEdit,
                        'can_manage_co_editors' => $canManageCoEditors,
                        'co_editor_count'       => $coEditorCount,
                        'has_revisions'         => isset($macroNamesWithRevisions[$apiMacro['name']]),
                        'owner_name'            => $dbMacro && $dbMacro->creator
                            ? $dbMacro->creator->first_name . ' ' . $dbMacro->creator->last_name
                            : '—',
                        'last_editor_name'      => $dbMacro
                            ? ($latestRevisions[$dbMacro->id] ?? null)
                            : null,
                    ];
                })
                    ->filter(fn($macro) => !str_starts_with($macro['name'], '._'))
                    ->values();
            } else {
                throw new Exception($http_response->body());
            }

            $response['webwork_macros'] = $macros;
            $response['can_create']     = $this->canEdit();
            $response['is_admin']       = $is_admin;

            if ($is_admin) {
                // Users who are owners of any macro
                $owner_ids = WebworkMacro::whereNotNull('user_id')
                    ->where('is_retired', false)
                    ->distinct()
                    ->pluck('user_id');

                // Users who are the last editor of any macro
                $last_editor_ids = DB::table('webwork_macro_revisions as r')
                    ->join(DB::raw('(SELECT webwork_macro_id, MAX(revision_number) as max_rev FROM webwork_macro_revisions GROUP BY webwork_macro_id) as latest'),
                        function ($join) {
                            $join->on('r.webwork_macro_id', '=', 'latest.webwork_macro_id')
                                ->on('r.revision_number', '=', 'latest.max_rev');
                        })
                    ->whereNotNull('r.edited_by_user_id')
                    ->distinct()
                    ->pluck('r.edited_by_user_id');

                $involved_ids = $owner_ids->merge($last_editor_ids)->unique();

                $response['creators'] = User::whereIn('id', $involved_ids)
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

    // ──────────────────────────────────────────────────────────────────────────
    // Store
    // ──────────────────────────────────────────────────────────────────────────

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
                'user_id'     => $request->user()->id,
                'name'        => $request->name,
                'description' => $request->description,
                'macro'       => $request->macro,
            ]);

            WebworkMacroRevision::create([
                'webwork_macro_id' => $macro->id,
                'name'             => $macro->name,
                'description'      => $macro->description,
                'macro'            => $macro->macro,
                'edited_by_user_id'=> $request->user()->id,
                'revision_number'  => 0,
                'reason_for_edit'  => null,
            ]);

            $response['type']    = 'success';
            $response['message'] = "The macro has been created.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to create the macro. Please try again or contact us for assistance.";
        }
        return $response;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────────────────

    public function update(StoreWebworkMacroRequest $request, WebworkMacro $webworkMacro): array
    {
        $response['type'] = 'error';

        // Gate check first (policy-level), then per-macro co-editor check
        $authorized = Gate::inspect('update', $webworkMacro);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $webwork_response = $this->postToWebworkServer($request->name, $request->macro, false);
            if (!$webwork_response['success']) {
                $response['message'] = $webwork_response['message'];
                return $response;
            }

            DB::beginTransaction();

            $revision_exists = WebworkMacroRevision::where('webwork_macro_id', $webworkMacro->id)->exists();
            if (!$revision_exists) {
                // Use the macro's original owner for revision 0.
                // Do NOT fall back to the current editor — if user_id is null the
                // macro pre-dates the ownership system; leave edited_by_user_id null
                // so the label shows "—" rather than wrongly crediting the co-editor.
                WebworkMacroRevision::create([
                    'webwork_macro_id' => $webworkMacro->id,
                    'name'             => $webworkMacro->name,
                    'description'      => $webworkMacro->description,
                    'macro'            => $webworkMacro->macro,
                    'edited_by_user_id'=> $webworkMacro->user_id ?? null,
                    'revision_number'  => 0,
                    'reason_for_edit'  => null,
                ]);
            }

            $next_revision = WebworkMacroRevision::where('webwork_macro_id', $webworkMacro->id)
                    ->max('revision_number') + 1;

            // Record who actually made this edit
            WebworkMacroRevision::create([
                'webwork_macro_id' => $webworkMacro->id,
                'name'             => $request->name,
                'description'      => $request->description,
                'macro'            => $request->macro,
                'edited_by_user_id'=> $request->user()->id,   // always the actual editor
                'revision_number'  => $next_revision,
                'reason_for_edit'  => $request->reason_for_edit,
            ]);

            // user_id (owner) intentionally NOT updated — original creator stays
            $webworkMacro->name        = $request->name;
            $webworkMacro->description = $request->description;
            $webworkMacro->macro       = $request->macro;
            $webworkMacro->save();
            DB::commit();

            $response['type']    = 'success';
            $response['message'] = "The macro has been updated.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "Error: {$e->getMessage()}";
        }
        return $response;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Destroy
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(WebworkMacro $webworkMacro, WebworkMacroService $macroService): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('destroy', $webworkMacro);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $is_admin = Helper::isAdmin();
        $user     = request()->user();

        // Co-editors may NOT retire a macro — only owner or admin
        if (!$is_admin && $webworkMacro->user_id !== $user->id) {
            $response['message'] = "You may only retire macros that you created.";
            return $response;
        }

        try {
            if ($macroService->macroIsInUse($webworkMacro->id)) {
                $response['message'] = $macroService->usageSummary($webworkMacro->id);
                return $response;
            }

            $webworkMacro->is_retired = true;
            $webworkMacro->save();
            $response['type']    = 'info';
            $response['message'] = "The macro {$webworkMacro->name} has been retired and will no longer appear in the list.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to delete the macro. Please try again or contact us for assistance.";
        }
        return $response;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Revisions & diff
    // ──────────────────────────────────────────────────────────────────────────

    public function revisions(WebworkMacro $webworkMacro): array
    {
        $response['type'] = 'error';

        try {
            // Allow owner, admin, or co-editor
            if (!$this->canEditMacro($webworkMacro)) {
                $response['message'] = "You are not authorised to view revisions for this macro.";
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
    ): array {
        if (!$this->canEditMacro($webworkMacro)) {
            return ['type' => 'error', 'message' => 'You are not authorised to compare revisions for this macro.'];
        }

        $revision1 = WebworkMacroRevision::with('editor')->findOrFail($revision1Id);
        $revision2 = WebworkMacroRevision::with('editor')->findOrFail($revision2Id);

        $fields       = ['reason_for_edit', 'name', 'description', 'macro'];
        $labelMapping = [
            'reason_for_edit' => 'Reason for Edit',
            'name'            => 'Name',
            'description'     => 'Description',
            'macro'           => 'Macro',
        ];

        $differences = [];
        foreach ($fields as $field) {
            $val1 = $revision1->$field ?? '';
            $val2 = $revision2->$field ?? '';
            if ($val1 === $val2) continue;

            $old = explode("\n", $val1);
            $new = explode("\n", $val2);

            $diffHtml   = '';
            $noDiffHtml = '';

            foreach ($new as $i => $line) {
                $oldLine = $old[$i] ?? null;
                if ($oldLine === null || $line !== $oldLine) {
                    $diffHtml .= '<span style="color:green">' . htmlspecialchars($line) . '</span><br>';
                } else {
                    $diffHtml .= '<span style="color:grey">' . htmlspecialchars($line) . '</span><br>';
                }
                $noDiffHtml .= htmlspecialchars($line) . '<br>';
            }

            $rev1Html = implode('<br>', array_map('htmlspecialchars', $old));

            $differences[] = [
                'property'        => $labelMapping[$field] ?? $field,
                'revision1'       => $rev1Html,
                'revision2'       => $diffHtml,
                'revision2NoDiffs'=> $noDiffHtml,
            ];
        }

        return [
            'type'        => 'success',
            'differences' => $differences,
            'revision1'   => [
                'id'              => $revision1->id,
                'revision_number' => $revision1->revision_number,
                'created_at'      => $revision1->created_at,
                'editor_name'     => $revision1->editor
                    ? $revision1->editor->first_name . ' ' . $revision1->editor->last_name : '—',
            ],
            'revision2'   => [
                'id'              => $revision2->id,
                'revision_number' => $revision2->revision_number,
                'created_at'      => $revision2->created_at,
                'editor_name'     => $revision2->editor
                    ? $revision2->editor->first_name . ' ' . $revision2->editor->last_name : '—',
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Co-editor management
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/webwork-macros/{macro}/co-editors
     * Returns current co-editors + the potential editor list.
     * Only the macro owner or admin may call this.
     */
    public function getCoEditors(WebworkMacro $webworkMacro): array
    {
        $response['type'] = 'error';

        $is_admin = Helper::isAdmin();
        $user     = request()->user();

        if (!$is_admin && $webworkMacro->user_id !== $user->id) {
            $response['message'] = "Only the macro owner or an admin can manage co-editors.";
            return $response;
        }

        try {
            $coEditors = WebworkMacroCoEditor::with('user')
                ->where('webwork_macro_id', $webworkMacro->id)
                ->get()
                ->map(fn($ce) => [
                    'id'         => $ce->id,
                    'user_id'    => $ce->user_id,
                    'user_name'  => $ce->user
                        ? $ce->user->first_name . ' ' . $ce->user->last_name
                        : '—',
                    'user_email' => $ce->user ? $ce->user->email : '',
                ]);

            // Potential editors: only users already in webwork_macro_editors
            $potentialEditors = DB::table('users')
                ->join('webwork_macro_editors', 'users.id', '=', 'webwork_macro_editors.user_id')
                ->orderBy('users.last_name')
                ->select(DB::raw('users.id, CONCAT(users.first_name, " ", users.last_name, " --- ", users.email) AS label'))
                ->whereNotNull('users.email')
                ->whereNotNull('users.central_identity_id')
                ->where('users.testing_student', 0)
                ->get()
                ->map(fn($u) => ['value' => $u->id, 'text' => $u->label]);

            $response['type']              = 'success';
            $response['co_editors']        = $coEditors;
            $response['potential_editors'] = $potentialEditors;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve co-editors. Please try again.";
        }
        return $response;
    }

    /**
     * POST /api/webwork-macros/{macro}/co-editors
     * Body: { user_id: int }
     * Only the macro owner or admin may call this.
     */
    public function addCoEditor(Request $request, WebworkMacro $webworkMacro): array
    {
        $response['type'] = 'error';

        $is_admin = Helper::isAdmin();
        $user     = $request->user();

        if (!$is_admin && $webworkMacro->user_id !== $user->id) {
            $response['message'] = "Only the macro owner or an admin can add co-editors.";
            return $response;
        }

        $newUserId = (int) $request->input('user_id');

        if (!$newUserId) {
            $response['message'] = "A valid user_id is required.";
            return $response;
        }

        // Prevent adding the owner as a co-editor
        if ($newUserId === $webworkMacro->user_id) {
            $response['message'] = "The macro owner is already an editor.";
            return $response;
        }

        try {
            WebworkMacroCoEditor::firstOrCreate([
                'webwork_macro_id' => $webworkMacro->id,
                'user_id'          => $newUserId,
            ]);

            $newUser = User::find($newUserId);
            $response['type']    = 'success';
            $response['message'] = ($newUser
                    ? $newUser->first_name . ' ' . $newUser->last_name
                    : "User $newUserId") . " has been added as a co-editor.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to add the co-editor. Please try again.";
        }
        return $response;
    }

    /**
     * DELETE /api/webwork-macros/{macro}/co-editors/{coEditor}
     * Only the macro owner or admin may call this.
     */
    public function removeCoEditor(WebworkMacro $webworkMacro, WebworkMacroCoEditor $coEditor): array
    {
        $response['type'] = 'error';

        $is_admin = Helper::isAdmin();
        $user     = request()->user();

        if (!$is_admin && $webworkMacro->user_id !== $user->id) {
            $response['message'] = "Only the macro owner or an admin can remove co-editors.";
            return $response;
        }

        if ($coEditor->webwork_macro_id !== $webworkMacro->id) {
            $response['message'] = "Co-editor does not belong to this macro.";
            return $response;
        }

        try {
            $name = $coEditor->user
                ? $coEditor->user->first_name . ' ' . $coEditor->user->last_name
                : "User {$coEditor->user_id}";
            $coEditor->delete();
            $response['type']    = 'success';
            $response['message'] = "$name has been removed as a co-editor.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to remove the co-editor. Please try again.";
        }
        return $response;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // WeBWork server helper
    // ──────────────────────────────────────────────────────────────────────────

    private function postToWebworkServer(string $name,
                                         string $source_code,
                                         bool   $check_existence = true): array
    {
        try {
            $authorized = Gate::inspect('postToWebworkServer', new WebworkMacro());
            if (!$authorized->allowed()) {
                return ['success' => false, 'message' => $authorized->message()];
            }

            $check_url    = "$this->webwork_base_url/api/macros";
            $webwork_token = config('myconfig.webwork_token');
            $existing     = Http::withToken($webwork_token)->get($check_url, ['name' => $name]);

            if ($existing->successful()) {
                $macro_list = $existing->json();
                if ($check_existence && count($macro_list) > 0) {
                    if (!in_array($macro_list[0]['source_type'], ['custom', 'unknown'])) {
                        return [
                            'success' => false,
                            'message' => "A macro named $name already exists on the WeBWork server.",
                        ];
                    }
                }
            } else {
                throw new Exception("WeBWork server returned status {$existing->status()}.");
            }

            $post_url        = "$this->webwork_base_url/api/authored/macros";
            $webwork_response = Http::withToken($webwork_token)->post($post_url, [
                'name'        => $name,
                'source_code' => $source_code,
            ]);

            if (!$webwork_response->successful()) {
                if ($webwork_response->status() == 422) {
                    throw new Exception(json_decode($webwork_response->body())->details->diagnostic);
                }
                throw new Exception("WeBWork server returned status {$webwork_response->status()}.");
            }

            return ['success' => true];
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return ['success' => false, 'message' => "Error saving macro: {$e->getMessage()}"];
        }
    }
}
