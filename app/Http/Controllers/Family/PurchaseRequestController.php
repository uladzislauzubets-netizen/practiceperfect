<?php

declare(strict_types=1);

namespace App\Http\Controllers\Family;

use App\Actions\Approvals\RequestChildPurchase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StorePurchaseRequestRequest;
use App\Models\ChildPurchaseApproval;
use App\Notifications\AmountFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isChildAccount(), 403);

        $requests = ChildPurchaseApproval::query()
            ->where('requested_by', $request->user()->id)
            ->latest('requested_at')
            ->limit(50)
            ->get()
            ->map(fn (ChildPurchaseApproval $approval) => [
                'id' => $approval->id,
                'description' => $approval->description,
                'amount_label' => AmountFormatter::format($approval),
                'status' => $approval->status->value,
                'status_label' => $approval->status->label(),
                'requested_at' => $approval->requested_at->toIso8601String(),
                'parent_notes' => $approval->parent_notes,
                'info_request_message' => $approval->info_requested_at !== null && $approval->isPending() ? $approval->info_request_message : null,
            ])
            ->all();

        return Inertia::render('Family/PurchaseRequests', ['requests' => $requests]);
    }

    public function store(StorePurchaseRequestRequest $request, RequestChildPurchase $requestPurchase): RedirectResponse
    {
        $approval = $requestPurchase->handle($request->user(), $request->safe()->only(['description', 'amount', 'payment_type']));

        return back()->with('success', $approval->auto_approved
            ? 'Done. Your parent was told about this token purchase.'
            : 'Sent to your parent for approval.');
    }
}
