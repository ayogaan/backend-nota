<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{

    public function __construct(){

    }

    public function update(Request $request){
        try{
            $permissions = $request->permissions;
            $role = Role::findByName('role_name');
            $data = $role->syncPermissions(permissions);
            return Response::json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $err) {
            return Response::json([
                'success' => false,
            ], 400);
        }
    }

    public function index(){
        return Permission::all();
    }

    public function roles(){
        return Role::all();
    }
}
