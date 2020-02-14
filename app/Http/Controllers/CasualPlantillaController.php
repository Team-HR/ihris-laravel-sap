<?php

namespace App\Http\Controllers;
use App\Appointment;
use App\Employee;
use App\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Response;
/**
 * 
 */

class CasualPlantillaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    private $lastRow = 0;
    private $currentMergedCell = "";
    private $spreadsheet;
    private $casuals;

    public function index()
    {
        $matchThese = [
            'from_date'=>'2020-01-01',
            'to_date'=>'2020-06-30',
            'nature_of_appointment' => 'REAPPOINTMENT',
            'department_id'=>1
        ];
        $casuals = Employee::join('appointments', 'appointments.employee_id', '=', 'employees.id')
            ->where($matchThese)
            ->select('*')
            ->get();

        // $appointments = DB::table('appointments')->distinct()->get(['from_date','to_date','nature_of_appointment']);
        $appointments = Appointment::distinct()->get(['from_date','to_date','nature_of_appointment']);
        $departments = Department::orderBy('short_name', 'asc')->get();


        return view('casual.plantilla.index', [
            'appointments' => $appointments->toArray(),
            'departments' => $departments,
            'casuals' => $casuals
        ]);
    }

    // public function generateReport(Request $request){
    //     $from_date = $request->from_date;
    //     $to_date = $request->to_date;
    //     $nature_of_appointment = $request->nature_of_appointment;
    //     $filter = $request->filter;




    //     // return Response::json($from_date.$to_date.$nature_of_appointment.$filter);
    // }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {   
        //
    }
    public function generateReport(Request $request){

        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $nature_of_appointment = $request->nature_of_appointment;
        $department_id = $request->filter;


        $matchThese = [
            'from_date'=>$from_date,
            'to_date'=>$to_date,
            'nature_of_appointment' => $nature_of_appointment,
        ];


        if($department_id == 'all'){
            $department = "LGU BAYAWAN CITY";
        } else {
            $where = array('id' => $department_id);
            $dept  = Department::where($where)->first();
            $department = $dept['name'];
            $matchThese['department_id'] = $department_id;
        }

        $casuals = Employee::join('appointments', 'appointments.employee_id', '=', 'employees.id')
            ->where($matchThese)
            ->select('*')
            ->orderBy('last_name','asc')
            ->get();

         $this->casuals = $casuals;
        // Create new Spreadsheet object
        
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('FranzDev')
            ->setLastModifiedBy('FranzDev')
            ->setTitle('Report Plantilla')
            ->setSubject('for Casual Employees')
            ->setDescription('Generated plantilla report for casual employees.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Report excel file');

    $spreadsheet->getActiveSheet()->getPageSetup()
    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    $spreadsheet->getActiveSheet()->getPageSetup()
    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
// style
$italic = [
    'italic'
];
    
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(12);
    

        // Add some data


// data
$count = count($casuals);
$pages = intval($count/15);
if ($count>($pages*15)) {
    $pages = $pages+1;
}
$totalNo = $pages*15;
$numbering = 1;
// row number to start
$startRow = 2;
$end = false;
for ($page=1; $page <= $pages ; $page++) {
// Head
$this->headSection($department,$spreadsheet,$startRow);

    for ($i=1; $i <= 15; $i++) {
        // (isset($casuals[$numbering-2])?$end = true:$end = false);
        $numbering++;
        // echo $numbering++.'. '.(isset($name[$numbering-2])?$name[$numbering-2]:($end?nothingFollows($end):'')).'<br>';  
        $row = $this->lastRow+1;
        $num = $numbering-1;
    

        if (!$end && !isset($casuals[$num-1]['last_name'])) {
            $end = true;
            $this->nothingFollows($spreadsheet,$row);
        } else {

        $col = 'A';
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row,$num);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getLeft()->setBorderStyle('thick');

        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = "B";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':C'.$row);
        $spreadsheet->getActiveSheet()->getCell('B'.$row)->setValue((isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['last_name']:''));
        $spreadsheet->getActiveSheet()->getStyle($col.$row.':C'.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = "D";
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['first_name']:''));
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = "E";
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['ext_name']:''));
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = "F";
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['middle_name']:''));
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = 'G';
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['position_title']:''));
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = 'H';
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['sg']:''));
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = 'I';
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?'Php. '.number_format((float)$casuals[$num-1]['daily_wage'], 2, '.', ''):''));
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = 'J';
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?date_format(date_create($casuals[$num-1]['from_date']), 'm/d/Y'):''));
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = 'K';
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?date_format(date_create($casuals[$num-1]['to_date']), 'm/d/Y'):''));
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = 'L';
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['nature_of_appointment']:''));
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = 'M';
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, $casual['nature_of_appointment']);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $col = 'N';
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, $casual['nature_of_appointment']);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getRight()->setBorderStyle('thick');

        $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(18);
        }

        $this->lastRow = $this->lastRow+1;
    }

    if ($totalNo == $count && $page == $pages) {
        // echo nothingFollows($end=false)."<br>";
            $row = $this->lastRow+1;
            $this->nothingFollows($spreadsheet,$row);
            $this->lastRow = $row;
    }

    $this->footSection($spreadsheet,$page,$pages);
    // number of rows before start row
    $startRow = $this->lastRow+4;
}
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(9);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(24);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(11.5);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(17.7);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(15);

        $spreadsheet->getActiveSheet()->setTitle((isset($dept['short_name'])?$dept['short_name']:'ALL_EMPLOYEES'));

        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.25);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.25);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.41);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.25);


        $spreadsheet->setActiveSheetIndex(0);
        // $spreadsheet->getActiveSheet()->getPageSetup()->setPrintArea('A1:N41');

        $b =-1;
        $printArea = '';
        for ($i=0; $i < $pages ; $i++) { 
            $a = $b+2;
            $b = $a+39;
            $printArea .= 'A'.$a.':N'.$b;
            if ($i != ($pages-1)) {
                $printArea .= ',';
            }
        }

        $spreadsheet->getActiveSheet()->getPageSetup()->setPrintArea($printArea);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);

        
        $this->createRaiWorksheet($spreadsheet);


        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // file title
        header('Content-Disposition: attachment;filename="CASUAL PLANTILLA - '.(isset($dept['short_name'])?$dept['short_name']:'ALL EMPLOYEES').'_'.$from_date.'-'.$to_date.'_'.$nature_of_appointment.'.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');

        exit;
        
    }

    private function headSection($department,$spreadsheet,$startRow){

$spreadsheet->getActiveSheet()->mergeCells('M'.$startRow.':N'.($startRow+1));
        $spreadsheet->getActiveSheet()->mergeCells('M'.($startRow+2).':N'.($startRow+2));
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A'.$startRow, 'CS Form No. 34-D')
            ->setCellValue('A'.($startRow+1), 'Revised 2018')
            ->setCellValue('M'.($startRow+2), '(Stamp of Date of Receipt)')
            ;
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(6);

$spreadsheet->getActiveSheet()->getCell('M'.$startRow)->setValue("For Accredited/Deregulated\nLocal Government Units");
$spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(30);

$spreadsheet->getActiveSheet()->getStyle('M'.$startRow)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('M'.$startRow)->getAlignment()->setHorizontal('center');
$spreadsheet->getActiveSheet()->getStyle('M'.$startRow)->getFont()->setSize(12);

$styleArray = [
    'borders' => [
        'outline' => [
            'borderStyle' => 'thin',
            // 'color' => ['argb' => 'black'],
        ],
    ],
];

$spreadsheet->getActiveSheet()->getStyle('M'.$startRow.':N'.($startRow+1))->applyFromArray($styleArray);

$spreadsheet->getActiveSheet()->getStyle('M'.($startRow+2))->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('M'.($startRow+2))->getAlignment()->setHorizontal('center');

         // set style
$spreadsheet->getActiveSheet()->getStyle('A'.($startRow+1))->getFont()->setItalic(true)->setSize(9);

// Header Title
$row = $startRow+5;
$spreadsheet->getActiveSheet()->mergeCells('A'.$row.':N'.$row);
$spreadsheet->getActiveSheet()->getCell('A'.$row)->setValue("Republic of the Philippines");
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
$row = $startRow+6;
$spreadsheet->getActiveSheet()->mergeCells('A'.$row.':N'.$row);
$spreadsheet->getActiveSheet()->getCell('A'.$row)->setValue("Province of Negros Oriental");
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
// $row = 9;
$row = $startRow+7;
$spreadsheet->getActiveSheet()->mergeCells('A'.$row.':N'.$row);
$spreadsheet->getActiveSheet()->getCell('A'.$row)->setValue("CITY OF BAYAWAN");
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
// $row = 11;
$row = $startRow+9;
$spreadsheet->getActiveSheet()->mergeCells('A'.$row.':N'.$row);
$spreadsheet->getActiveSheet()->getCell('A'.$row)->setValue("PLANTILLA OF CASUAL APPOINTMENTS");
$spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(22);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getFont()->setBold(true);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getFont()->setSize(14);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
// $row = 13;
$row = $startRow+11;
$spreadsheet->getActiveSheet()->getCell('A'.$row)->setValue("Department/Office:");
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getFont()->setSize(12);

// $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12.5);
$spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12.5);

