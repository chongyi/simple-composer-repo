<?php
/**
 * ManagerController.php
 *
 * @copyright 袁野 <yuanye@yunsom.com>
 * @link      https://insp.top
 */

namespace App\Http\Controllers;


class ManagerController extends Controller
{
    public function home()
    {
        return view('home');
    }
}