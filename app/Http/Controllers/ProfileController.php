<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Region;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService)
    {
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user'    => $request->user(),
            'profile' => $request->user()->profile,
            'regions' => Region::whereNull('parent_id')->get(),
            'section' => 'edit',
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->profileService->update($request->user(), $request->validated(), $request->file('avatar'));

        return Redirect::route('profile.view')->with('status', 'profile-updated');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users',
            'phone'     => 'nullable|string|max:15',
            'address'   => 'nullable|string|max:255',
            'region_id' => 'required|exists:regions,id',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'avatar'    => 'nullable|image|max:2048',
        ]);

        $this->profileService->store($validated, $request->file('avatar'));

        return redirect()->route('profile.view')->with('status', 'profile-created');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', ['password' => ['required', 'current_password']]);

        $user = $request->user();

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function getUserData(Request $request)
    {
        $user    = $request->user();
        $section = '';

        return view('profile.userprofile', compact('user', 'section'));
    }

    public function getUserProducts(Request $request)
    {
        $user     = $request->user();
        $products = $user->products()->get();
        $section  = 'products';

        return view('profile.userproducts', compact('user', 'products', 'section'));
    }

    public function getUserFavorites(Request $request)
    {
        $user      = $request->user();
        $favorites = $user->favoriteProducts()->get();
        $section   = 'favorites';

        return view('profile.userfavorites', compact('favorites', 'user', 'section'));
    }

    public function toggleFavorites(Request $request)
    {
        $isFavorited = $this->profileService->toggleFavorite($request->user(), (int) $request->input('product_id'));

        return response()->json(['status' => 'success', 'isFavorited' => $isFavorited]);
    }

    public function create(Request $request)
    {
        $regions = Region::whereNull('parent_id')->get();
        $user    = $request->user();
        $section = '';

        return view('profile.create_profile', compact('regions', 'user', 'section'));
    }
}