$spreadsheet->getActiveSheet()->mergeCells('C'.$row.':G'.$row);
$spreadsheet->getActiveSheet()->getStyle('C'.$row.':G'.$row)->getBorders()->getBottom()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getCell('C'.$row)->setValue($department);
$spreadsheet->getActiveSheet()->getStyle('C'.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('C'.$row)->getAlignment()->setHorizontal('center');
// $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);

$spreadsheet->getActiveSheet()->getCell('L'.$row)->setValue("Source of Funds:");
$spreadsheet->getActiveSheet()->getStyle('L'.$row)->getAlignment()->setHorizontal('right')->setVertical('center');
$spreadsheet->getActiveSheet()->mergeCells('M'.$row.':N'.$row);
$spreadsheet->getActiveSheet()->getCell('M'.$row)->setValue("PS");
$spreadsheet->getActiveSheet()->getStyle('M'.$row)->getAlignment()->setHorizontal('center')->setVertical('bottom');
$spreadsheet->getActiveSheet()->getStyle('M'.$row.':N'.$row)->getBorders()->getBottom()->setBorderStyle('thin');
        
// $row = 14;
$row = $startRow+12;

// $instructions = "INSTRUCTIONS:\n(1)   Only a maximum of fifteen (15) appointees must be listed on each page of the Plantilla of Casual Appointments.\n(2)   Indicate ‘NOTHING FOLLOWS’ on the row following the name of the last appointee on the last page of the Plantilla.\n(3)   Provide proper pagination (Page n of n page/s).";

$richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
// $richText->createText('This invoice is ');
$payable = $richText->createTextRun('INSTRUCTIONS:');
$payable->getFont()->setBold(true);
$richText->createText('
(1)   Only a maximum of fifteen (15) appointees must be listed on each page of the Plantilla of Casual Appointments.
(2)   Indicate ‘NOTHING FOLLOWS’ on the row following the name of the last appointee on the last page of the Plantilla.
(3)   Provide proper pagination (Page n of n page/s).');
// $spreadsheet->getActiveSheet()->getCell('A18')->setValue($richText);

$spreadsheet->getActiveSheet()->mergeCells('A'.$row.':N'.$row);
$spreadsheet->getActiveSheet()->getCell('A'.$row)->setValue($richText);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getFont()->setSize(14);
$spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(78);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setVertical('center');

// $row = 15;
$row = $startRow+13;
$spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(30);
$spreadsheet->getActiveSheet()->mergeCells('A'.$row.':F'.$row);
$spreadsheet->getActiveSheet()->getCell('A'.$row)->setValue("NAME OF APPOINTEE/S");
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle('A'.$row.':F'.$row)->getBorders()->getOutline()->setBorderStyle('thin');

// $row = 16;
$row = $startRow+14;
$spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(40);
$spreadsheet->getActiveSheet()->getStyle('A'.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->mergeCells('B'.$row.':C'.$row);
$spreadsheet->getActiveSheet()->getCell('B'.$row)->setValue("Last Name");
$spreadsheet->getActiveSheet()->getStyle('B'.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle('B'.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('B'.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle('B'.$row.':C'.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getCell('D'.$row)->setValue("First Name");
$spreadsheet->getActiveSheet()->getStyle('D'.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle('D'.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle('D'.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle('D'.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(17);
$col = "E";
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("Name\nExtension\n(Jr/III)");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(10);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(66);

$col = "F";
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("Middle Name");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth(17);
// $row = 15;
$row = $startRow+13;
$col = "G";
$spreadsheet->getActiveSheet()->mergeCells($col.$row.':'.$col.($row+1));
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("POSITION TITLE\n(Do not abbreviate)");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row.':'.$col.($row+1))->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth(17);
$col = "H";
$spreadsheet->getActiveSheet()->mergeCells($col.$row.':'.$col.($row+1));
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("EQUIVALENT\nSALARY/\nJOB/\nPAY GRADE");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(10);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row.':'.$col.($row+1))->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth(11);
$col = "I";
$spreadsheet->getActiveSheet()->mergeCells($col.$row.':'.$col.($row+1));
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("DAILY\nWAGE");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row.':'.$col.($row+1))->getBorders()->getOutline()->setBorderStyle('thin');
$col = "J";
$spreadsheet->getActiveSheet()->mergeCells($col.$row.':K'.$row);
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("PERIOD OF EMPLOYMENT");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('bottom');
$spreadsheet->getActiveSheet()->getStyle($col.$row.':K'.$row)->getBorders()->getOutline()->setBorderStyle('thin');

// $row = 16;
$row = $startRow+14;
$col = "J";
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("From\n(mm/dd/yyyy)");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth(15);
$col = "K";
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("To\n(mm/dd/yyyy)");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth(15);

// $row = 15;
$row = $startRow+13;
$col = "L";
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("NATURE OF APPOINTMENT");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('bottom');
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth(14);
// $row = 16;
$row = $startRow+14;
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("(Original/\nReappointment/\nReemployment)");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth(14);
// $row = 15;
$row = $startRow+13;
$col = "M";
$spreadsheet->getActiveSheet()->mergeCells($col.$row.':N'.$row);
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("ACKNOWLEDGEMENT OF APPOINTEE/S");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row.':N'.$row)->getBorders()->getOutline()->setBorderStyle('thin');
// $row = 16;
$row = $startRow+14;
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("Signature");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth(22);
$col = "N";
$spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("Date Received");
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
$spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
$spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth(22);

$spreadsheet->getActiveSheet()->getStyle('A'.($startRow+13).':N'.($startRow+14))->getBorders()->getOutline()->setBorderStyle('thick');

$this->lastRow = $startRow+14;

    }
    private function footSection($spreadsheet,$page,$pages){
        $col = "A";
        $row = $this->lastRow+1;
        $subquote = 'The abovenamed personnel are hereby hired/appointed as casuals at the rate of compensation stated opposite their names for the period indicated. It is understood that such employment will cease  automatically at the end of the period stated unless renewed. Any or all of them may be laid-off any time before the expiration of the employment period when their services are no longer needed or funds are no longer available or the project has already been completed/finished or their performance are below par.';
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':N'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue($subquote);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('left')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row.':N'.$row)->getBorders()->getOutline()->setBorderStyle('thick');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(50);

        $row = $row+2;
        $col = "A";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':D'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("CERTIFICATION:");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('top');
        $col = "F";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':G'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("CERTIFICATION:");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('top');

        $col = "I";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':K'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("CERTIFICATION AND SIGNATURE OF\nAPPOINTING OFFICER / AUTHORITY:");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setBold(true);

        $col = "M";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':N'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("ACCREDITED PURSUANT TO:");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('top');

        $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(30);

        $row = $row+1;
        $col = "A";
        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $richText->createText('This is to certify that all requirement and supporting papers pursuant to ');
        $payable = $richText->createTextRun('CSC MC No. 14 s. 2018, ');
        $payable->getFont()->setBold(true);
        $richText->createText('have been complied with, reviewed and found in order.');
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':D'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue($richText);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('left')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);

        $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(81);

        $col = "F";
        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $richText->createText('This is to certify that funds are available pursuant to Appropriation Ordinance No.');
        $payable = $richText->createTextRun(' 62 ');
        $payable->getFont()->setUnderline(true);
        $richText->createText(' series of ');
        $payable = $richText->createTextRun(' 2019 ');
        $payable->getFont()->setUnderline(true);
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':G'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue($richText);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('left')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        
        
        $col = "I";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':K'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("This is to certify that all pertinent provisions of Sec. 325 of RA 7160 (Local Government Code of 1991) have been complied with in the issuance of appointments of the above-mentioned persons.");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('left')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        

        $col = "M";
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("CSC Resolution No:\nDate:");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('right')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);

        $col = "N";
        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $payable = $richText->createTextRun('1201478');
        $payable->getFont()->setUnderline(true);
        $richText->createText("\n");
        $payable = $richText->createTextRun('September 26, 2012');
        $payable->getFont()->setUnderline(true);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue($richText);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('left')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);



        $row = $row+1;
        $col = "A";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':D'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("VERONICA GRACE P. MIRAFLOR");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('bottom');
        $spreadsheet->getActiveSheet()->getStyle($col.$row.':D'.$row)->getBorders()->getBottom()->setBorderStyle('thin');    

        $col = "F";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':G'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("CORAZON P. LIRAZAN ");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('bottom');
        $spreadsheet->getActiveSheet()->getStyle($col.$row.':G'.$row)->getBorders()->getBottom()->setBorderStyle('thin');    

        $col = "I";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':K'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("PRYDE HENRY A. TEVES");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('bottom');
        $spreadsheet->getActiveSheet()->getStyle($col.$row.':K'.$row)->getBorders()->getBottom()->setBorderStyle('thin');

        $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(30);

        $row = $row+2;
        $col = "B";
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("Date:");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('right')->setVertical('bottom');

        $col = "C";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':D'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("January 1, 2020");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('bottom');
        $spreadsheet->getActiveSheet()->getStyle($col.$row.':D'.$row)->getBorders()->getBottom()->setBorderStyle('thin');    

        $col = "F";
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("Date:");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('right')->setVertical('bottom');

        $col = "G";
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("January 1, 2020");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('bottom');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getBottom()->setBorderStyle('thin');

        $col = "I";
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("Date:");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('right')->setVertical('bottom');

        $col = "J";
        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':K'.$row);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue("January 1, 2020");
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('bottom');
        $spreadsheet->getActiveSheet()->getStyle($col.$row.':K'.$row)->getBorders()->getBottom()->setBorderStyle('thin');


        $row = $row+1;
        $col = "N";
        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $payable = $richText->createTextRun('Page ');
        $payable->getFont()->setBold(true);
        $payable = $richText->createTextRun($page);
        $payable->getFont()->setBold(true);
        $payable = $richText->createTextRun(' of ');
        $payable->getFont()->setBold(true);
        $payable = $richText->createTextRun($pages);
        $payable->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue($richText);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('left')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('right')->setVertical('bottom');


        $this->lastRow = $row;
    }


    private function nothingFollows($spreadsheet,$row,$colb='N'){
        $col = "A";

        $spreadsheet->getActiveSheet()->mergeCells($col.$row.':'.$colb.$row);

        $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue('***NOTHING FOLLOWS***');
        $spreadsheet->getActiveSheet()->getStyle($col.$row.':'.$colb.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        $spreadsheet->getActiveSheet()->getStyle($col.$row.':'.$colb.$row)->getBorders()->getLeft()->setBorderStyle('thick');
        $spreadsheet->getActiveSheet()->getStyle($col.$row.':'.$colb.$row)->getBorders()->getRight()->setBorderStyle('thick');
        
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(14);
        $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(15);
    }

    private function createRaiWorksheet($spreadsheet)
    {
        $worksheet1 = $spreadsheet->createSheet();
        $worksheet1->setTitle('RAI');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        // create head of RAI

        // start of head
$this->lastRow = 1;


$casuals =$this->casuals;
$count = count($casuals);
$pages = intval($count/15);
if ($count>($pages*15)) {
    $pages = $pages+1;
}
$totalNo = $pages*15;
$numbering = 1;
// row number to start
$end = false;
$cols = array(
    'a'=>0,
    'b'=>'from_date',
    'c'=>'last_name',
    'd'=>'first_name',
    'e'=>'ext_name',
    'f'=>'middle_name',
    'g'=>'position_title',
    'h'=>'sg',
    'i'=>'daily_wage',
    'j'=>0,
    'k'=>'employment_status',
    'l'=>'',
    'm'=>'nature_of_appointment',
    'n'=>'',
    'o'=>'',
    'p'=>'',
    'q'=>'',
    'r'=>'',
    's'=>'',
);


for ($page=1; $page <= $pages ; $page++) {

    

// Head
        $this->raiHead($spreadsheet);  

    for ($i=1; $i <= 15; $i++) {
        // (isset($casuals[$numbering-2])?$end = true:$end = false);
        $numbering++;
        // echo $numbering++.'. '.(isset($name[$numbering-2])?$name[$numbering-2]:($end?nothingFollows($end):'')).'<br>';  
        $row = $this->nextRow();
        $num = $numbering-1;

        if (!$end && !isset($casuals[$num-1]['last_name'])) {
            $end = true;
            $this->nothingFollows($spreadsheet,$row,'S');
        } else {

            foreach ($cols as $col => $index) {
                if ($col == 'a') {
                    $spreadsheet->getActiveSheet()->setCellValue($col.$row,$num);   
                } elseif ($col == 'b') {
                     $spreadsheet->getActiveSheet()->setCellValue($col.$row,(isset($casuals[$num-1]['from_date'])?date_format(date_create($casuals[$num-1]['from_date']), 'm/d/Y'):''));
                     $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
                } elseif ($col == 'l') {
                    $spreadsheet->getActiveSheet()->setCellValue($col.$row,(isset($casuals[$num-1]['from_date'])?date_format(date_create($casuals[$num-1]['from_date']), 'm/d/Y').' - '.date_format(date_create($casuals[$num-1]['to_date']), 'm/d/Y'):''));
                    $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
                } elseif (in_array($col, array('c','d','e','f'))) {
                    $spreadsheet->getActiveSheet()->setCellValue($col.$row,(isset($casuals[$num-1][$index])?$casuals[$num-1][$index]:''));
                    $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('left')->setVertical('center');
                } else {
                    $spreadsheet->getActiveSheet()->setCellValue($col.$row,(isset($casuals[$num-1][$index])?$casuals[$num-1][$index]:''));
                    $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
                }
                $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
                // $this->spreadsheet->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
                // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
                // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getLeft()->setBorderStyle('thick');
                // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
                // $spreadsheet->getActiveSheet()->getCell($col.$row)->setValue((isset($casuals[$num-1][$col])?$casuals[$num-1][$col]:''));
                // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
                // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
            }
    
        
        // $col = "D";
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['first_name']:''));
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $col = "E";
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['ext_name']:''));
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $col = "F";
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['middle_name']:''));
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $col = 'G';
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['position_title']:''));
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $col = 'H';
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['sg']:''));
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $col = 'I';
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?'Php. '.number_format((float)$casuals[$num-1]['daily_wage'], 2, '.', ''):''));
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $col = 'J';
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?date_format(date_create($casuals[$num-1]['from_date']), 'm/d/Y'):''));
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $col = 'K';
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?date_format(date_create($casuals[$num-1]['to_date']), 'm/d/Y'):''));
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $col = 'L';
        // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, (isset($casuals[$num-1]['last_name'])?$casuals[$num-1]['nature_of_appointment']:''));
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $col = 'M';
        // // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, $casual['nature_of_appointment']);
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $col = 'N';
        // // $spreadsheet->setActiveSheetIndex(0)->setCellValue($col.$row, $casual['nature_of_appointment']);
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getAlignment()->setHorizontal('center')->setVertical('center');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getOutline()->setBorderStyle('thin');
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getFont()->setSize(13);//data
        // $spreadsheet->getActiveSheet()->getStyle($col.$row)->getBorders()->getRight()->setBorderStyle('thick');

        $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(18);
        }
    }

    if ($totalNo == $count && $page == $pages) {
        // echo nothingFollows($end=false)."<br>";
            $row = $this->lastRow+1;
            $this->nothingFollows($spreadsheet,$row,'S');
            $this->lastRow = $row;
    }
        // start of foot
        $this->raiFoot($spreadsheet);
        $this->lastRow = $this->currentRow()+3;
        $spreadsheet->getActiveSheet()->getStyle('A20:S39')->getBorders()->getOutline()->setBorderStyle('thick');
}
    // end of perpage


        $b =-1;
        $printArea = '';
        for ($i=0; $i < $pages ; $i++) { 
            $a = $b+2;
            $b = $a+54;
            $printArea .= 'A'.$a.':S'.$b;
            if ($i != ($pages-1)) {
                $printArea .= ',';
            }
        }


        $spreadsheet->getActiveSheet()->getPageSetup()
        ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()
        ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.25);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.25);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.41);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.25);

        $spreadsheet->getActiveSheet()->getPageSetup()->setPrintArea($printArea);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);



    } 


    private function raiHead($spreadsheet){
        $col = 'A';
        $colend = 'S';
        $spreadsheet->setActiveSheetIndex(1);
        $spreadsheet->getActiveSheet()
            ->setCellValue($col.$this->nextRow(), 'CS Form No. 2')
            ->setCellValue($col.$this->nextRow(), 'Revised 2017')->getStyle($col.$this->currentRow())->getFont()->setBold(true)->setItalic(true)->setSize(9);
        $spreadsheet->getActiveSheet()
            ->mergeCells($this->currentMergedCell($col,$this->nextRow(2),$colend,$this->currentRow()))->getCell($col.$this->currentRow())->setValue('REPORT ON APPOINTMENTS ISSUED (RAI)')->getStyle($this->currentMergedCell)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($this->currentMergedCell)->getFont()->setBold(true)->setSize(14);

        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $payable = $richText->createTextRun('For the month of ');
        // $richText->createText('For the Month of ');
        $payable->getFont()->setItalic(true);
        $payable = $richText->createTextRun('JANUARY 2020');
        $payable->getFont()->setItalic(true);
        $payable->getFont()->setBold(true);
        $payable->getFont()->setUnderline(true);
        
        // $payable->getFont()->setBold(true);
        // $richText->createText('');

        $spreadsheet->getActiveSheet()
            ->mergeCells($this->currentMergedCell($col,$this->nextRow(),$colend,$this->currentRow()))->getCell($col.$this->currentRow())->setValue($richText)->getStyle($this->currentMergedCell)->getAlignment()->setHorizontal('center')->setVertical('center');
        $col = 'O';
        $spreadsheet->getActiveSheet()
            ->setCellValue($col.$this->nextRow(), 'Date received by CSCFO:')->getStyle($col.$this->currentRow())->getFont()->setBold(true)->setItalic(false)->setSize(10);


        $spreadsheet->getActiveSheet()->getStyle($col.$this->currentRow())->getAlignment()->setHorizontal('right');

        $spreadsheet->getActiveSheet()->mergeCells($this->currentMergedCell('p',$this->currentRow(),'s',$this->currentRow()));
        $spreadsheet->getActiveSheet()->getStyle($this->currentMergedCell)->getBorders()->getBottom()->setBorderStyle('thin');

        $col = 'A';
        $colB = 'B';
        $spreadsheet->getActiveSheet()
            ->mergeCells($this->currentMergedCell($col,$this->nextRow(3),$colB,$this->currentRow()))->getCell($col.$this->currentRow())->setValue('AGENCY:')->getStyle($this->currentMergedCell)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()
            ->getStyle($col.$this->currentRow())->getFont()->setBold(true)->setItalic(false)->setSize(12);
        $spreadsheet->getActiveSheet()->mergeCells($this->currentMergedCell('c',$this->currentRow(),'f',$this->currentRow()));
        $spreadsheet->getActiveSheet()->getStyle($this->currentMergedCell)->getBorders()->getBottom()->setBorderStyle('thin');
        // CSC Resolution No:   1201478 
        // label start
        $col = 'I';
        $row = $this->currentRow();
        $currentCell = $col.$row;
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,'CSC Resolution No:');
        // fontstyle
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(true);
        // alignment
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('right')->setVertical('center');
        // label end
        // underline start


        $spreadsheet->getActiveSheet()->mergeCells($this->currentMergedCell('j',$this->currentRow(),'k',$this->currentRow()));
        $currentCell = 'J'.$row;
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,'1201478');
        // fontstyle
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(true);
        // alignment
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('center')->setVertical('center');
        $spreadsheet->getActiveSheet()->getStyle($this->currentMergedCell)->getBorders()->getBottom()->setBorderStyle('thin');


        // underline end

        // CSCFO In-charge: M
        // label start
        $col = 'M';
        $row = $this->currentRow();
        $currentCell = $col.$row;
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,'CSC Resolution No:');
        // fontstyle
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(true);
        // alignment
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('right')->setVertical('center');
        // label end
        // underline start
        $spreadsheet->getActiveSheet()->mergeCells($this->currentMergedCell('n',$this->currentRow(),'s',$this->currentRow()));
        $spreadsheet->getActiveSheet()->getStyle($this->currentMergedCell)->getBorders()->getBottom()->setBorderStyle('thin');
        // underline end


        // start merged cells
        $col = 'A';
        $colb = 'B';
        $row = $this->nextRow(2);
        $currentCell = $col.$row;
        $currentMCell = $col.$row.':'.$colb.$row;

        $spreadsheet->getActiveSheet()->mergeCells($currentMCell);
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,'INSTRUCTIONS:');
        // fontstyle
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(true);
        // alignment
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('right')->setVertical('center');
        // end merged cells
        // start write cell
        $col = 'C';
        // $colb = 'B';
        $row = $this->currentRow();
        $currentCell = $col.$row;
        // $currentMCell = $col.$row.':'.$colb.$row;

        // $spreadsheet->getActiveSheet()->mergeCells($currentCell);
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,'(1) Fill-out the data needed in the form completely and accurately.');
        // fontstyle
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(false);
        // alignment
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('left')->setVertical('center');
        // end write cell
                // start write cell
        // $col = 'C';
        // $colb = 'B';
        $row = $this->nextRow();
        $currentCell = $col.$row;
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,'(2) Do not abbreviate entries in the form.');
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(false);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('left')->setVertical('center');
        $row = $this->nextRow();
        $currentCell = $col.$row;
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,'(3) Accomplish the Checklist of Common Requirements and sign the certification.');
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(false);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('left')->setVertical('center');
        $row = $this->nextRow();
        $currentCell = $col.$row;
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,'(4) Submit the duly accomplished form in electronic and printed copy (2 copies) to the CSC Field Office-in-Charge');
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(false);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('left')->setVertical('center');
        $row = $this->nextRow();
        $currentCell = $col.$row;
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,' together with the original CSC copy of appointments and supporting documents within the 30th day of the succeeding month.');
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(false);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('left')->setVertical('center');


        // Pertinent data on appointment issued
        $col = 'A';
        $row = $this->nextRow(2);
        $currentCell = $col.$row;
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,'Pertinent data on appointment issued');
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('left')->setVertical('center');

        $this->nextRow(2);

        $this->spreadsheet = $spreadsheet;
        $this->oneColMultiRowField('A',$this->currentRow(),'',3);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(5.5);
        $this->oneColMultiRowField('B',$this->currentRow(),'Date Issued/Effectivity (mm/dd/yyyy)',3);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $this->multiColOneRow('C',$this->currentRow(),'NAME OF APPOINTEE/S','F',true);
        $this->oneColMultiRowField('G',$this->currentRow(),'POSITION TITLE (Indicate parenthetical title if applicable)',3);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('g')->setWidth(22);
        $this->oneColMultiRowField('H',$this->currentRow(),'ITEM NO.',3);
        $this->oneColMultiRowField('I',$this->currentRow(),'SALARY/JOB/PAY GRADE',3);
        $this->oneColMultiRowField('J',$this->currentRow(),'SALARY RATE (Annual)',3);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(14);
        $this->oneColMultiRowField('K',$this->currentRow(),'EMPLOYMENT STATUS',3);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(14);
        $this->oneColMultiRowField('L',$this->currentRow(),"PERIOD OF EMPLOYMENT \n(for Temporary, Casual/ Contractual Appointments) (mm/dd/yyyy to mm/dd/yyyy)",3);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(31);
        $this->oneColMultiRowField('M',$this->currentRow(),'NATURE OF APPOINTMENT',3);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(17);

        $this->multiColOneRow('N',$this->currentRow(),'PUBLICATION','O');
        $this->multiColOneRow('P',$this->currentRow(),'CSC ACTION','R');
        $this->oneColMultiRowField('S',$this->currentRow(),'Agency Receiving Officer',3);

        $this->nextRow();
        $this->oneColMultiRowField('C',$this->currentRow(),'Last Name',2);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $this->oneColMultiRowField('D',$this->currentRow(),'First Name',2);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $this->oneColMultiRowField('E',$this->currentRow(),'Name Extension (Jr./III)',2);
        $this->spreadsheet->getActiveSheet()->getStyle('E'.$this->currentRow())->getFont()->setSize(8);
        $this->oneColMultiRowField('F',$this->currentRow(),'Middle Name',2);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);

        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        // $richText->createText('This is to certify that all requirement and supporting papers pursuant to ');
        $payable = $richText->createTextRun('DATE');
        $payable->getFont()->setBold(true);
        $payable = $richText->createTextRun("\nIndicate date of publication\n(mm/dd/yyyy)");
        $payable->getFont()->setSize(8);

        $this->oneColMultiRowField('N',$this->currentRow(),$richText,2,'top');

        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        // $richText->createText('This is to certify that all requirement and supporting papers pursuant to ');
        $payable = $richText->createTextRun('MODE');
        $payable->getFont()->setBold(true);
        $payable = $richText->createTextRun("\nCSC Bulletin of Vacant Positions");
        $payable->getFont()->setSize(8);
        $this->oneColMultiRowField('O',$this->currentRow(),$richText,2,'top');
        $this->oneColMultiRowField('P',$this->currentRow(),"V-Validated\nINV- Invalidated",2);
        $this->spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(14);
        $this->oneColMultiRowField('Q',$this->currentRow(),'Date of Action (mm/dd/yyyy)',2);
        $this->oneColMultiRowField('R',$this->currentRow(),'Date of Release (mm/dd/yyyy)',2);

        $this->spreadsheet->getActiveSheet()->getRowDimension($this->currentRow())->setRowHeight(51);

        $this->nextRow(3);

        $this->oneColOneRow('A',$this->currentRow(),'');
        $this->oneColOneRow('B',$this->currentRow(),'(1)');
        $this->multiColOneRow('C',$this->currentRow(),'(2)','F');
        

        $cols = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s');

        foreach ($cols as $num => $col) {
            if (!in_array($col, array('a','b','c','d','e','f'))) {
                $this->oneColOneRow($col,$this->currentRow(),'('.($num-3).')');
            }
        }


    }


