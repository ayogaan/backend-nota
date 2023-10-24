<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use Response;
class NoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request){
        if($request->query("page")=="all"){
            $data =  ['data'=>Note::all()];
            return response()->json($data);
        }
        return Note::paginate(5);
    }

    public function store(Request $request){
        try{
        $data = Note::create([
            'supplier_id'=> $request->supplier_id,            
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
        return Note::findOrFail($id);
    }

    public function update(Request $request,$id){
        try{
        $note = Note::find($id);
        if(empty($note)){
            return Response::json([
                'success' => false,
            ], 404); 
        }
     
        $note->supplier_id =  $request->supplier_id;
        $note->save();
        return Response::json([
            'success' => true,
            'data' => $note
        ], 200); 

        }catch (err $err){
            return Response::json([
                'success' => false,
            ], 400);
        }
    }

    public function destroy($id){
        try{
            $note = Note::where('id', $id)->delete();
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
