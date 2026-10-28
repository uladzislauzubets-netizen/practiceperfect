<?php

declare(strict_types=1);

use App\Actions\Approvals\ExpirePendingApprovals;
use App\Enums\ApprovalStatus;
use App\Models\ChildPurchaseApproval;
use App\Models\User;
use App\Notifications\ChildApprovalExpired;
use App\Notifications\ChildApprovalRequested;
use App\Notifications\ChildApprovalResolved;
use App\Notifications\ChildTokenSpendNotice;
use Illuminate\Support\Facades\Notification;

function childWithParent(): array
{
    $parent = User::factory()->create();
    $child = User::factory()->childAccountOf($parent)->create(['first_name' => 'Alex']);

    return [$parent, $child];
}

function usdRequest(array $overrides = []): array
{
    return ['description' => 'Saturday clinic', 'amount' => 4500, 'payment_type' => 'usd', ...$overrides];
}

it('puts a childs usd purchase in pending parent approval and notifies the parent', function () {
    Notification::fake();
    [$parent, $child] = childWithParent();

    $this->actingAs($child)->post('/purchase-requests', usdRequest())->assertRedirect()->assertSessionHas('success', 'Sent to your parent for approval.');

    $approval = ChildPurchaseApproval::query()->firstOrFail();
    expect($approval->status)->toBe(ApprovalStatus::Pending)
        ->and($approval->status->label())->toBe('Pending Parent Approval')
        ->and($approval->parent_id)->toBe($parent->id)
        ->and($approval->requested_by)->toBe($child->id)
        ->and($approval->amount)->toBe(4500)
        ->and((int) $approval->requested_at->diffInHours($approval->expires_at))->toBe(48);

    Notification::assertSentTo($parent, ChildApprovalRequested::class, fn ($n, $channels) => in_array('mail', $channels, true) && in_array('database', $channels, true));
});

it('requires approval for tokens by default', function () {
    Notification::fake();
    [, $child] = childWithParent();

    $this->actingAs($child)->post('/purchase-requests', usdRequest(['payment_type' => 'tokens', 'amount' => 20]));

    expect(ChildPurchaseApproval::query()->first()->status)->toBe(ApprovalStatus::Pending);
});

it('lets tokens through instantly when the parent allows it, with an informational notice only', function () {
    Notification::fake();
    [$parent, $child] = childWithParent();
    $child->playerProfile->update(['allow_token_spending_without_approval' => true]);

    $this->actingAs($child)->post('/purchase-requests', usdRequest(['payment_type' => 'tokens', 'amount' => 20]))
        ->assertSessionHas('success', 'Done. Your parent was told about this token purchase.');

    $approval = ChildPurchaseApproval::query()->firstOrFail();
    expect($approval->status)->toBe(ApprovalStatus::Approved)->and($approval->auto_approved)->toBeTrue();
    Notification::assertSentTo($parent, ChildTokenSpendNotice::class);
    Notification::assertNotSentTo($parent, ChildApprovalRequested::class);
});

it('always requires approval for usd even if the token rule is on', function () {
    Notification::fake();
    [, $child] = childWithParent();
    $child->playerProfile->update(['allow_token_spending_without_approval' => true]);

    $this->actingAs($child)->post('/purchase-requests', usdRequest());

    expect(ChildPurchaseApproval::query()->first()->status)->toBe(ApprovalStatus::Pending);
});

it('applies the token rule per child', function () {
    Notification::fake();
    $parent = User::factory()->create();
    $trusted = User::factory()->childAccountOf($parent)->create();
    $other = User::factory()->childAccountOf($parent)->create();
    $trusted->playerProfile->update(['allow_token_spending_without_approval' => true]);

    $this->actingAs($trusted)->post('/purchase-requests', usdRequest(['payment_type' => 'tokens']));
    $this->actingAs($other)->post('/purchase-requests', usdRequest(['payment_type' => 'tokens']));

    expect(ChildPurchaseApproval::query()->where('requested_by', $trusted->id)->first()->status)->toBe(ApprovalStatus::Approved)
        ->and(ChildPurchaseApproval::query()->where('requested_by', $other->id)->first()->status)->toBe(ApprovalStatus::Pending);
});

it('only child accounts can create purchase requests', function () {
    $this->actingAs(player())->post('/purchase-requests', usdRequest())->assertForbidden();
    $this->actingAs(trainer())->post('/purchase-requests', usdRequest())->assertForbidden();
});

it('lets the parent approve with a note, notifying the child', function () {
    Notification::fake();
    [$parent, $child] = childWithParent();
    $this->actingAs($child)->post('/purchase-requests', usdRequest());
    $approval = ChildPurchaseApproval::query()->firstOrFail();

    $this->actingAs($parent)->get('/family/approvals')->assertInertia(fn ($page) => $page->component('Family/Approvals')->has('approvals', 1)->where('approvals.0.status', 'pending'));
    $this->post("/family/approvals/{$approval->id}", ['decision' => 'approve', 'notes' => 'Have fun'])->assertRedirect();

    $approval->refresh();
    expect($approval->status)->toBe(ApprovalStatus::Approved)->and($approval->parent_notes)->toBe('Have fun')->and($approval->responded_at)->not->toBeNull();
    Notification::assertSentTo($child, ChildApprovalResolved::class);

    $this->actingAs($child)->get('/purchase-requests')->assertInertia(fn ($page) => $page->where('requests.0.status_label', 'Confirmed'));
});