private function raiFoot($spreadsheet){
        $this->nextRow();
        $cols = array('a','h','n');
        $row = $this->currentRow();
        foreach ($cols as $key => $col) {
            if ($col == 'n') {
                $currentCell = $col.$row;
                $spreadsheet->getActiveSheet()->setCellValue($currentCell,'Post-Audited by:');

            } else {
                $currentCell = $col.$row;
                $spreadsheet->getActiveSheet()->setCellValue($currentCell,'CERTIFICATION:');
            }
            $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(true)->setSize(12);
            $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('left')->setVertical('bottom')->setWrapText(false);
            $spreadsheet->getActiveSheet()->getRowDimension($row)->setRowHeight(36);
        }

$this->nextRow(2);
$row = $this->currentRow();

$texts0 = array(
    ' This is to certify that the information contained in this',
    'report are true, correct and complete based on the Plantilla',
    'of Personnel and appointment/s issued.'
);
$texts1 = array(
    ' This is to certify that the appointment/s issued',
    'is/are in accordance with existing Civil Service Law,',
    'rules and regulations.'
);
$cols = array('a'=>$texts0,'h'=>$texts1);

foreach ($cols as $col => $text) {
    foreach ($text as $i => $text) {
        $currentCell = $col.($row+$i);
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,$text);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setSize(14);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('left')->setVertical('center')->setWrapText(false);
    }
}

