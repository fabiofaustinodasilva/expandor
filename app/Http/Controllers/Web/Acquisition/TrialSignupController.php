<?php

namespace App\Http\Controllers\Web\Acquisition;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class TrialSignupController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->to(route('marketplace.home').'#demo');
    }

    public function store(): RedirectResponse
    {
        return redirect()->to(route('marketplace.home').'#demo');
    }
}
