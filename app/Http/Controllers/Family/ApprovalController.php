<?php

declare(strict_types=1);

namespace App\Http\Controllers\Family;

use App\Actions\Approvals\RespondToApproval;
use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\RespondApprovalRequest;
use App\Models\ChildPurchaseApproval;
use App\Notifications\AmountFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $approvals = ChildPurchaseApproval::query()
            ->with('playerProfile')
            ->where('parent_id', $request->user()->id)
            ->orderByRaw('case when status = ? then 0 else 1 end', [ApprovalStatus::Pending->value])
            ->latest('requested_at')
            ->limit(50)
            ->get()
            ->map(fn (ChildPurchaseApproval $approval) => [
                'id' => $approval->id,
                'child' => $approval->playerProfile->first_name,
                'description' => $approval->description,
                'amount_label' => AmountFormatter::format($approval),
                'payment_type' => $approval->payment_type->value,
                'status' => $approval->status->value,
                'status_label' => $approval->status->label(),
                'auto_approved' => $approval->auto_approved,
                'requested_at' => $approval->requested_at->toIso8601String(),
                'expires_at' => $approval->expires_at->toIso8601String(),
                'parent_notes' => $approval->parent_notes,
                'info_request_message' => $approval->info_request_message,
            ])
            ->all();

        return Inertia::render('Family/Approvals', ['approvals' => $approvals]);
    }

    public function respond(RespondApprovalRequest $request, ChildPurchaseApproval $approval, RespondToApproval $respond): RedirectResponse
    {
        $respond->handle($approval, $request->string('decision')->toString(), $request->input('notes'));

        return back()->with('success', match ($request->string('decision')->toString()) {
            RespondToApproval::APPROVE => 'Approved. The purchase can go ahead.',
            RespondToApproval::DENY => 'Denied. Your child has been notified.',
            default => 'Your question was sent to your child.',
        });
    }
}
