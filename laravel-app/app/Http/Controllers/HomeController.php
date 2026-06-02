<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Стартовая страница: ссылки на задания и контакты кандидата.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        return view('home');
    }
}
