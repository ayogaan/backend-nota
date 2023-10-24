<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Response;
use App\Http\Requests\StoreUserRequest;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {   
        if($request->query("page")=="all"){

            $data =  ['data'=>User::get()];
            return response()->json($data);
        }
        return User::paginate(5);
    }

    public function show(Request $request, $id){
        try{
            $user = User::find($id);
            return Response::json([
                'success' => true,
                'data' => $user
            ], 200);         
            if(empty($user)){
                return Response::json([
                    'success' => false,
                ], 400);
            }   
        }catch(\Exception $err){
           
            return Response::json([
                'success' => false,
            ], 404);
        }
    }

    
    

    public function destroy($id)
    {
        try {
            $user = User::find($id);
            if ($user) {
                $user->delete();
                return Response::json([
                    'success' => true,
                    'message' => 'Data deleted'
                ], 200);
            } else {
                return Response::json([
                    'success' => false,
                ], 404);
            }

        } catch (\Exception $err) {
            return Response::json([
                'success' => false,
            ], 400);
        }
    }
}