it('lets the parent deny', function () {
    Notification::fake();
    [$parent, $child] = childWithParent();
    $this->actingAs($child)->post('/purchase-requests', usdRequest());
    $approval = ChildPurchaseApproval::query()->firstOrFail();

    $this->actingAs($parent)->post("/family/approvals/{$approval->id}", ['decision' => 'deny', 'notes' => 'Not this month']);

    expect($approval->fresh()->status)->toBe(ApprovalStatus::Denied);
    Notification::assertSentTo($child, ChildApprovalResolved::class);
});

it('lets the parent request more info while the request stays pending', function () {
    Notification::fake();
    [$parent, $child] = childWithParent();
    $this->actingAs($child)->post('/purchase-requests', usdRequest());
    $approval = ChildPurchaseApproval::query()->firstOrFail();

    $this->actingAs($parent)->post("/family/approvals/{$approval->id}", ['decision' => 'request_info'])->assertSessionHasErrors('notes');
    $this->post("/family/approvals/{$approval->id}", ['decision' => 'request_info', 'notes' => 'Who is running it?'])->assertRedirect();

    expect($approval->fresh()->status)->toBe(ApprovalStatus::Pending)->and($approval->fresh()->info_request_message)->toBe('Who is running it?');
    $this->actingAs($child)->get('/purchase-requests')->assertInertia(fn ($page) => $page->where('requests.0.info_request_message', 'Who is running it?'));
});

it('only the parent of that child can respond', function () {
    Notification::fake();
    [, $child] = childWithParent();
    $this->actingAs($child)->post('/purchase-requests', usdRequest());
    $approval = ChildPurchaseApproval::query()->firstOrFail();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->post("/family/approvals/{$approval->id}", ['decision' => 'approve'])->assertForbidden();
    $this->actingAs($child)->post("/family/approvals/{$approval->id}", ['decision' => 'approve'])->assertForbidden();
    $this->actingAs(admin())->post("/family/approvals/{$approval->id}", ['decision' => 'approve'])->assertForbidden();

    expect($approval->fresh()->status)->toBe(ApprovalStatus::Pending);
});

it('cannot respond twice', function () {
    Notification::fake();
    [$parent, $child] = childWithParent();
    $this->actingAs($child)->post('/purchase-requests', usdRequest());
    $approval = ChildPurchaseApproval::query()->firstOrFail();

    $this->actingAs($parent)->post("/family/approvals/{$approval->id}", ['decision' => 'approve']);
    $this->post("/family/approvals/{$approval->id}", ['decision' => 'deny'])->assertSessionHasErrors('approval');

    expect($approval->fresh()->status)->toBe(ApprovalStatus::Approved);
});

it('auto denies requests after 48 hours and notifies both sides', function () {
    Notification::fake();
    [$parent, $child] = childWithParent();
    $this->actingAs($child)->post('/purchase-requests', usdRequest());
    $approval = ChildPurchaseApproval::query()->firstOrFail();

    $this->travel(47)->hours();
    expect(app(ExpirePendingApprovals::class)->handle())->toBe(0);

    $this->travel(2)->hours();
    $this->artisan('approvals:expire')->assertSuccessful();

    expect($approval->fresh()->status)->toBe(ApprovalStatus::Expired);
    Notification::assertSentTo($parent, ChildApprovalExpired::class);
    Notification::assertSentTo($child, ChildApprovalExpired::class);
});

it('refuses a response that arrives after expiry', function () {
    Notification::fake();
    [$parent, $child] = childWithParent();
    $this->actingAs($child)->post('/purchase-requests', usdRequest());
    $approval = ChildPurchaseApproval::query()->firstOrFail();

    $this->travel(49)->hours();
    $this->actingAs($parent)->post("/family/approvals/{$approval->id}", ['decision' => 'approve'])->assertSessionHasErrors('approval');

    expect($approval->fresh()->status)->toBe(ApprovalStatus::Expired);
});

it('stores in app notifications the parent can open', function () {
    [$parent, $child] = childWithParent();
    $this->actingAs($child)->post('/purchase-requests', usdRequest());

    $this->actingAs($parent)->get('/notifications')->assertInertia(fn ($page) => $page->has('notifications.data', 1)->where('notifications.data.0.read', false));
    $this->get('/dashboard')->assertInertia(fn ($page) => $page->where('auth.unread_notifications', 1)->where('pending_approvals', 1));

    $id = $parent->notifications()->first()->id;
    $this->get("/notifications/{$id}/open")->assertRedirect(route('family.approvals.index'));
    expect($parent->fresh()->unreadNotifications()->count())->toBe(0);

    $other = User::factory()->create();
    $this->actingAs($other)->get("/notifications/{$id}/open")->assertNotFound();
});

it('validates purchase requests', function () {
    [, $child] = childWithParent();

    $this->actingAs($child)->post('/purchase-requests', [])->assertSessionHasErrors(['description', 'amount', 'payment_type']);
    $this->post('/purchase-requests', usdRequest(['amount' => 0]))->assertSessionHasErrors('amount');
    $this->post('/purchase-requests', usdRequest(['payment_type' => 'bitcoin']))->assertSessionHasErrors('payment_type');
});
