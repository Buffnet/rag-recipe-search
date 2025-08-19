<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class WebSearchController extends Controller
{
    public function index(): View
    {
        return view('search.index');
    }
}