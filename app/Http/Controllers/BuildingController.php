<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Building;
use App\Models\AdditionalAmount;
use Response;

class BuildingController extends Controller
{
    public function store(Request $request){
        $data = Building::create([
            'name' => $request->name,
            'isSold' => $request->isSold,
            'amount' => $request->amount,
            'created_at' => $request->date
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

    public function update($id, Request $request){
        $data = Building::find($id);
        if ($data) {
            $data->update([
                'name' => $request->name,
                'isSold' => $request->isSold,
                'amount' => $request->amount,
                'created_at' => $request->date
            ]);
        }
        // AdditionalAmount::where('building_id', $id)->delete();      
        // foreach($request->additionals as $additional){
        //     AdditionalAmount::create([
        //         'building_id' => $data->id, 
        //         'amount' => $additional['amount'],
        //         'note' => $additional['note'],
        //         'created_at' => $additional['date']
        //     ]);
        // }

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

    public function index(Request $request){
        if($request->query('start_date')){
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date') ?? Carbon::now();
            return Building::whereBetween('created_at', [$startDate, $endDate])->where('name', 'like', '%'.$request->query('name').'%')->paginate(5);
        }
        $data = Building::where('name', 'like', '%'.$request->query('name').'%')->paginate(5);
        return $data;
    }

    public function destroy($id)
    {
        try {
            $building = Building::find($id);
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
            $building = Building::where('id', $id)->first();
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
                'error' => $err
            ], 404);
        }
    } 
}
