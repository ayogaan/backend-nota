<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BuildingInstallment;
use App\Models\BuildingNote;
use Response;
use App\Models\AdditionalAmount;
class BuildingInstallmentController extends Controller
{
    public function store(Request $request){
        
        $note = BuildingNote::find($request->note_id);
        if (!$note) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        $additional =  AdditionalAmount::where('note_id', $request->note_id)->sum('amount');
        $installment = BuildingInstallment::where('note_id', $request->note_id)->where('type', 'installment')->sum('amount');
        $reimburse = BuildingInstallment::where('note_id', $request->note_id)->where('type', 'reimburse')->sum('amount');

        
        $max_amount = $request->type === 'installment' ? ($additional + $note->amount) - $installment : $installment - $reimburse ; 
        
        if($request->amount > $max_amount){
            return response()->json([
                'success' => false,
                'message' => 'amount to large'
            ], 422);
        }
        $data = BuildingInstallment::create([
            'amount' => $request->amount,
            'note_id' => $request->note_id,
            'type' => $request->type,
            'created_at' => $request->date,

        ]);
        
    
        if($data){
            return Response::json([
                'success' => true,
                'data' => $data,
                'message' => 'building created'
            ], 200);
        }

        return Response::json([
            'success' => false,
        ], 500);
    }
    public function destroy($id)
    {
        try {
            $building = BuildingInstallment::find($id);
            if ($building) {
                $building->delete();
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
            $building = BuildingInstallment::find($id);
                   
            if(empty($building)){
                return Response::json([
                    'success' => false,
                ], 400);
            }   

            return Response::json([
                'success' => true,
                'data' => $building
            ], 200); 
        }catch(\Exception $err){
            return Response::json([
                'success' => false,
            ], 404);
        }
    }
}
