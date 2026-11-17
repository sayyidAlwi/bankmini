<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Redirect the authenticated user to the dashboard for their role.
     */
    public function index(Request $request): RedirectResponse
    {
        $route = $request->user()->dashboardRouteName();

        abort_if($route === null, 403, 'Role akun tidak dikenali.');

        return redirect()->route($route);
    }
}