$this->multiColOneRow('N',$this->currentRow(),'','P',false,'bottom');




$this->nextRow(3);
$this->spreadsheet->getActiveSheet()->getRowDimension($this->currentRow())->setRowHeight(45);

$this->nextRow(1);

        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $payable = $richText->createTextRun('VERONICA GRACE P. MIRAFLOR');
        $payable->getFont()->setBold(true);
        $payable->getFont()->setUnderline(true);
        $payable->getFont()->setSize(16);
        
        $col = 'A';
        $colb = 'D';
        $row = $this->currentRow();
        $currentCell = $col.$row;
        $currentMCell = $currentCell.':'.$colb.$row;
        $this->spreadsheet->getActiveSheet()->mergeCells($currentMCell);
        $this->spreadsheet->getActiveSheet()->setCellValue($currentCell,$richText);
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setSize(16);
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('center')->setVertical('center');

        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $payable = $richText->createTextRun('PRYDE HENRY A. TEVES');
        $payable->getFont()->setBold(true);
        $payable->getFont()->setUnderline(true);
        $payable->getFont()->setSize(16);

        $col = 'H';
        $colb = 'L';
        $row = $this->currentRow();
        $currentCell = $col.$row;
        $currentMCell = $currentCell.':'.$colb.$row;
        $this->spreadsheet->getActiveSheet()->mergeCells($currentMCell);
        $this->spreadsheet->getActiveSheet()->setCellValue($currentCell,$richText);
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setSize(16);
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('center')->setVertical('center');

        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $payable = $richText->createTextRun('');
        $payable->getFont()->setBold(true);
        $payable->getFont()->setUnderline(true);
        $payable->getFont()->setSize(16);

        $col = 'n';
        $colb = 'p';
        $row = $this->currentRow();
        $currentCell = $col.$row;
        $currentMCell = $currentCell.':'.$colb.$row;
        $this->spreadsheet->getActiveSheet()->mergeCells($currentMCell);
        $this->spreadsheet->getActiveSheet()->setCellValue($currentCell,$richText);
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setSize(16);
        $this->spreadsheet->getActiveSheet()->getStyle($currentMCell)->getBorders()->getBottom()->setBorderStyle('thin');
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('center')->setVertical('center');

        $this->nextRow();

        $col = 'A';
        $colb = 'D';
        $row = $this->currentRow();
        $currentCell = $col.$row;
        $currentMCell = $currentCell.':'.$colb.$row;
        $this->spreadsheet->getActiveSheet()->mergeCells($currentMCell);
        $this->spreadsheet->getActiveSheet()->setCellValue($currentCell,'HRMO IV');
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setSize(16);
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('center')->setVertical('top');

        $col = 'H';
        $colb = 'L';
        $row = $this->currentRow();
        $currentCell = $col.$row;
        $currentMCell = $currentCell.':'.$colb.$row;
        $this->spreadsheet->getActiveSheet()->mergeCells($currentMCell);
        $this->spreadsheet->getActiveSheet()->setCellValue($currentCell,'City Mayor');
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setSize(16);
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('center')->setVertical('top');

        $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
        $payable = $richText->createTextRun('_____________________________');
        $payable->getFont()->setBold(true);
        $payable->getFont()->setUnderline(true);


        $col = 'n';
        $colb = 'p';
        $row = $this->currentRow();
        $currentCell = $col.$row;
        $currentMCell = $currentCell.':'.$colb.$row;
        $this->spreadsheet->getActiveSheet()->mergeCells($currentMCell);
        $this->spreadsheet->getActiveSheet()->setCellValue($currentCell,'CSC Official');
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setSize(16);
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('center')->setVertical('top');

        $this->spreadsheet->getActiveSheet()->getRowDimension($this->currentRow())->setRowHeight(35);
        $this->nextRow();


        $col = 'A';
        $colb = 'S';
        $row = $this->currentRow();
        $currentCell = $col.$row;
        $currentMCell = $currentCell.':'.$colb.$row;
        $spreadsheet->getActiveSheet()->mergeCells($currentMCell);
        $this->spreadsheet->getActiveSheet()->setCellValue($currentCell,'For CSC use only:');
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(true)->setItalic(true);
        $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('left')->setVertical('center')->setWrapText(false);
        $this->spreadsheet->getActiveSheet()->getStyle($currentMCell)->getBorders()->getBottom()->setBorderStyle('double');

        
        for ($i=0; $i < 6; $i++) { 

            $this->nextRow();
            $row = $this->currentRow();
                $currentCell = $col.($row);
                $currentMCell = $currentCell.':'.$colb.($row);
                $spreadsheet->getActiveSheet()->mergeCells($currentMCell);
            if ($i == 0) {
                $this->spreadsheet->getActiveSheet()->setCellValue($currentCell,'REMARKS/COMMENTS/RECOMMENDATIONS (e.g. Reasons for Invalidation)');    
            } else {
                $this->spreadsheet->getActiveSheet()->setCellValue($currentCell,'');    
            }

            $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(true)->setItalic(true);
            $this->spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('left')->setVertical('center')->setWrapText(false);
            $this->spreadsheet->getActiveSheet()->getStyle($currentMCell)->getBorders()->getOutline()->setBorderStyle('thin');
            $this->spreadsheet->getActiveSheet()->getRowDimension($this->currentRow())->setRowHeight(20);     
        }
    }

    private function setColAutoSize($col){
        $spreadsheet = $this->spreadsheet;
        $spreadsheet->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
    }

    private function oneColOneRow($col,$row,$string,$bold=false){
        $spreadsheet = $this->spreadsheet;
        $col = $col;
        $row = $row;
        $currentCell = $col.$row;
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,$string);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold($bold);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getBorders()->getOutline()->setBorderStyle('thin');
    }

    private function oneColMultiRowField ($col,$row,$string,$rows,$valign='center',$border='outline'){
        $spreadsheet = $this->spreadsheet;
        $col = $col;
        $row = $row;
        $currentCell = $col.$row;
        $currentMCell = $currentCell.':'.$col.($row+$rows);
        $spreadsheet->getActiveSheet()->mergeCells($currentMCell);
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,$string);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold(false);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('center')->setVertical($valign)->setWrapText(true);
        if ($border == 'bottom') {
            $spreadsheet->getActiveSheet()->getStyle($currentMCell)->getBorders()->getBottom()->setBorderStyle('thin');
        } elseif ($border == 'outline') {
            $spreadsheet->getActiveSheet()->getStyle($currentMCell)->getBorders()->getOutline()->setBorderStyle('thin');
        }
    }

    private function multiColOneRow($col,$row,$string,$colb,$bold=false,$border='outline'){
        $spreadsheet = $this->spreadsheet;
        $col = $col;
        $row = $row;
        $currentCell = $col.$row;
        $currentMCell = $currentCell.':'.$colb.$row;
        $spreadsheet->getActiveSheet()->mergeCells($currentMCell);
        $spreadsheet->getActiveSheet()->setCellValue($currentCell,$string);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getFont()->setBold($bold);
        $spreadsheet->getActiveSheet()->getStyle($currentCell)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        if ($border == 'bottom') {
            $spreadsheet->getActiveSheet()->getStyle($currentMCell)->getBorders()->getBottom()->setBorderStyle('thin');
        } elseif ($border == 'outline') {
            $spreadsheet->getActiveSheet()->getStyle($currentMCell)->getBorders()->getOutline()->setBorderStyle('thin');
        }
    }

    private function nextRow($rowApart=1){
        $this->lastRow = $this->lastRow+$rowApart;
        return ($this->lastRow);
    }

    private function currentRow(){
        return $this->lastRow;
    }

    private function  currentMergedCell($cola,$rowa,$colb,$rowb){
        $this->currentMergedCell = $cola.$rowa.":".$colb.$rowb;
        return $this->currentMergedCell;
    }


}
