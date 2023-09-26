<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use Response;
class ProjectController extends Controller
{
    public function index(Request $request){
        if($request->query("page")=="all"){
            $data =  ['data'=>Project::select('name', 'id')->where('status', 'ongoing')->get()];
            return response()->json($data);
        }
        return Project::paginate(5);
    }

    public function store(Request $request){
        try{
        $data = Project::create([
            'name'=> $request->name,
            'date_start'=> $request->date_start,
            'status'=> $request->status,

        ]);
        return Response::json([
            'success' => true,
            'data' => $data
        ], 200); 

        }catch (err $err){
            return Response::json([
                'success' => false,
            ], 400);
        }
    }

    public function show($id){
        return Project::findOrFail($id);
    }

    public function update(Request $request,$id){
        try{
        $project = Project::find($id);
        if(empty($project)){
            return Response::json([
                'success' => false,
            ], 404); 
        }
     
        $project->name =  $request->name;
        $project->date_start = $request->date_start;
        $project->status = $request->status;
        $project->save();
        return Response::json([
            'success' => true,
            'data' => $project
        ], 200); 

        }catch (err $err){
            return Response::json([
                'success' => false,
            ], 400);
        }
    }

    public function destroy($id){
        try{
            $project = Project::find($id)->delete();
            return Response::json([
                'success' => true,
                'message' => 'data deleted'
            ], 200);
        }catch(err $err){
            return Response::json([
                'success' => false,
            ], 400);
        }

    }
}
