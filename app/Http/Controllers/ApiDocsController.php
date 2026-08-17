<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiDocsController extends Controller
{
    /**
     * Display the API documentation page.
     */
    public function index()
    {
        $docs = config('api-docs');
        
        return view('docs.api-docs', compact('docs'));
    }
}
