<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Enquiry::class);
        $status = EnquiryStatus::tryFrom(input_string($request->query('status')));

        return view('admin.enquiries.index', [
            'enquiries' => Enquiry::query()->when($status, fn ($q) => $q->where('status', $status))
                ->orderByRaw("case when status = 'new' then 0 else 1 end")->orderByDesc('id')->paginate(25)->withQueryString(),
            'status' => $status,
            'counts' => Enquiry::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Enquiry $enquiry): View
    {
        Gate::authorize('view', $enquiry);

        return view('admin.enquiries.show', ['enquiry' => $enquiry->load('notes.user')]);
    }

    public function update(Request $request, Enquiry $enquiry): RedirectResponse
    {
        Gate::authorize('update', $enquiry);
        $data = $request->validate(['status' => ['required', Rule::enum(EnquiryStatus::class)], 'body' => ['nullable', 'string', 'max:3000']]);
        $user = $this->user($request);
        $status = EnquiryStatus::from($data['status']);
        if ($enquiry->status !== $status) {
            $enquiry->update(['status' => $status]);
            $enquiry->log('Status changed to '.$status->label().'.', 'status', $user);
        }
        if (! empty($data['body'])) {
            $enquiry->log($data['body'], 'note', $user);
        }

        return back()->with('status', "{$enquiry->reference} saved.");
    }
}
