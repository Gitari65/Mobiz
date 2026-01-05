<?php
namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    // GET /api/roles - list all roles
    public function index()
    {
        $roles = Role::all();
        return response()->json(['roles' => $roles, 'data' => $roles]);
    }
}
