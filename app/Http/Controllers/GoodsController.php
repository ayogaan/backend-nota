<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Good;
use Response;

class GoodsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if($request->query('page')==="all"){
            $data =  ['data'=>Good::select('name', 'id', 'id_supplier', 'price')->where('id_supplier', $request->query('supplier'))->get()];
            return response()->json($data);
        }
        return Good::with('supplier')->paginate(5);
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
