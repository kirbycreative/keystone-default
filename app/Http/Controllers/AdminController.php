<?php

namespace App\Http\Controllers;

abstract class AdminController
{
    public function __construct()
    {


        page()->addClass('dark');
    }
}
