<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Response;
use App\Http\Requests\StoreSupplierRequest;

class SupplierController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {   
        if($request->query("page")=="all"){

            $data =  ['data'=>Supplier::select('name', 'id')->get()];
            return response()->json($data);
        }
        return Supplier::where('name', 'like', '%'.$request->query('name').'%')->paginate(5);
    }

    public function show(Request $request, $id){
        try{
            $supplier = Supplier::find($id);
            return Response::json([
                'success' => true,
                'data' => $supplier
            ], 200);         
            if(empty($supplier)){
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

    public function store(StoreSupplierRequest $request)
    {
        try {
            $data = Supplier::create([
                'name' => $request->name,
                'address' => $request->address,
                'contact_person' => $request->contact_person,
                // Add other fields as needed
            ]);
            
            return Response::json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $err) {
            dd($err);
            return Response::json([
                'success' => false,
            ], 400);
        }
    }

    public function update(StoreSupplierRequest $request, $id)
    {
        try {
            $supplier = Supplier::find($id);
            if (empty($supplier)) {
                return Response::json([
                    'success' => false,
                ], 404);
            }

            $supplier->name = $request->name;
            $supplier->address = $request->address;
            $supplier->contact_person = $request->contact_person;
            // Update other fields as needed
            $supplier->save();

            return Response::json([
                'success' => true,
                'data' => $supplier
            ], 200);

        } catch (\Exception $err) {
            return Response::json([
                'success' => false,
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $supplier = Supplier::find($id);
            if ($supplier) {
                $supplier->delete();
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
