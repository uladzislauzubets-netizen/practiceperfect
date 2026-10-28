<?php

declare(strict_types=1);

namespace App\Actions\Join;

use App\Models\ShareLink;
use App\Models\User;
use App\Notifications\ChildWantsToJoinTrainer;
use Illuminate\Support\Facades\Cache;

final class NotifyParentOfChildLinkClick
{
    public function handle(ShareLink $link, User $child): void
    {
        $parent = $child->parent;

        if ($parent === null || ! $parent->isActive()) {
            return;
        }

        $firstClick = Cache::add("child-link-notice:{$child->id}:{$link->id}", true, now()->addDay());

        if (! $firstClick) {
            return;
        }

        $parent->notify(new ChildWantsToJoinTrainer(
            $child->first_name,
            $link->trainer->trainerProfile?->business_name ?? $link->trainer->name,
            $link->url,
        ));
    }
}
