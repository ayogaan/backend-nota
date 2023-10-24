<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\InstallmentTransaction;

use Response;
use Carbon\Carbon;
use App\Models\Note;
class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $transaction = Note::with('supplier')->selectRaw('SUM(transactions.total_amount) as total_amount, transactions.id_notes, project_id, notes.created_at, notes.is_pay_later, notes.id, notes.supplier_id')
        ->join('transactions', 'transactions.id_notes', '=', 'notes.id');
        if($request->query('id_project')){
            $transaction = $transaction->where('id_project',$request->query('id_project'));
        }
        
        if($request->query('good_name')){
            $transaction = $transaction->where('id_project',$request->query('id_project'));
        }

        if($request->query('start_date')){
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date') ?? Carbon::now();
            $transaction = $transaction->whereBetween('notes.created_at', [$startDate, $endDate]);
        }
        //return $transaction->paginate(5);
        return $transaction
        ->groupBy('notes.id')
        ->paginate(5);
    }

    public function store(Request $request)
    {
        //dd($request->data[0]['id_good']);
        try {
            $note =  Note::create([
                'supplier_id' => $request->noteData['supplier_id'],
                'project_id' => $request->noteData['id_project'],
                'is_pay_later' => $request->noteData['is_pay_later'], 
                'created_at' => $request->noteData['note_date'], 

            ]);
            $total = 0;
            foreach($request->data as $d){
                $data = Transaction::create([
                    'id_notes' => $note->id,
                    'id_good' => $d['id_good'],
                    'quantity' => $d['quantity'],
                    'total_amount' => $d['total_amount'],
                    'id_project'=> $request->noteData['id_project'],
                    'created_at'=> $request->noteData['note_date']

                ]);
                $total += (int)$d['total_amount'];
            }
           
            if($note->is_pay_later == 0){
                //dd("asu");

                InstallmentTransaction::create([
                    'note_id' => $note->id,
                    'installment_number' => 1,
                    'amount' => $total,
                ]);
            }
                return Response::json([
                    'success' => true,
                    'data' => $data
                ], 200);
            
        } catch (\Exception $err) {
            dd($err);
            return Response::json([
                'success' => $err,
            ], 400);
        }
    }

    public function show($id){
        return Note::selectRaw('SUM(transactions.total_amount) as total_amount, transactions.id_notes, project_id, notes.created_at, notes.is_pay_later, notes.id')
        ->join('transactions', 'transactions.id_notes', '=', 'notes.id')->where('notes.id', $id)->groupBy('notes.id')->with('transactions')->first();
    }

    public function update(Request $request, $id)
    {
        try {
            $transaction = Note::find($id);
            if (empty($transaction)) {
                return Response::json([
                    'success' => false,
                ], 404);
            }

            $transaction->supplier_id = $request->noteData['supplier_id'];
            $transaction->project_id = $request->noteData['id_project'];
            $transaction->is_pay_later =  $request->noteData['is_pay_later'];
            $transaction->created_at = $request->noteData['note_date'];
            // Update other fields as needed
            $transaction->save();

            Transaction::where('id_notes', $id)->delete();
            $total = 0;
            foreach($request->data as $d){
                $data = Transaction::create([
                    'id_notes' => $id,
                    'id_good' => $d['id_good'],
                    'quantity' => $d['quantity'],
                    'total_amount' => $d['total_amount'],
                    'id_project'=> $request->noteData['id_project'],
                    'created_at'=> $request->noteData['note_date']

                ]);
                $total += (int)$d['total_amount'];
            }
            InstallmentTransaction::where('note_id', $id)->delete();

            if(!$transaction->is_pay_later){
                InstallmentTransaction::create([
                    'note_id' => $id,
                    'installment_number' => 1,
                    'amount' => $total,
                ]);
            }
                return Response::json([
                    'success' => true,
                    'data' => $transaction->is_pay_later
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
            $transaction = Transaction::find($id);
            if ($transaction) {
                $transaction->delete();
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






