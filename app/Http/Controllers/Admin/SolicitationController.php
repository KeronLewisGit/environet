<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Area;
use App\Enums\SolicitationStatus;
use App\Http\Controllers\Controller;
use App\Models\Solicitation;
use App\Models\SolicitationFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The bid register: RFQs and bid inquiries by deadline, bid/no-bid, pricing, submission and award. */
class SolicitationController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Solicitation::class);
        $filters = $this->filters($request);
        $counts = Solicitation::query()->filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        // Open solicitations by nearest deadline first; closed ones newest first underneath.
        $solicitations = Solicitation::with('assignee')->withCount('files')->filter($filters)
            ->orderByRaw("case when status in ('new', 'reviewing', 'pricing') then 0 else 1 end")
            ->orderByRaw("case when status in ('new', 'reviewing', 'pricing') then response_due_at end asc")
            ->orderByDesc('response_due_at')
            ->paginate(25)->withQueryString();

        return view('admin.solicitations.index', [
            'solicitations' => $solicitations,
            'filters' => $filters,
            'counts' => $counts,
            'openCount' => Solicitation::open()->count(),
            'staff' => User::working(Area::Solicitations)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Solicitation $solicitation): View
    {
        Gate::authorize('view', $solicitation);

        return view('admin.solicitations.show', [
            'solicitation' => $solicitation->load(['assignee', 'files', 'notes.user']),
            'staff' => User::working(Area::Solicitations)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Solicitation $solicitation): RedirectResponse
    {
        Gate::authorize('update', $solicitation);
        $user = $this->user($request);
        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(SolicitationStatus::class)],
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('active', true)],
        ]);

        // Check the assignee before changing anything, so a refused assignment leaves no half-saved status.
        $assignTo = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        $reassign = array_key_exists('assigned_to', $data) && $solicitation->assigned_to !== $assignTo;
        $assignee = $reassign && $assignTo ? User::find($assignTo) : null;
        if ($assignee && ! $assignee->works(Area::Solicitations)) {
            return back()->withErrors(['assigned_to' => "{$assignee->name} works in {$assignee->role->label()} and can't see solicitations."]);
        }

        if (isset($data['status']) && $solicitation->claimStatus($status = SolicitationStatus::from($data['status']))) {
            $solicitation->log('Status changed to '.$status->label().'.', 'status', $user);
        }
        if ($reassign) {
            $solicitation->assigned_to = $assignee?->id;
            $solicitation->log($assignee ? "Assigned to {$assignee->name}." : 'Unassigned.', 'assign', $user);
        }
        $solicitation->save();

        return back()->with('status', "{$solicitation->reference} saved.");
    }

    public function note(Request $request, Solicitation $solicitation): RedirectResponse
    {
        Gate::authorize('update', $solicitation);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $solicitation->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    public function file(Request $request, Solicitation $solicitation, SolicitationFile $file): StreamedResponse
    {
        Gate::authorize('openFile', [$solicitation, $file]);
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        $solicitation->log("Opened {$file->original_name}.", 'file', $this->user($request));
        $name = Str::slug($solicitation->reference.'-'.pathinfo($file->original_name, PATHINFO_FILENAME)).'.'.pathinfo($file->path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($file->path, $name, ['Cache-Control' => 'private, no-store']);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Solicitation::class);
        $query = Solicitation::with('assignee')->filter($this->filters($request))->orderBy('response_due_at');

        return $this->csv('environet-bids', ['Reference', 'Received', 'Status', 'From', 'Source', 'Title', 'Solicitation number', 'Type', 'Set-aside', 'NAICS', 'Place of performance', 'Response due (HST)', 'Owner', 'Contact', 'Email', 'Phone'],
            $query->lazyById(500)->map(fn (Solicitation $t) => [
                $t->reference, $t->created_at->format('Y-m-d'), $t->status->label(), $t->organization, $t->source, $t->title, $t->solicitation_number,
                $t->type->short(), $t->set_aside?->short(), $t->naics, $t->region, $t->response_due_at->format('Y-m-d H:i'), $t->assignee->name ?? '', $t->contact_name, $t->email, format_phone($t->phone),
            ]));
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $status = input_string($request->query('status'));
        $assigned = input_string($request->query('assigned'));
        $source = input_string($request->query('source'));

        return [
            'status' => $status === 'open' ? 'open' : SolicitationStatus::tryFrom($status)?->value,
            'source' => array_key_exists($source, Solicitation::SOURCES) ? $source : null,
            'assigned' => match (true) {
                $assigned === 'me' => (string) $request->user()?->getAuthIdentifier(),
                $assigned === 'none', ctype_digit($assigned) => $assigned,
                default => null,
            },
            'due' => $request->boolean('due') ? '1' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
