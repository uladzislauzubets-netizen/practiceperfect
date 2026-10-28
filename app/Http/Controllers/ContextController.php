<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SwitchContextRequest;
use App\Services\ContextResolver;
use Illuminate\Http\RedirectResponse;

class ContextController extends Controller
{
    public function switch(SwitchContextRequest $request, ContextResolver $contexts): RedirectResponse
    {
        $contexts->switch($request->user(), $request->integer('profile_id'), $request->integer('trainer_id'));

        return back();
    }
}
