<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Note;
use Carbon\Carbon;
use DB;
class ReportController extends Controller
{
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
    public function index(Request $request){
        //get incomplete transaction data
        $notes = Note::join('suppliers', 'suppliers.id', 'notes.supplier_id' )->where('is_pay_later', 1)->select('notes.*', 'suppliers.name as supplier_name')->with('transactions', 'installments')->get();
        //get complete transaction data
        $summary = Note::where('is_pay_later', 1)
        ->select('notes.*', 'suppliers.name as supplier_name', DB::raw('SUM(total_amount) as total_amount'))->with('installments')
        ->join('suppliers', 'suppliers.id', '=', 'notes.supplier_id')
        ->join('transactions', 'transactions.id_notes', '=', 'notes.id')
        ->leftJoin('installments', 'installments.note_id', '=', 'notes.id')
        
        ->groupBy('notes.supplier_id',)
        ->get();
        $suppliers = Note::join('suppliers', 'suppliers.id', 'notes.supplier_id' )->select('suppliers.name')->distinct()->pluck('name');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A3', 'LAPORAN MATERIAL BELUM TERBAYAR');
        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A4', "PROJECT ".$request->selectedProject);
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
            foreach ($note->transactions as $transac){
                $subTotal += $transac->total_amount;
                $sheet->setCellValue('D'.$i, $transac->good_name);
                $sheet->setCellValue('E'.$i, $transac->quantity);
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
        foreach($summary as $sum){
            $totalInstallment= 0;
            
                foreach($sum->installments as $summaryInstallment){
                    $totalInstallment += $summaryInstallment->amount;
                }
                $sheet->setCellValue('C'.$i, $sum->supplier_name );
                $sheet->setCellValue('D'.$i, ((int) $sum->total_amount - (int)$totalInstallment) );
                $total+= $sum->total_amount ;
                $i++;
            }
        $sheet->setCellValue('C'.$i, "total" );
        $sheet->setCellValue('D'.$i, $total);
    

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
            
            ->havingRaw('YEAR(latest_paid) = ?', [$request->selectedYear])
            ->havingRaw('MONTH(latest_paid) = ?', [$monthStart])
            ->select('notes.*', 'suppliers.name as supplier_name', DB::raw('MAX(installments.created_at) as latest_paid'))->with('transactions', 'installments')->groupBy('notes.id');
            $notesDone = $notes->get();
           

            
            
            
            $noteId = [];
            foreach($notesDone as $note){
            
                $sheet2->setCellValue('A'.$i, 'LAPORAN MATERIAL BELUM TERBAYAR');
                $sheet2->mergeCells('A'.$i.':H'.$i);
                $sheet2->setCellValue('A'.($i+1), "PROJECT ".$request->selectedProject);
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
                array_push($noteId, $note->id);
            $i+=2;
            $sheet2->setCellValue('A'.$i, Carbon::parse($note->created_at)->format('m-d'));
            $sheet2->setCellValue('C'.$i, $note->supplier_name);
                $subTotal = 0;
                foreach ($note->transactions as $transac){
                    $subTotal += $transac->total_amount;
                    $sheet2->setCellValue('D'.$i, $transac->good_name);
                    $sheet2->setCellValue('E'.$i, $transac->quantity);
                    $sheet2->setCellValue('G'.$i, $transac->total_amount);
                    ++$i;
                }
                $sheet2->setCellValue('H'.$i-1, $subTotal);

                $newSubTotal = 0;
                $counter = 1;
                foreach ($note->installments as $installment){
                                        
                    $newSubTotal += $installment->amount;
                    $sheet2->setCellValue('C'.$i, Carbon::parse($installment->created_at)->format('m-d'));
                    $sheet2->setCellValue('D'.$i, $counter == count($note->installments) ? "pelunasan" : "titip");
                    $sheet2->setCellValue('E'.$i, $installment->amount);
                    ++$i;
                    $counter++;
                }
              
                $sheet2->setCellValue('H'.$i-1, $newSubTotal);
                $i++;
                
                $sheet2->setCellValue('C'.$i, 'Rincian');
                $i++;
                $total = 0;
                $months = ['22 januari', '22 februari'];
                $summaryDone = Note::where('is_pay_later', 0)->with('installments')
                    ->select('notes.*', 'suppliers.name as supplier_name',  DB::raw('MAX(installments.created_at) as latest_paid'))
                    ->join('suppliers', 'suppliers.id', 'notes.supplier_id')
                    ->join('installments', 'installments.note_id', 'notes.id')
                    
                    ->whereIn('notes.id',$noteId)
                    ->get();

                $totalAmount = Note::whereIn('notes.id',$noteId)->select(DB::raw('SUM(total_amount) as total_amount'))->join('transactions', 'transactions.id_notes', 'notes.id')->first();
                //return $totalAmount;                
                foreach($summaryDone as $sum){
                $totalInstallment= 0;

                    foreach($sum->installments as $summaryInstallment){
                        $totalInstallment += $summaryInstallment->amount;
                    }
                    $sheet2->setCellValue('C'.$i, $sum->supplier_name );
                    $sheet2->setCellValue('D'.$i, ((int)$totalInstallment) );
                    array_push($detailTotal, ['storeName'=> $sum->supplier_name,'amount'=> '=Terbayar!D'.$i, 'date'=>'22 januari']);
                    $total+= ((int)$totalInstallment);

                    $i++;
                }
                $sheet2->setCellValue('C'.$i, "total" );
                $sheet2->setCellValue('D'.$i, $total);
                $i+=2;
                
            }
            
        }

        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Rincian');
        $i = 2;
        $sheet3->setCellValue('A'.$i, "Belum Terbayar");
        $i++;
        foreach($suppliers as $sup){
            $sheet3->setCellValue('A'.$i, $sup);
            $i++;
        }
        $sheet3->setCellValue('A'.$i, "Total");
       
        $i = 3;
        foreach($detailTotal as $detail){
            $key = array_search($detail['date'], $months);
            $index = 0;
            foreach ($suppliers as $sum){
                if($sum == $detail['storeName']){$found = $index;}
                $index++;
            }
            $columnStart = 2;
            $column = $this->generateColumnCodes(2+$key);
            $sheet3->setCellValue("B".$i+$found, $detail['amount']);
        }
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
        

}
