<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Good;
use App\Models\GoodLog;
use DB;
use Response;

class GoodsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:create goods');
    }

    public function index(Request $request)
    {
        if($request->query('page')==="all"){
            $data =  ['data'=>Good::select('name', 'id', 'id_supplier', 'price')->where('id_supplier', $request->query('supplier'))->get()];
            return response()->json($data);
        }
        return Good::where('name', 'like', '%'.$request->query('name').'%')->with('supplier')->paginate(5);
    }

    public function store(Request $request)
    {
        try {
            $data = Good::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'id_supplier' => $request->id_supplier,

                // Add other fields as needed
            ]);

            GoodLog::create([
                'good_id' => $data->id,
                'price' => $request->price
            ]);
            
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

    public function update(Request $request, $id)
    {
        try {
            $goods = Good::find($id);
            if (empty($goods)) {
                return Response::json([
                    'success' => false,
                ], 404);
            }

            if($goods->price !== $request->price){
                GoodLog::create([
                    'good_id' => $goods->id,
                    'price' => $request->price
                ]);
            }
            
            $goods->name = $request->name;
            $goods->description = $request->description;
            $goods->price = $request->price;
            $goods->id_supplier = $request->id_supplier;
            
            // Update other fields as needed
            $goods->save();

            

            return Response::json([
                'success' => true,
                'data' => $goods
            ], 200);

        } catch (\Exception $err) {
            dd($err);
            return Response::json([
                'success' => false,
            ], 400);
        }
    }
    public function log($id){
        return Response::json([
            'data' => GoodLog::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as date'), 'price as value')->where('good_id', $id)->orderBy('created_at', 'ASC')->get(),
        ], 200);
    }
    public function destroy($id)
    {
        try {
            $goods = Good::find($id);
            if ($goods) {
                $goods->delete();
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
            $good = Good::find($id);
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
                'success' => false,
            ], 404);
        }
    }
}
