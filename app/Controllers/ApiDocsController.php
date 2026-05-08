<?php

namespace App\Controllers;

class ApiDocsController extends BaseController
{
    public function index()
    {
        return view('api_docs/swagger');
    }
}
