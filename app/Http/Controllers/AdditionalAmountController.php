<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdditionalAmount;
class AdditionalAmountController extends Controller
{
    public function index($id){
        $data = AdditionalAmount::where('building_id', $id)->toarray();
        return Response::json([
            'success' => true,
            'data' => $data,
            'message' => 'building created'
        ], 200);
    }

    public function destroy($id)
    {
        try {
            $building = AdditionalAmount::find($id);
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
            $building = AdditionalAmount::find($id);
                   
            if(empty($good)){
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
