<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BuildingRefund;
use Response;
class BuildingRefundController extends Controller
{
    public function store(Request $request){
        $data = BuildingRefund::create([
            'created_at' => $request->date,
            'amount' => $request->amount,
            'building_id' => $request->building_id,
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
            $building = BuildingRefund::find($id);
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
            $building = BuildingRefund::find($id);
                   
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
