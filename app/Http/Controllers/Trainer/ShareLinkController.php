<?php

declare(strict_types=1);

namespace App\Http\Controllers\Trainer;

use App\Actions\Trainer\GetPlayerShareLink;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShareLinkController extends Controller
{
    public function show(Request $request, GetPlayerShareLink $shareLink): Response
    {
        $link = $shareLink->handle($request->user());

        return Inertia::render('Trainer/ShareLink', [
            'url' => $link->url,
            'uses_count' => $link->uses_count,
            'recent_uses' => $link->uses()->latest('used_at')->limit(10)->pluck('used_at')->map->toIso8601String()->all(),
        ]);
    }
}
