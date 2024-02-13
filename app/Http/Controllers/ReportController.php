<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as Xlsxreader;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Note;
use App\Models\Project;
use App\Models\Expense;
use App\Models\InstallmentTransaction;
use App\Models\BuildingRefund;
use App\Models\BuildingInstallment;
use App\Models\BuildingNote;
use App\Models\Transaction;
use App\Models\Supplier;
use App\Models\Good;

use Carbon\Carbon;
use DB;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Models\Building;
use Response;

class ReportController extends Controller
{

    public function __construct()
    {
        //$this->middleware('auth');
    }

    protected $months = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
    ];

    protected function addBordersToSpreadsheet($sheet, string $range, string $borderStyle = Border::BORDER_THIN, string $borderColor = '000000') {
    
        // Set border for the specified range of cells
        $borderStyleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => $borderStyle,
                    'color' => ['rgb' => $borderColor],
                ],
            ],
        ];
        $sheet->getStyle($range)->applyFromArray($borderStyleArray);
    
        
    }

    public function index(Request $request){
        $year = $request->selectedYear;
        $monthStart = array_search($request->selectedMonthStart, $this->months)+1;
        $monthFinish = array_search($request->selectedMonthFinish, $this->months)+1;
        $dateStart = Carbon::parse($year.'-'.$monthStart.'-01');
        $dateFinish = Carbon::parse($year.'-'.$monthFinish.'-01')->endOfMonth();
        $project = Project::find($request->selectedProject);
        //return $project;
        $notes = Note::join('suppliers', 'suppliers.id', 'notes.supplier_id' )->where('is_pay_later', 1)->whereBetween('notes.created_at', [$dateStart, $dateFinish])->where('project_id', $request->selectedProject)->select('notes.*', 'suppliers.name as supplier_name')->with('transactions', 'installments')->get();
        $summary = Note::where('is_pay_later', 1)
        ->select('notes.*', 'suppliers.name as supplier_name', DB::raw('SUM(total_amount) as total_amount'))->with('installments')
        ->join('suppliers', 'suppliers.id', '=', 'notes.supplier_id')
        ->join('transactions', 'transactions.id_notes', '=', 'notes.id')
       // ->leftJoin('installments', 'installments.note_id', '=', 'notes.id')
        ->whereBetween('notes.created_at', [$dateStart, $dateFinish])
        ->where('project_id', $request->selectedProject)
        ->groupBy('notes.supplier_id')
        ->get();
        
        $suppliers = Note::join('suppliers', 'suppliers.id', 'notes.supplier_id' )->select('suppliers.name')->distinct()->pluck('name');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A3', 'LAPORAN MATERIAL BELUM TERBAYAR');
        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A4', "PROJECT ".$project->name);
        $sheet->mergeCells('A4:H4');
        $this->setAsHeader($sheet,'A3');
        $this->setAsHeader($sheet,'A4');
        $sheet->mergeCells('A4:H4');
        $sheet->setCellValue('A5', 'TGL');
        $sheet->setCellValue('C5', 'NAMA TOKO');
        $sheet->setCellValue('D5', 'NAMA BARANG');
        $sheet->setCellValue('E5', 'UNIT');
        $sheet->setCellValue('F5', 'HARGA');
        $sheet->setCellValue('G5', 'TOTAL');
        $sheet->setCellValue('H5', 'SUB TOTAL');
        $sheet->mergeCells('A5:B5');
        $this->setAsHeader($sheet,'A5');
        $this->setAsHeader($sheet,'C5');
        $this->setAsHeader($sheet,'D5');
        $this->setAsHeader($sheet,'E5');
        $this->setAsHeader($sheet,'F5');
        $this->setAsHeader($sheet,'G5');
        $this->setAsHeader($sheet,'H5');

        //GET DATA BELUM TERBAYAR
        $i = 6;
        foreach($notes as $note){
        $sheet->setCellValue('A'.$i, Carbon::parse($note->created_at)->format('m-d'));
        $sheet->setCellValue('C'.$i, $note->supplier_name);
            $subTotal = 0;
            return $note->transactions;
            foreach ($note->transactions as $transac){
                $subTotal += $transac->total_amount;
                $sheet->setCellValue('D'.$i, $transac->good_name);
                $sheet->setCellValue('E'.$i, $transac->quantity);
                $sheet->setCellValue('F'.$i, $transac->total_amount / $transac->quantity);
                $sheet->setCellValue('G'.$i, $transac->total_amount);
                ++$i;
            }
            $sheet->setCellValue('H'.$i-1, $subTotal);

            $newSubTotal = $subTotal;
            foreach ($note->installments as $installment){
                $newSubTotal -= $installment->amount;
                $sheet->setCellValue('C'.$i, Carbon::parse($installment->created_at)->format('m-d'));
                $sheet->setCellValue('D'.$i, "titip");
                $sheet->setCellValue('E'.$i, $installment->amount);
                ++$i;

            }
            $sheet->setCellValue('H'.$i-1, $newSubTotal);
            $i++;
        }
        $sheet->setCellValue('C'.$i, 'Rincian');
        $i++;
        $total = 0;
        $summaryUnpaid = [];
        //return $summary;
        foreach($summary as $sum){
            
            $totalInstallment= 0;
                foreach($sum->installments as $summaryInstallment){
                    $totalInstallment += $summaryInstallment->amount;
                }
                $sheet->setCellValue('C'.$i, $sum->supplier_name );
                $sheet->setCellValue('D'.$i, ((int) $sum->total_amount - (int)$totalInstallment) );
                $total+= ((int) $sum->total_amount - (int)$totalInstallment) ;
                array_push($summaryUnpaid, ['supplier'=>$sum->supplier_name, 'total' => (int) $sum->total_amount - (int)$totalInstallment]);
                $i++;
            }
        $sheet->setCellValue('C'.$i, "total" );
        $sheet->setCellValue('D'.$i, $total);
        
        $this->addBordersToSpreadsheet($sheet, "A3:". 'H'.($i));

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Terbayar');
        $monthStart = array_search($request->selectedMonthStart, $this->months)+1;
        $monthFinish = array_search($request->selectedMonthFinish, $this->months)+1;
        $i = 3;
        $detailTotal = [];
        
        for (; $monthStart<=$monthFinish; $monthStart++){
            
            $notes = Note::join('suppliers', 'suppliers.id', 'notes.supplier_id' )
            ->leftJoin('installments', 'notes.id', 'installments.note_id')
            ->where('is_pay_later', 0)
            ->where('project_id', $request->selectedProject)
            ->havingRaw('YEAR(latest_paid) = ?', [$request->selectedYear])
            ->havingRaw('MONTH(latest_paid) = ?', [$monthStart])
            ->select('notes.*', 'suppliers.name as supplier_name', DB::raw('MAX(installments.created_at) as latest_paid'))->with('transactions', 'installments')->groupBy('notes.id');
            $notesDone = $notes->get();
            if(count($notesDone) !== 0){
                $startAt = $i;
                $sheet2->setCellValue('A'.$i, 'LAPORAN MATERIAL BELUM TERBAYAR BULAN '. strtoupper($this->months[$monthStart - 1]));
                $sheet2->mergeCells('A'.$i.':H'.$i);
                $sheet2->setCellValue('A'.($i+1), "PROJECT ".$project->name);
                $sheet2->mergeCells('A'.($i+1).':H'.($i+1));
                $this->setAsHeader($sheet2,'A'.$i);
                $this->setAsHeader($sheet2,'A'.$i+1);
                $sheet2->mergeCells('A'.($i+1).':H'.($i+1));
                $sheet2->setCellValue('A'.($i+2), 'TGL');
                $sheet2->setCellValue('C'.($i+2), 'NAMA TOKO');
                $sheet2->setCellValue('D'.($i+2), 'NAMA BARANG');
                $sheet2->setCellValue('E'.($i+2), 'UNIT');
                $sheet2->setCellValue('F'.($i+2), 'HARGA');
                $sheet2->setCellValue('G'.($i+2), 'TOTAL');
                $sheet2->setCellValue('H'.($i+2), 'SUB TOTAL');
                $sheet2->mergeCells('A'.($i+2).':B'.($i+2));
                $this->setAsHeader($sheet2,'A'.($i+2));
                $this->setAsHeader($sheet2,'C'.($i+2));
                $this->setAsHeader($sheet2,'D'.($i+2));
                $this->setAsHeader($sheet2,'E'.($i+2));
                $this->setAsHeader($sheet2,'F'.($i+2));
                $this->setAsHeader($sheet2,'G'.($i+2));
                $this->setAsHeader($sheet2,'H'.($i+2));
                $i++;         
            }
            $noteId = [];
            //return($notesDone);
            foreach($notesDone as $note){
            
                
                array_push($noteId, $note->id);
            
                $i+=2;
                $sheet2->setCellValue('A'.$i, Carbon::parse($note->created_at)->format('m-d'));
                $noteDateDone = $i;
                $sheet2->setCellValue('C'.$i, $note->supplier_name);
                $subTotal = 0;
                foreach ($note->transactions as $transac){
                    $subTotal += $transac->total_amount;
                    $sheet2->setCellValue('D'.$i, $transac->good_name);
                    $sheet2->setCellValue('E'.$i, $transac->quantity);
                    $sheet2->setCellValue('F'.$i, $transac->total_amount/$transac->quantity);
                    $sheet2->setCellValue('G'.$i, $transac->total_amount);
                    ++$i;
                }
                
                $sheet2->setCellValue('H'.$i-1, $subTotal);

                $newSubTotal = 0;
                $counter = 1;

                if(count($note->installments) == 1){
                    $sheet2->setCellValue('B'.$noteDateDone, Carbon::parse($note->installments[0]->created_at)->format('m-d'));
                }
                else{
                    foreach ($note->installments as $installment){
                                            
                        $newSubTotal += $installment->amount;
                        $sheet2->setCellValue('C'.$i, Carbon::parse($installment->created_at)->format('m-d'));
                        $sheet2->setCellValue('D'.$i, $counter == count($note->installments) ? "pelunasan" : "titip");
                        $sheet2->setCellValue('E'.$i, $installment->amount);
                        if($counter == count($note->installments)){

                        }
                        ++$i;
                        $counter++;
                    }
                }
              
                $sheet2->setCellValue('H'.$i-1, $newSubTotal);
                $i++;
                
                
                
            }
            if(count($notesDone) !== 0){  

                $sheet2->setCellValue('C'.$i, 'Rincian');
                $i++;
                $total = 0;
                $monthlyPaidSummary = [];
                $summaryDone = Note::with('installments')
                    ->select('notes.*', 'suppliers.name as supplier_name',  DB::raw('MAX(installments.created_at) as latest_paid'))
                    ->join('suppliers', 'suppliers.id', 'notes.supplier_id')
                    ->join('installments', 'installments.note_id', 'notes.id')
                    ->where('project_id', $request->selectedProject)
                    ->whereIn('notes.id',$noteId)
                    ->groupBy('notes.id')
                    ->get();

                $totalAmount = Note::whereIn('notes.id',$noteId)->select(DB::raw('SUM(total_amount) as total_amount'))->join('transactions', 'transactions.id_notes', 'notes.id')->first();
                $summaryByName =  $this->groupArrayByValue($summaryDone, 'supplier_name');              
                
                foreach($summaryByName as $sum){
                    $totalInstallment= 0;                

                    foreach($sum as $s)
                    foreach($s->installments as $summaryInstallment){
                        $totalInstallment += $summaryInstallment->amount;
                    }
                    $sheet2->setCellValue('C'.$i, $s->supplier_name );
                    $sheet2->setCellValue('D'.$i, ((int)$totalInstallment) );
                    
                    array_push($detailTotal, ['supplier'=> $s['supplier_name'],'total'=> '=Terbayar!D'.$i, 'date'=>$monthStart]);
                    $total+= ((int)$totalInstallment);
                    $i++;
                    }
                $sheet2->setCellValue('C'.$i, "total" );
                $sheet2->setCellValue('D'.$i, $total);
                $this->addBordersToSpreadsheet($sheet2, "A".$startAt.":". 'H'.($i));
                
                $i+=2;
                
                }  
            
        }
        
       
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Rincian');
        $i = 2;
        $counter = 2;
        foreach($this->months as $month){
            $sheet3->setCellValue($this->intToAlphabet($counter+1).$i, $month.$request->selectedYear);
            $counter++;
        }
        $sheet3->setCellValue($this->intToAlphabet($counter+1).$i, "Total");

        $sheet3->setCellValue('A'.$i, "Belum Terbayar");
        $sheet->mergeCells('A'.$i.':B'.$i);

        $i++;
        foreach($suppliers as $sup){
            $sheet3->setCellValue('A'.$i, $sup);
            $i++;
        }
        $sheet3->setCellValue('A'.$i, "Total");
        
        $i = 3;
        
        foreach($summaryUnpaid as $detail){
            //$key = array_search($detail['supplier'], $suppliers);
            $index = 0;
            foreach ($suppliers as $sum){
                
                if($sum == $detail['supplier']){$found = $index;}
                $index++;
            }
            //$columnStart = 2;
            //$column = $this->generateColumnCodes(2+$key);
            $sheet3->setCellValue("B".$i+$found, $detail['total']);
        }

        foreach($detailTotal as $detail){
            //$key = array_search($detail['date'], $months);
            $index = 0;
            foreach ($suppliers as $sum){
                if($sum == $detail['supplier']){$found = $index;}
                $index++;
            }
            $sheet3->setCellValue($this->intToAlphabet($detail['date']+2).$i+$found, $detail['total']);
        }

        for($j=3; $j<4+count($suppliers);$j++){
            $sheet3->setCellValue("O".$j,"=SUM(B".$j.":N".$j.")" );
        }
        
        for($counter=2; $counter<4+count($this->months);$counter++){
            $sheet3->setCellValue($this->intToAlphabet($counter).($j-1),"=SUM(".$this->intToAlphabet($counter)."3".":".$this->intToAlphabet($counter).($j-1).")" );
        }
        $this->addBordersToSpreadsheet($sheet3, "A2:". 'O'.(4+count($suppliers)));

        $writer = new Xlsx($spreadsheet);
        $filename = 'hello_world.xlsx';
        $writer->save($filename);
      

        $fileName = 'hello_world.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;       
    }

    protected function setAsHeader($sheet,$column){
        $sheet->getStyle($column)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        $sheet->getStyle($column)->getFont()->setBold(true);
    }

    protected function setHeader($sheet,$column){
        $sheet->getStyle($column)->getFont()->setBold(true);
    }

    protected function generateColumnCodes($count) {
        $columnCodes = [];
        $letters = range('A', 'Z'); // An array of uppercase letters from A to Z
    
        // Generate column codes based on the count
        for ($i = 0; $i < $count; $i++) {
            if ($i < 26) {
                // For columns A to Z
                $columnCodes[] = $letters[$i];
            } else {
                // For columns AA, AB, AC, and so on
                $prefixIndex = floor($i / 26) - 1;
                $suffixIndex = $i % 26;
                $prefix = $letters[$prefixIndex];
                $suffix = $letters[$suffixIndex];
                $columnCodes[] = $prefix . $suffix;
            }
        }
    
        return $columnCodes;
    }
    
    protected function groupArrayByValue($array, $key) {
        $result = [];
        foreach ($array as $item) {
            $value = $item[$key];
            if (!array_key_exists($value, $result)) {
                $result[$value] = [];
            }
            $result[$value][] = $item;
        }
        return $result;
    }

    protected function intToAlphabet($n) {
        $result = '';
        while ($n > 0) {
            $remainder = ($n - 1) % 26;  // Convert 1-based index to 0-based
            $result = chr(ord('A') + $remainder) . $result;
            $n = floor(($n - 1) / 26);
        }
        return $result;
    }

    public function caseFlow(Request $request){
        
        $startDate = Carbon::parse($request->startDate)->startOfDay();
        $endDate = Carbon::parse($request->endDate)->endOfDay();

        $expense = Expense::select([
            \DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as created_at"),
            'amount as kredit',
            'note as keterangan'
        ])->whereBetween('created_at', [$startDate, $endDate])
          ->get()
          ->toArray();
        
        $installments = InstallmentTransaction::selectRaw('*, DATE_FORMAT(installments.created_at, "%Y-%m-%d") as created_at, SUM(amount) as kredit, suppliers.name as keterangan')
            ->whereBetween('installments.created_at', [$startDate, $endDate])
            ->groupBy('note_id')
            ->join('notes', 'notes.id', 'note_id')
            ->join('suppliers', 'suppliers.id', 'supplier_id')
            ->get()
            ->toArray();
        
        $buildingInstallment = BuildingInstallment::selectRaw('*, DATE_FORMAT(building_installments.created_at, "%Y-%m-%d") as created_at, buildings.name as keterangan, SUM(building_installments.amount) as debit')
            ->whereBetween('building_installments.created_at', [$startDate, $endDate])
            ->groupBy('building_id')
            ->join('buildings', 'building_id', 'buildings.id')
            ->get()
            ->toArray();
        
        $buildingRefund = BuildingRefund::selectRaw('*, DATE_FORMAT(building_refunds.created_at, "%Y-%m-%d") as created_at, buildings.name as keterangan, SUM(building_refunds.amount) as kredit')
            ->whereBetween('building_refunds.created_at', [$startDate, $endDate])
            ->groupBy('building_id')
            ->join('buildings', 'building_id', 'buildings.id')
            ->get()
            ->toArray();
        
                    
        $new_array = array_merge($expense, $installments);
        $new_array = array_merge($new_array, $buildingInstallment);
        $new_array = array_merge($new_array, $buildingRefund);
        
        $new_array = $this->arrayGroupBy($new_array);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A2', 'Tanggal');
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('B2', 'Keterangan');
        $sheet->setCellValue('C2', 'Debit');
        $sheet->setCellValue('D2', 'Kredit');
        $sheet->setCellValue('E2', 'Saldo');
        //return $new_array;
        $i = 3;
        foreach($new_array as $data){
            $sheet->setCellValue('A'.$i, $data[0]['created_at']);
            foreach($data as $d){
                $sheet->setCellValue('B'.$i, $d['keterangan']);
                $sheet->setCellValue('C'.$i, $d['debit'] ?? '');
                $sheet->setCellValue('D'.$i, $d['kredit'] ?? '');
                $sheet->setCellValue('E'.$i, '=C'.$i.'-D'.$i);

                $i++;
            }
        
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save('cashflow.xlsx');
        return $new_array;
    }   



    protected function arrayGroupBy($data){
        $groupedByDate = [];
        foreach ($data as $item) {
            $date = $item['created_at'];
            if (!isset($groupedByDate[$date])) {
                $groupedByDate[$date] = [];
            }

            $groupedByDate[$date][] = $item;
        }

        return $groupedByDate;
    }

    public function penjualanRumah(){
        //building left join note finished left join installment
        $result = BuildingNote::with('building', 'installments', 'expenses')->get();

        return Response::json([
            'success' => true,
            'data' => $result,
            'message' => 'building created'
        ], 200);
    }

    public function penjualanRumahExcel(){
        //building left join note finished left join installment
        $data = BuildingNote::with('building', 'installments', 'expenses')->get();

        $spreadsheet = new Spreadsheet();

// Add a worksheet
$sheet = $spreadsheet->getActiveSheet();

// Set column headers
$columnHeaders = [
    'Name',
    'Lokasi',
    'Harga Pokok',
    'Tambahan',
    'Total Penjualan',
    'Tanggal',
    'Jumlah',
    'Piutang',
];

    foreach ($columnHeaders as $index => $header) {
        $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        $sheet->getColumnDimensionByColumn($index + 1)->setWidth(15);

       
    }
    $this->setAsHeader($sheet,'A1');
    $this->setAsHeader($sheet,'B1');
    $this->setAsHeader($sheet,'C1');
    $this->setAsHeader($sheet,'D1');
    $this->setAsHeader($sheet,'E1');
    $this->setAsHeader($sheet,'F1');
    $this->setAsHeader($sheet,'G1');
    $this->setAsHeader($sheet,'H1');
    
    
    // Your data array
    // Iterate over the data and populate the worksheet
    $rowIndex = 2; // Start from the second row, as the first row is for headers
    foreach ($data as $item) {
        $sheet->setCellValueByColumnAndRow(1, $rowIndex, $item['name']);
        $sheet->setCellValueByColumnAndRow(2, $rowIndex, $item['building'][0]['name']);
        $sheet->setCellValueByColumnAndRow(3, $rowIndex, $item['building'][0]['amount']);
        $rowIndexAmount = $rowIndex;

        // You may need to adjust the index based on your actual data structure
        $expenses = $item['expenses'];
        foreach ($expenses as $expense) {
            $rowIndex++;
            $sheet->setCellValueByColumnAndRow(1, $rowIndex, $expense['note']);
            $sheet->setCellValueByColumnAndRow(4, $rowIndex, $expense['amount']);
        }

        // Add the subtotal row
        $rowIndex++;
        $sheet->setCellValueByColumnAndRow(1, $rowIndex, 'Total');
        $sheet->setCellValueByColumnAndRow(5, $rowIndex, '=SUM(D' . ($rowIndex - count($expenses)) . ':D' . ($rowIndex - 1) . ":C".($rowIndexAmount) . ')');
        $rowIndexAmountTotal = $rowIndex;
        
        // Add the installment rows
        $installments = $item['installments'];
        $totalInstallment = 0 ;
        foreach ($installments as $index => $installment) {
            $rowIndex++;
            $sheet->setCellValueByColumnAndRow(1, $rowIndex, $index == 0 ? 'Transaksi' : '');
            $sheet->setCellValueByColumnAndRow(6, $rowIndex, $installment['created_at']);
            $sheet->setCellValueByColumnAndRow(7, $rowIndex, $installment['amount']);
            $totalInstallment+=$installment['amount'];
            // Calculate remaining amount
            $remainingAmount = '=E' . $rowIndexAmountTotal . '-' . $totalInstallment;
            $sheet->setCellValueByColumnAndRow(8, $rowIndex, $index == count($installments) - 1 ? $remainingAmount : '');
        }

        // Move to the next item
        $rowIndex++;
    }
    $this->addBordersToSpreadsheet($sheet, "A1:". 'H'.($rowIndex));
    // Save the spreadsheet to a file
    $writer = new Xlsx($spreadsheet);
    $writer->save('output.xlsx');
    }

    protected function getCurrentAmountBasedOnMonth($data){
        $total = 0;
        foreach($data as $d){
            $total += $d['amount']; 
        }
        $data['totalAmount'] = $total;

        $note = Note::find($data[0]->note_id);
        $getSupplierNoteTransaction = Transaction::where('id_notes', $data[0]->note_id)->join('goods', 'goods.id', 'transactions.id_good')->get();
        //dd($getSupplierNoteTransaction);
        $data['transactions'] = $getSupplierNoteTransaction;
        $prevAmount = InstallmentTransaction::where('note_id', $data[0]->note_id)->sum('amount') - $total;
        $data['prev_amount'] = $prevAmount;
        $data['note'] = Note::where('id',$data[0]->note_id)->with('supplier')->first();
        $data['is_pay_later'] = $note->is_pay_later == 1 ? 'debt' : 'paid';
        return $data;
    }
    protected function sortArrayByNoteDate($dataArray) {
        // Define a custom comparison function for usort
        $compareFunction = function ($a, $b) {
            $carbonA = $a['note_date'];
            $carbonB = $b['note_date'];
            // dd($a, $b);
            // // Convert date strings to DateTime objects for comparison
            // $carbonA = Carbon::createFromFormat('Y-m-d', $dateA);
            // $carbonB = Carbon::createFromFormat('Y-m-d', $dateB);
    
            // Compare DateTime objects
            if ($carbonA == $carbonB) {
                return 0;
            }
            return ($carbonA < $carbonB) ? -1 : 1;
        };
    
        // Use usort to sort the array using the custom comparison function
        usort($dataArray, $compareFunction);
    
        return $dataArray;
    }

    protected function formatRupiahCell($sheet, $cellReference) {
        // Get the cell style
        $style = $sheet->getStyle($cellReference);
    
        // Set the number format to display Rupiah
        $style->getNumberFormat()->setFormatCode('#,##0.00 [$Rp-Indonesian]');
    }
    
    protected function formatRupiahForEntireSheet($sheet) {
        // Iterate through all cells in the sheet
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                // Check if the cell contains a numeric value or starts with an equals sign (=)
                if ((is_numeric($cell->getValue()) && $cell->getValue() >=1000) || (is_string($cell->getValue()) && substr($cell->getValue(), 0, 1) === '=') ) {
                    // Use the function to format the numeric cell or formula result as Rupiah
                    $this->formatRupiahCell($sheet, $cell->getCoordinate());
                }
            }
        }
    }

    public function paidData(Request $request){
        
        $project_id = $request->query('projectId');
        $project  = Project::find($project_id);
        $dateEnd = $request->query('dateEnd');
        $dateStart = $request->query('dateStart');
        $dateEndYearAndMonth = explode("-",$dateEnd);
        $lastDayOfMonth = Carbon::create($dateEndYearAndMonth[0], $dateEndYearAndMonth[1], 1)->endOfMonth();
        $dateEndLastDay = $lastDayOfMonth->toDateString();
        
        $data = InstallmentTransaction::join('notes', 'installments.note_id', 'notes.id')->where('notes.project_id',$project_id)->whereBetween('installments.created_at', [$dateStart.'-01', $dateEndLastDay])->get();
        $noteIds = InstallmentTransaction::join('notes', 'installments.note_id', 'notes.id')->where('notes.project_id',$project_id)->whereBetween('installments.created_at', [$dateStart.'-01', $dateEndLastDay])
                        ->distinct('note_id')
                        ->pluck('note_id')
                        ->toArray();
        //return $installmentIds;
        $dataTransactionGroupedByMonth = InstallmentTransaction::select(DB::raw('DATE_FORMAT(installments.created_at, "%Y-%m") as installment_month'), 'installments.*', 'notes.*', 'installments.created_at', 'suppliers.name as supplier_name')->join('notes', 'installments.note_id', 'notes.id')->join('suppliers', 'notes.supplier_id', 'suppliers.id')->where('notes.project_id',$project_id)->whereBetween('installments.created_at', [$dateStart.'-01', $dateEndLastDay])->get();
        $dataTransactionGroupedByMonth = $this->groupArrayByValue($dataTransactionGroupedByMonth, 'installment_month');
        $suppliers = Supplier::all();
        $dataTransactionPerMonthGroupedByNoteId = [];
        $summaryBySupplier = [];
        $prevAmountBySupplier = [];
        foreach ($dataTransactionGroupedByMonth as $transactionMonth) {
            
            $summaryBySupplier[$transactionMonth[0]->installment_month] = $this->groupArrayByValue($transactionMonth,'supplier_name');
            $transactionPerNote = $this->groupArrayByValue($transactionMonth,'note_id');
            $note = [];
            foreach($transactionPerNote as &$transaction){
                $note[$transaction[0]->note_id]['note'] = Note::where('id',$transaction[0]->note_id)->with('transactions', 'supplier')->first();
                $note[$transaction[0]->note_id]['installment'] = $transaction;
                $month = explode('-',$transactionMonth[0]->installment_month)[1];
                $note[$transaction[0]->note_id]['prev_amount'] = InstallmentTransaction::where('note_id',$transaction[0]->note_id)->whereRaw('month(installments.created_at) <?', [$month] )->get();
                
                //$transaction['note'] = Note::find($transaction[0]->note_id);
            }
            $dataTransactionPerMonthGroupedByNoteId[$transactionMonth[0]->installment_month] = $note;
            //$dataTransactionPerMonthGroupedByNoteId[$transactionMonth[0]->installment_month]['summary'] = $summaryBySupplier;
            
            // $dataTransactionPerMonthGroupedByNoteId[$transactionMonth[0]->installment_month][]['note_info'] = Note::find($transactionMonth[0]->installment_month);
        }
        //return $summaryBySupplier;
        //return $dataTransactionPerMonthGroupedByNoteId;

        $data = $this->groupArrayByValue($data,'note_id');
        $data = array_map([$this,'getCurrentAmountBasedOnMonth'], $data);
        //not in notes_id
        $dontHaveInstallment = Note::where('is_pay_later', 1)->where('project_id', $project_id)
        ->whereNotIn('id', $noteIds)
        ->whereBetween('created_at', [$dateStart.'-01', $dateEndLastDay])->with('supplier', 'transactions')->get();
        $data = $this->groupArrayByValue($data,'is_pay_later');
        //$data['debt'].pus = array_merge($data['debt'], $dontHaveInstallment);
        // data = installment in range date / data terbayar
        if(!$project){
            return null;
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A3', 'LAPORAN MATERIAL BELUM TERBAYAR');
        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A4', "PROJECT ".$project->name ?? '');
        $sheet->mergeCells('A4:H4');
        $this->setAsHeader($sheet,'A3');
        $this->setAsHeader($sheet,'A4');
        $sheet->mergeCells('A4:H4');
        $sheet->setCellValue('A5', 'TGL');
        $sheet->setCellValue('C5', 'NAMA TOKO');
        $sheet->setCellValue('D5', 'NAMA BARANG');
        $sheet->setCellValue('E5', 'UNIT');
        $sheet->setCellValue('F5', 'HARGA');
        $sheet->setCellValue('G5', 'TOTAL');
        $sheet->setCellValue('H5', 'SUB TOTAL');
        $sheet->mergeCells('A5:B5');
        $this->setAsHeader($sheet,'A5');
        $this->setAsHeader($sheet,'C5');
        $this->setAsHeader($sheet,'D5');
        $this->setAsHeader($sheet,'E5');
        $this->setAsHeader($sheet,'F5');
        $this->setAsHeader($sheet,'G5');
        $this->setAsHeader($sheet,'H5');
        $i = 6;
        $noteArr = [];
        $indexBelumTerbayar = [];
        if(!array_key_exists('debt', $data)){
            $data['debt'] = [];
        }

        foreach($data['debt'] as $debt){
            $dataArr = [];
            $dataArr['supplier_name'] = $debt['note']->supplier->name;
            $dataArr['note_date'] = $debt['note']->created_at;
            $dataArr['transactions'] = $debt['transactions'];
            $dataArr['prev_amount'] = $debt['prev_amount'];

            $transactionCount = count($debt) - 5;
            
            $dataArr['installment'] = [];
            for($t=0; $t<$transactionCount; $t++){
                
                array_push( $dataArr['installment'],$debt[$t]);
            }
            array_push( $noteArr,$dataArr);

        }
        foreach($dontHaveInstallment as $note){
                $dataArr = [];
                $dataArr['supplier_name'] = $note->supplier->name;
                $dataArr['note_date'] = $note->created_at;
                $dataArr['transactions'] = $note->transactions;
                $dataArr['installment'] = [];

                array_push( $noteArr,$dataArr);
                
            }
            $noteArr = $this->sortArrayByNoteDate($noteArr);
            foreach($noteArr as $note){
                $barangCounter = 0;
                if($barangCounter == 0){
                    $sheet->setCellValue('A'.$i, $note['note_date']->format('m-d'));
                    $sheet->setCellValue('C'.$i, $note['supplier_name']);
                }
                $cuts = [];
                $transactionIndexArr = [];
                $amountStart = $i;
                $nowCounter = $i;
                foreach($note['transactions'] as $transaction){
                    $sheet->setCellValue('D'.$i, $transaction->name);
                    $sheet->setCellValue('E'.$i, $transaction->quantity);
                    $sheet->setCellValue('F'.$i, $transaction->total_amount/$transaction->quantity);
                    $sheet->setCellValue('G'.$i, '=E'.$i.'*'.'F'.$i);
                   
                    array_push($transactionIndexArr, 'G'.$i);                            

                    if($transaction->total_cuts != null || $transaction->total_cuts !=0){
                        $i++;
                        array_push($cuts, 'G'.$i);                            
                        $sheet->setCellValue('D'.($i),'    diskon'.((float)$transaction->total_cuts/(float)$transaction->quantity).'/satuan');
                        $sheet->setCellValue('F'.($i), $transaction->total_cuts/$transaction->quantity);
                        $sheet->setCellValue('G'.$i, '=F'.($i).'*'.'E'.($i-1));
                    }

                    $barangCounter++;
                    $i++;
                }
                $sumCounter = $i-1;
                if(count($cuts) > 0 ){
                    $sheet->setCellValue('G'.$i, '=SUM('.implode(',', $transactionIndexArr).')-SUM('.implode(',', $cuts).')');

                }else{
                    $sheet->setCellValue('G'.$i, '=SUM(G'.$amountStart.':G'.($i-1).')');
                }
                
                $titipCounter = $i;
                $j = 0;
                foreach($note['installment'] as $installment){
                    $sheet->setCellValue('B'.$i+1, $installment->created_at->format('m-d'));
                    $sheet->setCellValue('D'.$i+1, 'titip');
                    $sheet->setCellValue('E'.$i+1, $installment->amount);
                    $i++;
                    $j++;
                    if($j == count($note['installment'])){
                        $sheet->setCellValue('H'.$i, '=G'.($sumCounter+1).'-SUM(E'.$titipCounter.':E'.($i).')');
                    }
                }

                if(count($note['installment'])<=0){
                    $sheet->setCellValue('H'.$i, '=G'.($sumCounter+1).'-SUM(E'.$titipCounter.':E'.($i).')');
                }


                

                $i+=2;   

            }
            $sheet->setCellValue('C'.$i, 'Rincian');
            $i++;
            $rincianArr = $this->groupArrayByValue($noteArr,'supplier_name');
            
            $rincianCounter = 0;
            foreach($rincianArr as $rincian){
                $sheet->setCellValue('C'.$i, $rincian[0]['supplier_name']);
                $amount = 0;
                $transactions = 0;
                $cuts = 0;
                foreach($rincian as $data){
                    //return($data);
                    $amount+= $this->sumByKey($data['installment'], 'amount');
                    $transactions+= $this->sumByKey($data['transactions'], 'total_amount');
                    $cuts+= $this->sumByKey($data['transactions'], 'total_cuts');
                    
                }

                $rincianCounter = $i;
                $sheet->setCellValue('D'.$i, $transactions-$cuts-$amount);
                $indexBelumTerbayar[$rincian[0]['supplier_name']] = $transactions-$cuts-$amount;
                $i++;
                // if($rincianArr = 0){
                //     $sheet->setCellValue('C'.$i, $note->supplier_name);
                // }
            }
            $sheet->setCellValue('C'.$i, 'Total');
            $sheet->setCellValue('D'.$i, '=sum(D'.$rincianCounter.':'.'D'.($i-1).')');
            $sheet->setCellValue('H'.$i, '=sum(H6'.':'.'H'.($i-1).')');
            $this->addBordersToSpreadsheet($sheet, "A3:". 'H'.($i));
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Terbayar');
            $i = 2;
            $indexTerbayarPerBulan = [];
            //return $dataTransactionPerMonthGroupedByNoteId;
            foreach($dataTransactionPerMonthGroupedByNoteId as $index => $paidData){
                $month =$index;
                $startAt =$i;
                $sheet2->setCellValue('A'.$i, 'LAPORAN MATERIAL TERBAYAR '. $index);
                $sheet2->mergeCells('A'.$i.':H'.$i);
                $sheet2->setCellValue('A'.($i+1), "PROJECT ".$project->name);
                $sheet2->mergeCells('A'.($i+1).':H'.($i+1));
                $this->setAsHeader($sheet2,'A'.$i);
                $this->setAsHeader($sheet2,'A'.$i+1);
                $sheet2->mergeCells('A'.($i+1).':H'.($i+1));
                $sheet2->setCellValue('A'.($i+2), 'TGL');
                $sheet2->setCellValue('C'.($i+2), 'NAMA TOKO');
                $sheet2->setCellValue('D'.($i+2), 'NAMA BARANG');
                $sheet2->setCellValue('E'.($i+2), 'UNIT');
                $sheet2->setCellValue('F'.($i+2), 'HARGA');
                $sheet2->setCellValue('G'.($i+2), 'TOTAL');
                $sheet2->setCellValue('H'.($i+2), 'SUB TOTAL');
                $sheet2->mergeCells('A'.($i+2).':B'.($i+2));
                $this->setAsHeader($sheet2,'A'.($i+2));
                $this->setAsHeader($sheet2,'C'.($i+2));
                $this->setAsHeader($sheet2,'D'.($i+2));
                $this->setAsHeader($sheet2,'E'.($i+2));
                $this->setAsHeader($sheet2,'F'.($i+2));
                $this->setAsHeader($sheet2,'G'.($i+2));
                $this->setAsHeader($sheet2,'H'.($i+2));
                $i+=3;
                $starterPoint = $i;   
                                
                foreach($paidData as $data){
                    $sheet2->setCellValue('A'.($i),$data['note']->created_at->format('m-d'));
                    $sheet2->setCellValue('C'.($i),$data['note']->supplier->name);
                    $amountStart = $i;
                    $amount = 0;
                    $cuts = [];
                    $transactionIndexArr = [];
                    foreach($data['note']->transactions as $transaction){
                        $sheet2->setCellValue('D'.($i),$transaction->good_name);
                        $sheet2->setCellValue('E'.$i, $transaction->quantity);
                        $sheet2->setCellValue('F'.$i, $transaction->total_amount/$transaction->quantity);
                        $sheet2->setCellValue('G'.$i, '=E'.$i.'*'.'F'.$i);
                        array_push($transactionIndexArr, 'G'.$i);                            
                        
                        
                        if($transaction->total_cuts != null || $transaction->total_cuts !=0){
                            $i++;
                            array_push($cuts, 'G'.$i);                            
                            $sheet2->setCellValue('D'.($i),'    diskon'.((float)$transaction->total_cuts/(float)$transaction->quantity).'/satuan');
                            $sheet2->setCellValue('F'.($i), $transaction->total_cuts/$transaction->quantity);
                            $sheet2->setCellValue('G'.$i, '=F'.($i).'*'.'E'.($i-1));
                            $amount+= ($transaction->quantity * $transaction->total_amount/$transaction->quantity - $transaction->total_cuts);
                        }else{

                            $amount+= ($transaction->quantity * $transaction->total_amount/$transaction->quantity);
                        }
                        $i++;
                    }
                    if(count($cuts) > 0 ){
                        $sheet2->setCellValue('G'.$i, '=SUM('.implode(',', $transactionIndexArr).')-SUM('.implode(',', $cuts).')');

                    }else{
                        $sheet2->setCellValue('G'.$i, '=SUM(G'.$amountStart.':G'.($i-1).')');
                    }
                        //$total = $data['prev_amount'];
                    $total = 0;
                    foreach($data['prev_amount'] as $pamount){
                        
                        $total+= $pamount['amount'];
                        $sheet2->setCellValue('B'.$i+1, $pamount->created_at->format('m-d'));
                        $sheet2->setCellValue('D'.$i+1, 'titip di bulan sebelumnya');
                        $sheet2->setCellValue('H'.$i+1, $pamount->amount);
                        $supname =  Note::where('id',$pamount->note_id)->with('supplier')->get();

                        $i++;
                    }
                    $prevAmountBySupplier[$month][$data['note']->supplier->name] = $total;    
                    
                        
                    foreach($data['installment'] as $index => $installment){
                        //return $installment->created_at->format('Y-m');
                        $total += $installment->amount; 
                        $sheet2->setCellValue('B'.$i+1, $installment->created_at->format('m-d'));
                        $sheet2->setCellValue('D'.$i+1, ($amount == $total) ? 'pelunasan' : 'titip');
                        $sheet2->setCellValue('H'.$i+1, $installment->amount);
                        $i++;
                    
                    }

                    
                    $i++;
                }
                    $sheet2->setCellValue('C'.$i, 'rincian');
                    $i++;
                    //return $month;
                    foreach($summaryBySupplier[$month] as $index=> $supplier){
                        $sheet2->setCellValue('C'.$i, $index);
                        $amount = 0;
                        $prevAmount = $prevAmountBySupplier[$month][$index];
                        foreach($supplier as $s){
                            $ym = explode('-', $month);
                            
                            $amount+= $s->amount;
                            $sheet2->setCellValue('D'.$i, $amount+ $prevAmount);

                            $indexTerbayarPerBulan[$month][$index] = $amount;
                        }
                        
                        $i++;
                    }
                    $sheet2->setCellValue('C'.$i+1, 'Total');
                    $sheet2->setCellValue('D'.$i+1, '=sum(D'.($i-1).':D'.$starterPoint.')');
                    $sheet2->setCellValue('H'.$i+1, '=sum(H'.($i-1).':H'.$starterPoint.')');
                    $this->addBordersToSpreadsheet($sheet2, "A".$startAt.":". 'H'.($i+1));
                    
                $i+=10;;
            }
        //sheet 3
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Rincian');
        $rangeMonth = $this->getAllMonthsAndYears($dateStart.'-01', $dateEndLastDay);
        $counter = 3;
        $sheet3->setCellValue('A'.'2', 'Supplier');
        $sheet3->setCellValue('B'.'2', 'Belum Terbayar');
        $i = 2;
        foreach($rangeMonth as $month){
            $sheet3->setCellValue($this->intToAlphabet($counter).$i, $month['month'].' '.$month['year']);
            
            $counter++;
        }

        $i++;
        foreach($suppliers as $supplier){
            $sheet3->setCellValue('A'.$i, $supplier->name);
            $sheet3->setCellValue('B'.$i, $indexBelumTerbayar[$supplier->name] ?? 0);
            $i++;
        }
        $counter = 3;
        
        foreach($rangeMonth as $month){
            $i = 3;
            foreach($suppliers as $index => $supplier){
                $sheet3->setCellValue($this->intToAlphabet($counter).$i, $indexTerbayarPerBulan[$month['year'].'-'.$month['monthNum']][$supplier->name] ?? '');
                
                $i++;
            }
            $sheet3->setCellValue($this->intToAlphabet($counter).$i,"=SUM(".$this->intToAlphabet($counter).'3'.":".$this->intToAlphabet($counter).$i.")" );
            $counter++;
            

        }

        foreach($suppliers as $index => $supplier ) {
            $sheet3->setCellValue(
            $this->intToAlphabet(3 + count($rangeMonth)) . ((int)$index + 3),
            "=SUM(" . $this->intToAlphabet(2) . ((int)$index + 3).':' . $this->intToAlphabet(2 + count($rangeMonth)) . ((int)$index + 3) . ")"
);


        }
        $sheet3->setCellValue("B".$i,"=SUM(B".'3'.":B".$i.")" );
        $sheet3->setCellValue($this->intToAlphabet($counter).$i,"=SUM(".$this->intToAlphabet($counter).'3'.":".$this->intToAlphabet($counter).$i.")" );
        $this->formatRupiahForEntireSheet($sheet);
        $this->formatRupiahForEntireSheet($sheet2);
        $this->formatRupiahForEntireSheet($sheet3);

        
        $this->addBordersToSpreadsheet($sheet3, "A2:". $this->intToAlphabet($counter).$i);
        //return $prevAmountBySupplier;
        //get range date
        $writer = new Xlsx($spreadsheet);
        $filename = 'aaa.xlsx';
        $writer->save($filename);
        return Response::json([
            'success' => true,
            'data' => $prevAmountBySupplier,    
            'message' => 'paid data'
        ], 200);
    }

    protected function sumByKey($array, $key) {
        $sum = 0;
    
        foreach ($array as $item) {
            if (isset($item[$key])) {
                $sum += $item[$key];
            }
        }
    
        return $sum;
    }

    public function getAllMonthsAndYears($startDate, $endDate) {
        $monthsAndYears = array();
    
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
    
        while ($currentDate->lte($endDate)) {
            $month = $currentDate->format('M');
            $year = $currentDate->format('Y');
            $monthNum = $currentDate->format('m');
            $monthsAndYears[] = array('month' => $month, 'year' => $year, 'monthNum' => $monthNum);
    
            $currentDate->addMonth(); // Move to the next month
        }
    
        return $monthsAndYears;
    }

    protected function getBuildingData($data){
        $newData['installment'] = $data;
        $newData['building'] = Building::find($data[0]['building_id']);
        $newData['expenses'] = BuildingNote::where('id', $data[0]['note_id'])->with('expenses')->first()->expenses;
        return $newData;
    }

    protected function buildingWithNoInstallment($data){
        $newData['installment'] = [];
        $newData['building'] = $data;
        return $newData;
    }    
    
    public function buildingReport(){
        $installment = BuildingInstallment::leftJoin('building_notes', 'building_installments.note_id', '=', 'building_notes.id')
        ->select('building_notes.name', 'building_notes.building_id', 'building_installments.*')
        ->get()->toArray();
        $installmentByNote = $this->groupArrayByValue($installment, 'note_id');
        $buildingIds = BuildingInstallment::join('building_notes', 'building_installments.note_id', 'building_notes.id')->select('building_notes.name', 'building_notes.building_id', 'building_installments.*')
                        ->distinct('building_id')
                        ->pluck('building_id')
                        ->toArray();
        $noteWithInstallmentIds = BuildingInstallment::join('building_notes', 'building_installments.note_id', 'building_notes.id')->select('building_notes.name', 'building_notes.building_id', 'building_installments.*')
                        ->distinct('note_id')
                        ->pluck('note_id')
                        ->toArray();
        
        $building = Building::whereNotIn('id',$buildingIds)->get()->toArray();
        $data = array_map([$this,'getBuildingData'], $installmentByNote);

        // $data
        $data[0] = array_map([$this,'buildingWithNoInstallment'], $building);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A4', 'Daftar Kekurangan Pembayaran Konsumen');
        $sheet->mergeCells('A5:A6');
        $sheet->mergeCells('B5:B6');
        $sheet->mergeCells('C5:C6');
        $sheet->mergeCells('A4:I4');
        $sheet->setCellValue('A5', 'No');
        $sheet->setCellValue('B5', 'NAMA');
        $sheet->setCellValue('C5', 'Lokasi');
        $sheet->setCellValue('D5', 'Total Penjualan');
        $sheet->setCellValue('D6', 'Harga Pokok');
        $sheet->setCellValue('E6', 'Tambahan');
        $sheet->setCellValue('F6', 'Total Penjualan');
        $sheet->setCellValue('G5', 'Pembayaran');
        $sheet->setCellValue('G6', 'Tanggal');
        $sheet->setCellValue('H6', 'Jumlah');
        $sheet->setCellValue('I5', 'Piutang');
        $sheet->mergeCells('I6:I5');
        $sheet->mergeCells('G5:H5');
        $sheet->mergeCells('D5:F5');

        $this->setAsHeader($sheet,'A4');
        $this->setAsHeader($sheet,'A5');
        $this->setAsHeader($sheet,'B5');
        $this->setAsHeader($sheet,'C5');
        $this->setAsHeader($sheet,'D5');
        $this->setAsHeader($sheet,'D6');
        $this->setAsHeader($sheet,'E6');
        $this->setAsHeader($sheet,'F6');
        $this->setAsHeader($sheet,'G5');
        $this->setAsHeader($sheet,'G6');
        $this->setAsHeader($sheet,'H6');
        $this->setAsHeader($sheet,'I5');
        
        $i = 7;
        foreach($data as $index =>$d){
            if($index == 0) {
                $startAssetIndex = $i;        

                foreach($d as $dataUnSelled){
                    
                    $sheet->setCellValue('B'.$i, 'Belum Terjual');
                    $this->setHeader($sheet,'B'.$i);
                    $sheet->setCellValue('C'.$i, $dataUnSelled['building']['name']);
                    $sheet->setCellValue('I'.$i, $dataUnSelled['building']['amount']);
                    $endStartAssetIndex =$i;
                    $i++;
                }
                
            }else{
                $name =  $d['installment'][0]['name'];
                $sheet->setCellValue('B'.$i, $name);
                $this->setHeader($sheet,'B'.$i);
                $sheet->setCellValue('C'.$i, $d['building']['name']);
                $sheet->setCellValue('D'.$i, $d['building']['amount']);
                $baseAmountIndex = $i;
                foreach($d['expenses'] as $index=>$expense){
                    $i++;
                    $sheet->setCellValue('B'.$i, $expense['note']);
                    $sheet->setCellValue('E'.$i, $expense['amount']);
                    if($index == count($d['expenses'])-1){
                        $totalAmountIndex = $i;
                        $sheet->setCellValue('F'.$i, '=SUM(D'.$baseAmountIndex.',E'.($baseAmountIndex+1).':E'.$i.')');
                    }
                }
                $i++;
                $installmentAmountIndex = $i;
                $arrCicilan = [];
                $arrPengembalian = [];
                foreach($d['installment'] as $index=>$installment){
                    if($installment['type'] != 'installment' ){
                        $sheet->setCellValue('B'.$i, "pengembalian");
                        array_push($arrPengembalian, 'H'.$i);
                    }else{
                        $sheet->setCellValue('B'.$i, "cicilan");
                        array_push($arrCicilan, 'H'.$i);
                    }
                    $sheet->setCellValue('G'.$i, Carbon::parse($installment['created_at'])->format('m-d')); 
                    $sheet->setCellValue('H'.$i, $installment['amount']);
                    if($index == count($d['installment'])-1){
                        if(count($arrPengembalian) == 0){
                            $sheet->setCellValue('I'.$i, '=D'.$baseAmountIndex.'-SUM('.implode(',',$arrCicilan).')');
                        }else{
                            $sheet->setCellValue('I'.$i, '=D'.$baseAmountIndex.'-SUM('.implode(',',$arrCicilan).')'.'+'.implode('+',$arrPengembalian));
                        }
                    }
                    
                    $i++;
                }
                
            }
        
            $i++;
        }
        $sheet->setCellValue('C'.$i, 'Total Aset dan Sisa Kekurangan Pembayaran Konsumen');
        $sheet->setCellValue('I'.$i, '=SUM(I7:I'.$i.')');

        $sheet->setCellValue('C'.($i+1), 'Stok Rumah');
        if(isset($endStartAssetIndex)){
            $sheet->setCellValue('I'.($i+1), '=SUM(I'.$startAssetIndex.':I'.$endStartAssetIndex.')');
        }
        $sheet->setCellValue('C'.($i+2), 'TOTAL');

        $this->setAsHeader($sheet,'C'.$i);
        $this->setAsHeader($sheet,'C'.($i+1));
        $this->setAsHeader($sheet,'C'.($i+2));
    
        $sheet->mergeCells('C'.$i.':F5');
        $sheet->mergeCells('C'.($i+1).':F5');
        $sheet->mergeCells('C'.($i+2).':F5');
        $this->formatRupiahForEntireSheet($sheet);
        $this->addBordersToSpreadsheet($sheet, "A4:". 'I'.($i+2));
        
        $writer = new Xlsx($spreadsheet);
        $filename = 'aaa9.xlsx';
        
        $writer->save($filename);
    }

    public function kasExpenses(Request $request){
        //get previous kas flow
        
        $yourDate = $request->query('date').'-01';
        $carbonDate = Carbon::createFromFormat('Y-m-d', $yourDate);
        $previousMonth = $carbonDate->format('Y-m-d');
        
        $carbonDate = Carbon::parse($yourDate);
        $dateEndLastDay = Carbon::parse($request->query('date').'-01')->endOfMonth();
        $previousMonth = $carbonDate->subMonth()->firstOfMonth();
        $formattedPreviousMonth = $previousMonth->format('Y-m');
        //return $formattedPreviousMonth;
        // $cashFlowPrevMonth = 
        // $expense = Expense::select('*', DB::raw('DATE_FORMAT(created_at, "%Y/%m")) as formated_day'))
        //             ->whereBetween('created_at', [$yourDate, $dateEndLastDay])
        //             ->groupBy('created_at')->get()->toArray();
        
        $expense = Expense::select('*', DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as formated_date'))
                    ->where('type', 'kredit')
                    ->whereBetween('created_at', [$yourDate, $dateEndLastDay])
                    ->orderBy('created_at', 'ASC')
                    ->get()
                    ->toArray();
        
        $prevExpense = Expense::select('*', DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as formated_date'))
                    ->where('type', 'kredit')
                    ->where('created_at', '<' , $yourDate)
                    ->orderBy('created_at', 'ASC')
                    ->sum('amount');

        $debit = Expense::select('*', DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as formated_date'))
                    ->where('type', 'debit')
                    ->whereBetween('created_at', [$yourDate, $dateEndLastDay])
                    ->orderBy('created_at', 'ASC')
                    ->get()
                    ->toArray();
        
        $prevDebit = Expense::select('*', DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as formated_date'))
                    ->where('type', 'debit')
                    ->where('created_at', '<' , $yourDate)
                    ->orderBy('created_at', 'ASC')
                    ->sum('amount');

    
        
        $prevTransactionInstallment = InstallmentTransaction::where('installments.created_at', '<', $yourDate)
                    ->join('notes', 'installments.note_id', 'notes.id')
                    ->join('suppliers', 'suppliers.id', 'supplier_id')
                    ->select
                        ('installments.*',  DB::raw('CONCAT(suppliers.name, " ", DATE_FORMAT(installments.created_at, "%Y/%m")) as note'),
                        DB::raw('DATE_FORMAT(installments.created_at, "%Y-%m-%d") as formated_date'))
                    
                    ->sum('amount');
        //transaction uang keluar
        $transactionInstallment = InstallmentTransaction::whereBetween('installments.created_at', [$yourDate, $dateEndLastDay])
                                ->join('notes', 'installments.note_id', 'notes.id')
                                ->join('suppliers', 'suppliers.id', 'supplier_id')
                                ->select
                                    ('installments.*',  DB::raw('CONCAT(suppliers.name, " ", DATE_FORMAT(installments.created_at, "%Y/%m")) as note'),
                                    DB::raw('DATE_FORMAT(installments.created_at, "%Y-%m-%d") as formated_date'))
                                
                                ->get()->toArray();
        //pengembalian
        
        $buildingInstallmentReimburse = BuildingInstallment::whereBetween('building_installments.created_at', [$yourDate, $dateEndLastDay])
        ->where('type', 'reimburse')
        ->join('building_notes', 'building_installments.note_id', 'building_notes.id')
        ->join('buildings', 'building_notes.building_id', 'buildings.id')
        ->select(
            DB::raw('DATE_FORMAT(building_installments.created_at, "%Y-%m-%d") as formated_date'),
            'building_installments.*',
            DB::raw('CONCAT( "Pengembalian ", CONCAT(building_notes.name, " ", CONCAT(buildings.name, " ", DATE_FORMAT(building_installments.created_at, "%Y/%m")))) as note')
        )->get()->toArray();

        $prevBuildingInstallmentReimburse = BuildingInstallment::where('building_installments.created_at','<', $yourDate)
        ->where('type', 'reimburse')
        ->join('building_notes', 'building_installments.note_id', 'building_notes.id')
        ->join('buildings', 'building_notes.building_id', 'buildings.id')
        ->select(
            DB::raw('DATE_FORMAT(building_installments.created_at, "%Y-%m-%d") as formated_date'),
            'building_installments.*',
            DB::raw('CONCAT( "Pengembalian ", CONCAT(building_notes.name, " ", CONCAT(buildings.name, " ", DATE_FORMAT(building_installments.created_at, "%Y/%m")))) as note')
        )->sum('building_installments.amount');
        
        $buildingInstallment = BuildingInstallment::whereBetween('building_installments.created_at', [$yourDate, $dateEndLastDay])
        ->where('type', 'installment')
        ->join('building_notes', 'building_installments.note_id', 'building_notes.id')
        ->join('buildings', 'building_notes.building_id', 'buildings.id')
        ->select(
            DB::raw('DATE_FORMAT(building_installments.created_at, "%Y-%m-%d") as formated_date'),
            'building_installments.*',
            DB::raw('CONCAT( "Pembayaran ", CONCAT(building_notes.name, " ", CONCAT(buildings.name, " ", DATE_FORMAT(building_installments.created_at, "%Y/%m")))) as note')
        )
        ->get()->toArray();
        
        $prevBuildingInstallment = BuildingInstallment::where('building_installments.created_at', '<', $yourDate)
        ->where('type', 'installment')
        ->join('building_notes', 'building_installments.note_id', 'building_notes.id')
        ->join('buildings', 'building_notes.building_id', 'buildings.id')
        ->select(
            DB::raw('DATE_FORMAT(building_installments.created_at, "%Y-%m-%d") as formated_date'),
            'building_installments.*',
            DB::raw('CONCAT("Pembayaran ", CONCAT(building_notes.name, " ", CONCAT(buildings.name, " ", DATE_FORMAT(building_installments.created_at, "%Y/%m")))) as note')
        )
        ->sum('building_installments.amount'); // Remove 'building_installments.amount'



        $keyToAdd = 'type';
        $valueToAdd = 'kredit';
        //return($prevBuildingInstallment);
        $addKreditFunction = function (&$object) use ($keyToAdd, $valueToAdd) {
            $object[$keyToAdd] = $valueToAdd;
        };

        $spendings = array_merge($expense, $transactionInstallment, $buildingInstallmentReimburse);
        $gainings = array_merge($buildingInstallment, $debit);

       
        array_walk($spendings, $addKreditFunction);
        $valueToAdd = 'debit';

        $addDebitFunction = function (&$object) use ($keyToAdd, $valueToAdd) {
            $object[$keyToAdd] = $valueToAdd;
        };
        array_walk($gainings, $addDebitFunction);
        $data = array_merge($spendings, $gainings);
        $data = $this->groupArrayByValue($data,'formated_date');
        ksort($data);
          	  	  
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Laporan Arus Kas '. $request->query('date'));
        $sheet->setCellValue('A3', 'Tanggal');
        $sheet->setCellValue('B3', 'KETERANGAN');
        $sheet->setCellValue('C3', 'DEBET');
        $sheet->setCellValue('D3', 'KREDIT');
        $sheet->setCellValue('E3', 'SALDO');
        
        $this->setAsHeader($sheet,'A1');
        $this->setAsHeader($sheet,'A3');
        $this->setAsHeader($sheet,'B3');
        $this->setAsHeader($sheet,'C3');
        $this->setAsHeader($sheet,'D3');
        $this->setAsHeader($sheet,'E3');
        $sheet->mergeCells('A1:E1');

        $i =4;

        
        $sheet->setCellValue('B'.$i, 'bulan sebelumnya');
        $sheet->setCellValue('E'.$i, ($prevBuildingInstallment + $prevDebit) - $prevBuildingInstallmentReimburse - $prevTransactionInstallment - $prevExpense);
        $i++;

        foreach($data as $index =>$d){
            $sheet->setCellValue('A'.$i, $index);
            
            foreach($d as $index=>$dataPerDate){
                $pos = 'D';
                if($dataPerDate['type']=='debit'){
                    $pos = 'C';
                }
                $sheet->setCellValue('B'.$i, $dataPerDate['note']);
                $sheet->setCellValue($pos.$i, $dataPerDate['amount']);
                $sheet->setCellValue('E'.$i, '=E'.($i-1).'-D'.$i.'+C'.$i);
                $i++;
            }
        }
        $this->formatRupiahForEntireSheet($sheet);

        $this->addBordersToSpreadsheet($sheet, "A3:". 'E'.($i-1));
        $spreadsheet->getActiveSheet()->calculateWorksheetDimension();

        foreach ($spreadsheet->getActiveSheet()->getColumnIterator() as $column) {
            $spreadsheet->getActiveSheet()
                ->getColumnDimension($column->getColumnIndex())
                ->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        $filename = 'aaa1.xlsx';
        $writer->save($filename);
        return Response::json([
            'success' => true,
            'data' => $data,
            'previousData' => ($prevBuildingInstallment + $prevDebit) - $prevBuildingInstallmentReimburse - $prevTransactionInstallment - $prevExpense,
            'message' => 'building created'
        ], 200);
    }

    // public function installmentUntilMonth(Request $request){
    //     $project_id = $request->query('projectId');
    //     $project  = Project::find($project_id);
    //     $dateStart = $request->query('dateStart');
    //     $dateEnd = $request->query('dateEnd');
    //     $dateEndYearAndMonth = explode("-",$dateEnd);
    //     $lastDayOfMonth = Carbon::create($dateEndYearAndMonth[0], $dateEndYearAndMonth[1], 1)->endOfMonth();
    //     $dateEndLastDay = $lastDayOfMonth->toDateString();
        
    //     //$rangeMonth = $this->getAllMonthsAndYears($dateStart.'-01', $dateEndLastDay);
    //     $installments = InstallmentTransaction::whereBetween('installments.created_at',[$dateStart.'-01',$dateEndLastDay])->where('notes.is_pay_later', 1)
    //         ->join('notes', 'installments.note_id', 'notes.id')
    //         ->join('suppliers', 'notes.supplier_id', 'suppliers.id')

    //         ->select(
    //             'suppliers.name as supplier_name',
    //             'installments.*',
    //             db::raw("DATE_FORMAT(installments.created_at, '%y-%m' ) as month")
    //         )->get();
        
    //         $installmentIds = InstallmentTransaction::whereBetween('installments.created_at', [$dateStart.'-01',$dateEndLastDay])
    //         ->join('notes', 'installments.note_id', '=', 'notes.id')
    //         ->join('suppliers', 'notes.supplier_id', '=', 'suppliers.id')
    //         ->where('notes.is_pay_later', 1)
    //         ->select(
    //             'suppliers.name as supplier_name',
    //             'installments.*',
    //             DB::raw("DATE_FORMAT(installments.created_at, '%y-%m' ) as month")
    //         )
    //         ->distinct()
    //         ->pluck('note_id')
    //         ->toArray();
        
        
    //     $installments =  $this->groupArrayByValue($installments, 'note_id');
    //     $installmentData = [];
    //     $haveNoInstallments = Note::whereBetween('notes.created_at',[$dateStart.'-01',$dateEndLastDay] )
    //     ->where('is_pay_later', 1)
    //     ->join('suppliers', 'suppliers.id', 'notes.supplier_id')
    //     ->select('notes.*', 'suppliers.name as supplier_name', db::raw("DATE_FORMAT(notes.created_at, '%y-%m') as month"))
    //     ->with('transactions')
    //     ->whereNotIn('notes.id',$installmentIds)
    //     ->get();
    //     foreach($installments as $index=> &$installment){
    //         $installment = $this->groupArrayByValue($installment, 'month');
    //         foreach($installment as $month => &$installmentPerMonth){
    //             $amount = 0;
    //             foreach($installmentPerMonth as $dataIns){
    //                 $amount+=$dataIns['amount'];
    //             }
    //             $installmentPerMonth['installments'] = $amount;
    //             $installmentPerMonth['supplier_name'] = $installmentPerMonth[0]['supplier_name'];
    //             $installmentPerMonth['transaction_amount'] = Transaction::where('id_notes', $index)->sum('total_amount');
    //             $installmentPerMonth['previous_amount'] = InstallmentTransaction::where('note_id', $index)->where("installments.created_at",'<',$month.'-01')->sum('amount');
    //             $installmentData[$installmentPerMonth[0]['supplier_name']][$index][$month] = [
    //                                             'installment'=> $installmentPerMonth['installments'],
    //                                             'supplier_name'=>$installmentPerMonth['supplier_name'],
    //                                             'transaction_amount'=>$installmentPerMonth['transaction_amount'],
    //                                             'previous_amount'=>$installmentPerMonth['previous_amount']

    //                                         ];

    //         }
    //     }
    //     $debtPerDate = [];
    //     foreach($installmentData as $data){
    //         foreach($data as $dataNote){
    //             foreach($dataNote as $month => $installmentDate){
                    
    //                 $debtPerDate[$installmentDate['supplier_name']][$month] = $installmentDate;
                    
    //             }
    //         }
    //     }
        
    //     $debtHaveNoInstallment = [];
    //     $amount = 0;
    //     foreach($haveNoInstallments as &$note){
            
    //         foreach ($note['transactions'] as $key => $transaction) {
    //             $amount+=(int)$transaction['total_amount'];
    //             $debtHaveNoInstallment[$note['supplier_name']][$note['month']] = [
    //                                             'installment'=> 0,
    //                                             'supplier_name'=>$note['supplier_name'],
    //                                             'transaction_amount'=>$amount,
    //                                             'previous_amount'=>0
    //             ];
    //         }
    //     }
    //     $combinedArray = $debtPerDate;
    //     foreach ($debtHaveNoInstallment as $key => $value) {
            
    //         if (!array_key_exists($key, $combinedArray)) {
    //             $combinedArray[$key] = $value;
    //         }else{
    //             $combinedArray[$key]+= $value;
    //         }
    //     }

    //     $spreadsheet = new Spreadsheet();
    //     $sheet = $spreadsheet->getActiveSheet();
    //     $sheet->setCellValue('A3', 'EKSPEDISI SUPPLIER');
    //     $counter = 2;
    //     $i = 4;
    //     $rangeMonth = $this->getAllMonthsAndYears($dateStart.'-01', $dateEndLastDay);

    //     foreach($rangeMonth as $month){
    //         $sheet->setCellValue($this->intToAlphabet($counter+1).$i, $month['month'].$month['year']);
    //         $counter++;
    //     }
    //     $sheet->setCellValue('A'.$i, 'No');
    //     $sheet->setCellValue('B'.$i, 'supplier name');

    //     $i++;
    //     $suppliers = Supplier::all();
    //     foreach($suppliers as $supplier){
    //         $counter = 2;
    //         $sheet->setCellValue('B'.$i, $supplier->name);

    //         foreach ($rangeMonth as $month){
    //             $amount = 
    //             (($combinedArray[$supplier->name][substr($month['year'], -2).'-'.$month['monthNum']]['transaction_amount'] ?? 0) -  
    //             ($combinedArray[$supplier->name][substr($month['year'], -2).'-'.$month['monthNum']]['installment'] ?? 0) 
    //             - ($combinedArray[$supplier->name][substr($month['year'], -2).'-'.$month['monthNum']]['previous_amount'] ?? 0));
    //             $sheet->setCellValue($this->intToAlphabet($counter+1).$i,
    //             ($amount > 0) ? $amount : '');
                
    //             $counter++;
    //         }
    //         $sheet->setCellValue($this->intToAlphabet($counter+1).$i, "=sum(C".$i.":".$this->intToAlphabet($counter+1).$i.")");

    //         $i++;
    //     }
    //     $sheet->setCellValue($this->intToAlphabet($counter+1).$i, "=sum(C4".":".$this->intToAlphabet($counter).($i-1).")");

    //     $sheet->setCellValue('B'.$i, 'total');
    //     $counter =2;
    //     foreach($rangeMonth as $month){
    //         $sheet->setCellValue($this->intToAlphabet($counter+1).$i, "=sum(".$this->intToAlphabet($counter+1).$i.":".$this->intToAlphabet($counter+1)."5)");
    //         $counter++;
    //     }
        
    //     $this->addBordersToSpreadsheet($sheet, 'A4:'.$this->intToAlphabet($counter+1).$i);
    //     $writer = new Xlsx($spreadsheet);
    //     $filename = 'aaa2.xlsx';
        
    //     $writer->save($filename);
    //     return Response::json([
    //         'success' => true,
    //         'data' => $combinedArray,
    //         'message' => 'building created'
    //     ], 200);

    // }


    public function debtTracker(Request $request){
        $project_id = $request->query('projectId');
        $project  = Project::find($project_id);
        $dateStart = $request->query('dateStart');
        $dateEnd = $request->query('dateEnd');
        $dateEndYearAndMonth = explode("-",$dateEnd);
        $lastDayOfMonth = Carbon::create($dateEndYearAndMonth[0], $dateEndYearAndMonth[1], 1)->endOfMonth();
        $dateEndLastDay = $lastDayOfMonth->toDateString();
        
        //$rangeMonth = $this->getAllMonthsAndYears($dateStart.'-01', $dateEndLastDay);
        $notes = Note::whereBetween('created_at',[$dateStart.'-01',$dateEndLastDay])->where('notes.is_pay_later', 1)
            ->select('*', DB::raw('DATE_FORMAT(created_at, "%Y-%m") as formated_date'))
            ->with(
                'supplier',
                'transactions',
                'installments'
            )->get();        
        $notesGroupByMonth = $this->groupArrayByValue($notes, 'formated_date');
        foreach ($notesGroupByMonth as &$note){
            $note = $this->groupArrayByValue($note, 'supplier_id');
        }
        $newData = [];
        foreach($notesGroupByMonth as $monIndex=>$dataPerMonth) {
            foreach($dataPerMonth as $supIndex=>$supplierOnMonth){
                $amount = 0;
                $cuts = 0;
                $installment = 0;
                foreach($supplierOnMonth as $noteOnMonth){
                    $amount += $this->sumByKey($noteOnMonth->transactions , 'total_amount');
                    $cuts += $this->sumByKey($noteOnMonth->transactions , 'total_cuts');
                    $installment += $this->sumByKey($noteOnMonth->installments , 'amount');

                }
                $newData[$supIndex][$monIndex] = ['amount' =>$amount - $cuts -$installment, 'supplier_name'=>Supplier::find($supIndex)->name];

            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A3', 'EKSPEDISI SUPPLIER');
        $counter = 2;
        $i = 4;
        $rangeMonth = $this->getAllMonthsAndYears($dateStart.'-01', $dateEndLastDay);

        foreach($rangeMonth as $index=>$month){
            $sheet->setCellValue($this->intToAlphabet($counter+1).$i, $month['month'].$month['year']);
            if(count($rangeMonth) == $index+1){
                $sheet->setCellValue($this->intToAlphabet($counter+2).$i, "total");
            }
            $counter++;
        }
        $sheet->setCellValue('A'.$i, 'No');
        $sheet->setCellValue('B'.$i, 'supplier name');

        $i++;
        $suppliers = Supplier::all();
        foreach($suppliers as $supIndex => $supplier){
            $counter = 3;
            $sheet->setCellValue('B'.$i, $supplier->name);
            //return($rangeMonth);
            foreach ($rangeMonth as $index=>$month){
                $sheet->setCellValue($this->intToAlphabet($counter).$i, $newData[$supplier->id][$month['year'].'-'.$month['monthNum']]['amount'] ?? '0');

                
                $this->formatRupiahCell($sheet, $this->intToAlphabet($counter).$i);
                if(count($rangeMonth) == $index+1){
                    $sheet->setCellValue($this->intToAlphabet($counter+1).$i, '=SUM('.$this->intToAlphabet(3).$i.':'.$this->intToAlphabet($counter).$i.')');
                    $this->formatRupiahCell($sheet, $this->intToAlphabet($counter+1).$i);
                }
                
                $counter++;
            }
            if(count($suppliers) == $supIndex+1){
                $sheet->setCellValue('B'.$i+1, 'total');
            }
            $i++;
        }
        $counter = 3;
        foreach ($rangeMonth as $month){
            $sheet->setCellValue($this->intToAlphabet($counter).$i, '=SUM('.$this->intToAlphabet($counter).'5'.':'.$this->intToAlphabet($counter).($i-1).')');
            $this->formatRupiahCell($sheet, $this->intToAlphabet($counter).$i);
            
            $counter++;
        }
        $sheet->setCellValue($this->intToAlphabet($counter).$i, '=SUM('.$this->intToAlphabet($counter).'5'.':'.$this->intToAlphabet($counter).($i-1).')');
        $this->formatRupiahCell($sheet, $this->intToAlphabet($counter).$i);
        $this->addBordersToSpreadsheet($sheet, "A3:". $this->intToAlphabet($counter).$i);

        $writer = new Xlsx($spreadsheet);
        $filename = 'aaa2.xlsx';
        
        $writer->save($filename);
        return Response::json([
            'success' => true,
            'data' => $newData,
            'supplier' => $suppliers,
            'message' => 'building created'
        ], 200);
    }

    public function restore(Request $request){
        
        $request->validate([
            'excelFile' => 'required|mimes:xlsx,xls',
        ]);
        try{
            $uploadedFile = $request->file('excelFile');

            $reader = new Xlsxreader();
            $spreadsheet = $reader->load($uploadedFile->getRealPath());
            
            $sheet = $spreadsheet->getSheet(0);
            $datadata = [];
            //DB::beginTransaction();
            
            foreach ($sheet->getRowIterator(4) as $row) {
                $cellIterator = $row->getCellIterator();
                
                $data = [];
                
                foreach ($cellIterator as $cell) {
                    $data[] = $cell->getValue();
                }

                $datadata[] = $data;
                
                // $location = Location::where('location_name', $data[3])->first();

                // Attendance::create([
                //     'user_id' => $id,
                //     'date' => $data[0],
                //     'check_in_time' => $data[1],
                //     'check_out_time' => $data[2],
                //     'location_id' => $location->id, // Access the ID directly
                //     'is_late' => str_replace(" ", "", $data[4]) == "terlambat" ? 1 : 0,
                // ]);
                
            }
            $i = 0;
            foreach($datadata as $d){
                if($d[2] === 'Rincian'){
                    echo "Found 'Rincian'"; // Print a message when 'Rincian' is found
                    break;
                }

                if($d[2]!==null && $d[3] !== 'titip' && $d[3] !== 'pelunasan'  && $d[3] !== 'titip Casbon' ){
                    $i++;                     
                    $supplier = Supplier::where('name', $d[2])->first();
                    if(!$supplier){
                        $supplier = Supplier::create([
                            'name'=>$d[2],
                            'address'=> 'xxx',
                            'contact_person'=> 'xxx'
                        ]);

                    }
                    $excelBaseDate = Carbon::createFromDate(1900, 1, 1);

                    // Add the number of days to the base date
                    $dateToInsert = $excelBaseDate->addDays($d[0] - 2); // Subtracting 2 to account for Excel's date starting from 1900-01-01

                    // Format the date as needed for your database
                    $formattedDate = $dateToInsert->format('Y-m-d');
                    $note =  Note::create([
                        'supplier_id' => $supplier->id,
                        'project_id' => 1,
                        'is_pay_later' => 1,  
                        'created_at' => $dateToInsert
                    ]);


                    $firstGoods = Good::where(['name'=>$d[3], 'id_supplier'=> $supplier->id])->first();
                    if(!$firstGoods){
                        $firstGoods = Good::create([
                            'name' => $d[3],
                            'description' => 'xxx',
                            'price' => $d[5],
                            'id_supplier' => $supplier->id,
            
                            // Add other fields as needed
                        ]);
                    }
                    $data = Transaction::create([
                        'id_notes' => $note->id,
                        'id_good' => $firstGoods->id,
                        'quantity' => $d[4],
                        'total_amount' => $d[4] * $d[5],
                        'total_cuts' => null,
                        'id_project'=> 1,
                        'created_at'=> $note->created_at
    
                    ]);
                    //buat nota transaksi;
                    
                }else if($d[2]===null && $d[3] !== 'titip' && $d[3] !== 'pelunasan'  && $d[3] !== 'titip Casbon'  && $d[3]!==null ){
                    
                    $firstGoods = Good::where(['name'=>$d[3], 'id_supplier'=> $supplier->id])->first();
                    if(!$firstGoods){
                        $firstGoods = Good::create([
                            'name' => $d[3],
                            'description' => 'xxx',
                            'price' => $d[5],
                            'id_supplier' => $supplier->id,
            
                            // Add other fields as needed
                        ]);
                    }

                    $data = Transaction::create([
                        'id_notes' => $note->id,
                        'id_good' => $firstGoods->id,
                        'quantity' => $d[4],
                        'total_amount' => $d[4] * $d[5],
                        'total_cuts' => null,
                        'id_project'=> 1,
                        'created_at'=> $note->created_at
                    ]);
                    
                }else if( $d[3] === 'titip' || $d[3] === 'pelunasan'  || $d[3] === 'titip Casbon'){
                    echo $d[2];
                    $excelBaseDate = Carbon::createFromDate(1900, 1, 1);
                    $dateToInsert = $excelBaseDate->addDays(is_numeric($d[2]) ? $d[2] : 0 - 2); // Subtracting 2 to account for Excel's date starting from 1900-01-01
                    $formattedDate = $dateToInsert->format('Y-m-d');
                    $installment = InstallmentTransaction::create([
                        'note_id' => $note->id,
                        'installment_number' => 1,
                        'amount' => $d[5],
                        'created_at' => $formattedDate
                    ]);
                }
            }
//            return $i;
            //DB::commit();
            return Response::json([
                'success' => true,
                'data' => ['total_notes'=> $i, 'data'=>$datadata],
                'message' => count($datadata)
            ], 200);
        }catch(Exception $err){
            DB::rollback();
            return Response::json([
                'success' => false,
                'message' => $err->message()
            ], 400);
    }
}
}

