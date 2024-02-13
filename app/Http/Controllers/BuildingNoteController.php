<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BuildingNote;
use Response;
use App\Models\AdditionalAmount;
use App\Models\Expense;
class BuildingNoteController extends Controller
{
    public function __construct()
    {
        
        //$this->middleware('permission:create buildingNotes');
    }

    public function index(Request $request)
    {
        if($request->query('page')==="all"){
            $data =  ['data'=>BuildingNote::where('building_id', $request->query('buildingid'))->get()];
            return response()->json($data);
        }
        return BuildingNote::where('building_id', $request->query('buildingid'))->paginate(5);
    }

    public function store(Request $request)
    {
        //return $request->formData;
        try {
            $data = BuildingNote::create([
                'name' => $request->formData['name'],
                'building_id' => $request->formData['building_id'],
                'amount' => $request->formData['amount'],
                'created_at' => $request->formData['date'],

                // Add other fields as needed
            ]);

            foreach($request->dataExpenses as $expense){
                AdditionalAmount::create([
                    'amount' => $expense['amount'],
                    'note' => $expense['note'],
                    'note_id' => $data->id,
                    'created_at' => $expense['date']
                ]);
            }

            return Response::json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $err) {
            return Response::json([
                'err' => $err,
                'success' => false,
            ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $buildingNotes = BuildingNote::find($id);
            if (empty($buildingNotes)) {
                return Response::json([
                    'success' => false,
                ], 404);
            }
            
            $buildingNotes->name = $request->formData['name'];
            $buildingNotes->building_id = $request->formData['building_id'];
            $buildingNotes->amount = $request->formData['amount'];
            $buildingNotes->created_at = $request->formData['date'];
            
            // Update other fields as needed
            $buildingNotes->save();
            $expenseDelete = AdditionalAmount::where('note_id',$id)->delete();
            foreach($request->dataExpenses as $expense){
                AdditionalAmount::create([
                    'amount' => $expense['amount'],
                    'note' => $expense['note'],
                    'note_id' => $id,
                    'created_at' => $expense['date']
                ]);
            }

            return Response::json([
                'success' => true,
                'data' => $buildingNotes
            ], 200);

        } catch (\Exception $err) {
           
            return Response::json([
                'err'=> $err,
                'success' => false,
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $buildingNotes = BuildingNote::find($id);
            if ($buildingNotes) {
                $buildingNotes->delete();
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
                'err' => $err->getMessage()
            ], 400);
        }
    }

    public function show($id){
        try{
            $good = BuildingNote::where('id',$id)->with('installments', 'expenses')->first();
            
            return Response::json([
                'success' => true,
                'data' => $good
            ], 200);         
            if(empty($good)){
                return Response::json([
                    'success' => false,
                ], 400);
            }   
        }catch(\Exception $err){
            return Response::json([
                'err'=> $err,
                'success' => false,
            ], 404);
        }
    }
}
