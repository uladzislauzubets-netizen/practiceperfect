<?php

declare(strict_types=1);

namespace App\Http\Controllers\Trainer;

use App\Actions\Trainer\UpdateBranding;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\UpdateBrandingRequest;
use App\Services\BrandingResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function edit(Request $request, BrandingResolver $branding): Response
    {
        $current = $branding->forTrainer($request->user());

        return Inertia::render('Trainer/Branding', [
            'saved' => [
                'color' => $request->user()->trainerProfile->brand_color,
                'logo_url' => $current['logo_url'],
            ],
            'default_color' => BrandingResolver::DEFAULT_COLOR,
        ]);
    }

    public function update(UpdateBrandingRequest $request, UpdateBranding $update): RedirectResponse
    {
        $update->handle(
            $request->user(),
            $request->validated('brand_color'),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        return back()->with('success', 'Branding saved. Your players and coaches see it right away.');
    }

    public function reset(Request $request, UpdateBranding $update): RedirectResponse
    {
        $update->resetColor($request->user());

        return back()->with('success', 'Brand color reset to the default.');
    }
}
