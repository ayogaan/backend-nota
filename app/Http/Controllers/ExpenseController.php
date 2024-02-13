<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\AdditionalAmount;
use Response;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function store(Request $request){
        $data = Expense::create([
            'amount' => $request->amount,
            'note' => $request->note,
            'created_at' => $request->date,
            'type' => $request->type ?? 'debit',

        ]);
        
        if($data){
            return Response::json([
                'success' => true,
                'data' => $data,
                'message' => 'expense created'
            ], 200);
        }

        return Response::json([
            'success' => false,
        ], 500);
    }

    public function update($id, Request $request){
        $data = Expense::find($id);
        if (!$data) {
            return Response::json([
                'success' => false,
                ], 404);    
        }
        //$data->timestamps = true;

        $data->update([
            'amount' => $request->amount,
            'note' => $request->note,
            'created_at' => Carbon::parse($request->date),
            'type' => $request->type ?? 'debit',
        ]);

        //$data->timestamps = true;
        if($data){
            return Response::json([
                'success' => true,
                'data' => $data,
                'message' => 'expense created'
            ], 200);
        }

        return Response::json([
            'success' => false,
        ], 500);
    }

    public function index(){
        $data = Expense::paginate(5)->toarray();
        return Response::json([
            'success' => true,
            'data' => ['data'=>$data['data'],
                'meta'=>[
                    'next_url'=>$data['next_page_url'],
                    'prev_url'=>$data['prev_page_url']
                ]],
            'message' => 'expense created'
        ], 200);
    }

    public function destroy($id)
    {
        try {
            $expense = Expense::find($id);
            if ($expense) {
                $expense->delete();
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
            $expense = Expense::where('id', $id)->first();
    
            if(empty($expense)){
                return Response::json([
                    'success' => false,
                ], 400);
            }   

            return Response::json([
                'success' => true,
                'data' => $expense
            ], 200); 
        }catch(\Exception $err){
            return Response::json([
                'success' => false,
                'error' => $err
            ], 404);
        }
    } 
}
