<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserssController extends Controller
{
    public function index(){
    	return view('welcome');
    }

    public function index1()
    {
        return "ok";
    }
}
