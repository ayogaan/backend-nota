<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstallmentTransaction;
use App\Models\Note;
use App\Models\Transaction;
use Response;

class InstallmentTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $transactionId = $request->query('note_id');
        $data = InstallmentTransaction::where('note_id', $transactionId)->get();
        $total = InstallmentTransaction::where('note_id', $transactionId)->sum('amount');
        $response = [
            'data' => ['data'=>$data, 'total'=> $total],
        ];
        
        return response()->json($response);
    }

    public function store(Request $request)
    {
       
        try {
            $data = InstallmentTransaction::create([
                'note_id' => $request->note_id,
                'installment_number' => 1,
                'amount' => $request->amount,
                'created_at' => $request->date
                // Add other relevant fields here
            ]);
            $totalDebt = Transaction::where('id_notes', $request->note_id)->sum('total_amount');
            $totalInstallment = InstallmentTransaction::where('note_id', $request->note_id)->sum('amount');
            
            $note = Note::where('id', $request->note_id)->first();

            // Loop through the retrieved transactions and update the 'is_pay_later' column
            if($totalDebt == $totalInstallment){
                $note->is_pay_later = 0;
                $note->save();
            }
            return Response::json([
                'success' => true,
                'data' => $data
            ], 200);
            //calculate installment 
            
        } catch (\Exception $err) {
            dd($err);
            return Response::json([
                'success' => false,
            ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $installmentTransaction = InstallmentTransaction::find($id);

            if (empty($installmentTransaction)) {
                return Response::json([
                    'success' => false,
                ], 404);
            }

            $installmentTransaction->note_id = $request->note_id;
            $installmentTransaction->installment_number = $request->installment_number;
            $installmentTransaction->amount = $request->amount;
            // Update other relevant fields here
            
            $installmentTransaction->save();

            return Response::json([
                'success' => true,
                'data' => $installmentTransaction
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
            $installmentTransaction = InstallmentTransaction::find($id);
            if ($installmentTransaction) {
                $installmentTransaction->delete();
                return Response::json([
                    'success' => true,
                    'message' => 'Data deleted'
                ], 200);
            } else {
                return Response::json([
                    'success' => false,
                    'message' => 'Data not found'
                ], 404);
            }

        } catch (\Exception $err) {
            return Response::json([
                'success' => false,
            ], 400);
        }
    }
}
