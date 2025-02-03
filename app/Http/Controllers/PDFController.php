<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Cost;
use App\Models\Deduct;
use App\Models\DeductedItem;
use App\Models\Deposit;
use App\Models\DoctorShift;
use App\Models\Doctorvisit;
use App\Models\Item;
use App\Models\LabRequest;
use App\Models\MainTest;
use App\Models\Order;
use App\Models\OrderMeal;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PrescribedDrug;
use App\Models\RequestedChildMeal;
use App\Models\RequestedResult;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Settings;
use App\Models\Shift;
use App\Models\Shipping;
use App\Models\Specialist;
use App\Models\User;
use App\Models\Whatsapp;
use Carbon\Carbon;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use App\Mypdf\Pdf;
use Illuminate\Support\Facades\DB;
use NumberToWords\NumberToWords;
use PhpOffice\PhpSpreadsheet\Calculation\Database;
use ReflectionObject;
use TCPDF_FONTS;
use Spatie\Permission\Models\Permission;
function extractBase64FromOutput($pdfOutput) {
    // Use a regex to find and extract the Base64 content
    $pattern = '/^Content-Type: application\/pdf;.*?base64\s*(.+)$/s';

    if (preg_match($pattern, $pdfOutput, $matches)) {
        // Return the Base64 part (captured in group 1)
        return trim($matches[1]);
    }

    // Return false if the Base64 part isn't found
    return false;
}
class PDFController extends Controller
{


    public function __construct()
    {
//        $this->middleware(['permission:add items']);

    }


    public function orders(Request $request)
    {


        $pdf = new Pdf('l', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setFontSubsetting(true);
        $pdf->setCompression(true);

        $lg = array();
        $lg['a_meta_charset'] = 'UTF-8';
        $lg['a_meta_dir'] = 'rtl';
        $lg['a_meta_language'] = 'fa';
        $lg['w_page'] = 'page';
        $pdf->setLanguageArray($lg);
        $pdf->setCreator(PDF_CREATOR);
        $pdf->setAuthor('Nicola Asuni');
        $pdf->setTitle('الطلبات');
        $pdf->setSubject('TCPDF Tutorial');
        $pdf->setKeywords('TCPDF, PDF, example, test, guide');
        $pdf->setHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->setHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->setFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->setAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setFont('times', 'BI', 12);
        $pdf->AddPage();
        $page_width = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
        $fontname = TCPDF_FONTS::addTTFfont(public_path('arial.ttf'));
        $pdf->setFont($fontname, '', 12);

        $date = new Carbon('now');
        $date = $date->format('Y/m/d');
        $pdf->head = function ()use($pdf,$date){
            $pdf->Cell(30, 5, $date, 1, 0, 'C');

        };

        $img = public_path('logo.png');
//        dd($img);
//        $pdf->Image($img,25,5,20,20);
        $pdf->setFont($fontname, '', 22);
        $settings = Settings::first();

        $pdf->Cell($page_width, 5, $settings?->kitchen_name, 0, 1, 'C');
        $pdf->Cell($page_width, 5, 'الطلبات', 0, 1, 'C');
        $pdf->Ln();
        $pdf->setFont($fontname, 'b', 16);
        $pdf->setFillColor(0, 0, 0);
        $col = $page_width / 6;
        $pdf->Cell(20, 5, 'التاريخ ', 0, 0, 'C', fill: 0);
        $pdf->Cell(25, 5, $date, 0, 1, 'C');
        $pdf->Ln();
        $pdf->setFont($fontname, 'b', 10);

        $pdf->Cell($col/2,5,'Order Id',1,0,'C',true);
        $pdf->Cell($col*2,5,'Name',1,0,'C',true);
        $pdf->Cell($col/2,5,'Total',1,0,'C',true);
        $pdf->Cell($col/2,5,'Paid',1,0,'C',true);
        $pdf->Cell($col/2,5,'Remaining',1,0,'C',true);
        $pdf->Cell($col*2,5,'Details',1,1,'C',true);
        $orders  = Order::whereDate('created_at','=',$date)->get();
        $total_total_f = 0;
        $total_paid_f = 0;
        $total_remaining_f = 0;
        /** @var Order $order */
        foreach ($orders as $order){
            $y = $pdf->GetY();

            $pdf->Line(15,$y,$page_width +15,$y);
            $pdf->Cell($col/2,5,$order->id,'0',0,'C',0);
            $pdf->Cell($col*2,5,$order?->customer?->name,'0',0,'C',0);
            $pdf->Cell($col/2,5,$order->totalPrice(),'0',0,'C',0);
            $pdf->Cell($col/2,5,$order->amount_paid,'0',0,'C',0);
            $pdf->Cell($col/2,5,$order->totalPrice() - $order->amount_paid,'0',0,'C',0);
            $total_total_f += $order->totalPrice();
            $total_paid_f += $order->amount_paid;
            $total_remaining_f += $order->totalPrice() - $order->amount_paid;
            $pdf->MultiCell($col*2,5,$order->orderMealsNames(),'0','L',0,1);
            $y = $pdf->GetY();

            $pdf->Line(15,$y,$page_width +15,$y);
        }
        $pdf->Cell($col/2,5,'','0',0,'C',0);
        $pdf->Cell($col*2,5,'','0',0,'C',0);
        $pdf->Cell($col/2,5,$total_total_f,'0',0,'C',0);
        $pdf->Cell($col/2,5,$total_paid_f,'0',0,'C',0);
        $pdf->Cell($col/2,5,$total_remaining_f,'0',0,'C',0);
        $pdf->MultiCell($col*2,5,'','0','L',0,1);

        $pdf->Ln();
        $arial = TCPDF_FONTS::addTTFfont(public_path('arial.ttf'));

        $qeury = Order::query();
        $delivery_date= $request->get('date');
        $qeury->when($delivery_date,function (\Illuminate\Database\Eloquent\Builder $q) use ($request,$delivery_date){
            $query_date = Carbon::parse($delivery_date)->format('Ymd');
            $q->whereRaw("Date(created_at) =  ? Or Date(delivery_date) =  ? ",[$query_date,$query_date]);
        });

        $orders=  $qeury->get();







        $pdf->Ln();

        $pdf->Output('example_003.pdf', 'I');
    }
    public function printSale(Request $request,$order_id,$wb = false)
    {
        //سعدنا بزيارتكم اسم العميل نتمني لكم دوام الصحه والعافيه

        $order = Order::find($request->get('order_id') ?? $order_id);
         $totalChildren = $order->mealOrders->reduce(function ($prev,$curr){
            return $prev + $curr->requestedChildMeals->count();
        },0);
        $count =  $order->mealOrders->count();
        $custom_layout = array(80, 120 + ($count * 5)*3 + ($totalChildren * 5)) ;
        $pdf = new Pdf('portrait', PDF_UNIT, $custom_layout, true, 'UTF-8', false);
        $lg = array();
        $lg['a_meta_charset'] = 'UTF-8';
        $lg['a_meta_dir'] = 'rtl';
        $lg['a_meta_language'] = 'fa';
        $lg['w_page'] = 'page';
        $pdf->setLanguageArray($lg);
        $lg = array();

        $pdf->setCreator(PDF_CREATOR);
        $pdf->setAuthor('alryyan mahjoob');
        $pdf->setTitle('ticket');
        $pdf->setSubject('ticket');
        $pdf->setMargins(10, 0, 10);
//        $pdf->setHeaderMargin(PDF_MARGIN_HEADER);
//        $pdf->setFooterMargin(0);
        $page_width = 65;
//        echo  $pdf->getPageWidth();
        $arial = TCPDF_FONTS::addTTFfont(public_path('arial.ttf'));
        $pdf->AddPage();
        $settings= Settings::all()->first();
        $img_base64_encoded =  $settings->header_base64;
        $img = base64_decode(preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded));
        $pdf->Ln();
        if ($settings->is_logo ){
//            $pdf->Image("@".$img, 50 , 0, 80, 20,align: 'C',fitbox: 1);// radi
            $pdf->Image("@".$img, 50 , 5, 80, 25,align: 'C',fitbox: 1); //اسعد

        }

        $pdf->setAutoPageBreak(TRUE, 0);
        $pdf->setMargins(10, 0, 10);

        //$pdf->Ln(25);
        $pdf->SetFillColor(255, 255, 255);

        $pdf->SetFont($arial, '', 7, '', true);
//        $pdf->Cell(60,5,$order->created_at->format('Y/m/d H:i A'),0,1);

        $pdf->Ln(25);

        $pdf->Cell($page_width,5,$settings->hospital_name,0,1,'C');
//        $pdf->Cell($page_width,5,'مسقط - عمان',0,1,'C');
        $pdf->SetFont($arial, '', 10, '', true);
        $pdf->SetFont($arial, '', 7, '', true);

        $colWidth = $page_width/3;
        $pdf->SetFont($arial, '', 13, '', true);

        $pdf->Cell($page_width,10,'  فاتورة Invoice',0,1,'C',fill: 1);
        $pdf->SetFont($arial, '', 10, '', true);

//        $pdf->Cell($page_width,5,'VATIN '.$settings->vatin,0,1,'C',fill: 0);
        $pdf->Cell(15,5,'رقم الطلب :',0,0);
        $pdf->Cell(35,5,$order->id,0,0,'C');
        $pdf->Cell(15,5,'Oder No :',0,1,'L');

        $pdf->Cell(15,5,'التاريخ :',0,0);
        $pdf->Cell(35,5,$order->created_at->format('Y-m-d H:i A'),0,0,'C');
        $pdf->Cell(15,5,'Date :',0,1,'L');

        $pdf->Cell(15,5,'المستخدم :',0,0);
        $pdf->Cell(35,5,$order->user->username,0,0,'C');
        $pdf->Cell(15,5,'User :',0,1,'L');

        $pdf->Cell(15,5,'اسم العميل :',0,0);
        $pdf->Cell(35,5,$order->customer->name ?? 'Default Client',0,0,'C');
        $pdf->Cell(15,5,'To :',0,1,'L');

        $pdf->Cell(15,5,'هاتف العميل  :',0,0);
        $pdf->Cell(35,5,$order?->customer?->phone ,0,0,'C');
        $pdf->Cell(15,5,'Phone :',0,1,'L');

        $pdf->Cell(15,5,'عنوان العميل  :',0,0);
        $pdf->Cell(35,5,$order?->customer?->area .' /'. $order->customer->state ,0,0,'C');
        $pdf->Cell(15,5,'Address :',0,1,'L');
//        $pdf->SetFont($arial, 'u', 14, '', true);



//        $pdf->Ln();
//        $pdf->Cell(15,5,'Date',0,0);

//        $pdf->Ln();
        $pdf->SetFont($arial, 'u', 10, '', true);

//        $pdf->Cell(25,5,'Requested Items',0,1,'L');

        $pdf->SetFont($arial, '', 8, '', true);
        $colWidth = $page_width/4;
        $x = 50;
        $y = 160;
        $w = 100;
        $h = 40;
        $style = 'DF'; // Border and fill

        $pdf->SetDrawColor(0, 0, 0); // Black border
     //   $pdf->SetFillColor(255, 200, 200); // Light red fill
        $index = 1 ;
        $colWidth = $page_width/3;

        $pdf->Cell($colWidth * 2,5,'Name ','B',0,fill: 0);
        $pdf->Cell($colWidth/2,5,' ','B',0,fill: 0);
        $pdf->Cell($colWidth/2,5,'Price ','B',1,fill: 0);

        /** @var OrderMeal $orderMeal */
        foreach ($order->mealOrders as $orderMeal){
//            rgb(232, 234, 246)
          //  $pdf->SetFillColor(232, 234, 246); // Light red fill
            $y = $pdf->GetY();
            $count =$orderMeal->requestedChildMeals->count();

            $pdf->Rect(5, $y, $page_width,  ($count * 5)  + 10, $style);
//            rgb(187, 222, 251)
//            $pdf->SetLineStyle(array('width' => 0.1, 'dash' => '3,3', 'color' => array(0, 0, 0)));

           // $pdf->SetFillColor(187, 222, 251); // Light red fill
            $pdf->Cell(5,5,$index ,1,0,fill: 0,stretch: 1);
            $colWidth = $page_width /3;
            $pdf->Cell($colWidth * 2.3 ,5,$orderMeal->meal->name,1,0,fill: 0);
//            $pdf->Cell($colWidth/2,5,' ','TB',0,fill: 1);
            $pdf->Cell(($colWidth/2) - 0.5,5, $orderMeal->price,1,1,fill: 0,align: 'C');
            $colWidth = $page_width/3;
//
            $pdf->Cell($colWidth*2,5,'Item ','B',0,fill: 0);
            $pdf->Cell($colWidth/2,5,'QYN ','B',0,fill: 0);
            $pdf->Cell($colWidth/2,5,'Price ','B',1,fill: 0);
//            $pdf->Ln();
            /** @var RequestedChildMeal $requestedChildMeal */
            foreach ($orderMeal->requestedChildMeals as $requestedChildMeal){
                $pdf->Cell($colWidth*2,5,$requestedChildMeal->childMeal->service->name,'B',0,fill: 0);
//                $pdf->Cell($colWidth/2,5,'','B',0,fill: 0); //comment this line if using del-pasta
                $pdf->Cell($colWidth/2,5,$requestedChildMeal->quantity * $requestedChildMeal->count ,'B',0,fill: 0); //del pasta
                $pdf->Cell($colWidth/2,5,$requestedChildMeal->price * $requestedChildMeal->count,'B',1,fill: 0,align: 'C');

            }
            $index++;
            $pdf->Ln();
        }
//

        $pdf->Ln();
        $style = array(
            'position' => 'C',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255),
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4
        );




//        $pdf->Ln();
//        $pdf->write1DBarcode("$order->id", 'C128', '', '', '40', 18, 0.4, $style, 'N');
//        $pdf->Ln();
        $cols = $page_width / 3;
        $y = $pdf->GetY();



        $pdf->SetFont($arial, '', 10, '', true);
        if ($order->is_delivery) {
            $pdf->Cell($cols, 5, 'ر.توصيل', 'TB', 0, 'C', fill: 0);
            $pdf->Cell($cols, 5, $order->delivery_fee, 'TB', 0, 'C', 0);
            $pdf->Cell($cols, 5, 'Delivery Fee', 'TB', 1, 'C', fill: 0);
        }
        $pdf->SetFont($arial, 'b', 15, '', true);

        $pdf->Cell($cols,5,'المجموع','TB',0,'C',fill: 0);
        $pdf->Cell($cols,5,$order->totalPrice() ,'TB' ,0,'C',0);
        $pdf->Cell($cols ,5,'Total','TB',1,'C',fill: 0);
        $pdf->SetFont($arial, '', 10, '', true);

//        $pdf->Cell($cols,5,'المدفوع','TB',0,'C',fill: 0);
//        $pdf->Cell($cols,5,$order->amount_paid ,'TB' ,0,'C',0);
//        $pdf->Cell($cols ,5,'Paid','TB',1,'C',fill: 0);
//




//        $pdf->Cell($cols,5,'الدفع','TB',0 ,'C',fill: 0);
//        $pdf->Cell($cols,5,$order->payment_type ,'TB' ,0,'C',0);
//
//        $pdf->Cell($cols ,5,'Payment','TB' ,1,'C',fill: 0);
//        $pdf->Cell($cols,5,' التوصيل','TB',0,'C',fill: 0);
//        $pdf->Cell($cols,5,$order->is_delivery ? 'نعم':'لا' ,'TB'  ,0,'C',0);


//        $pdf->Cell($cols ,5,'Delivery','TB',1,'C',fill: 0);
        if ($order->is_delivery){
            $pdf->Cell($page_width,5,''.$order->delivery_address,0,1,'C');

        }
        $pdf->Cell($page_width,5,''.$order->notes,0,1,'C');

        $y = $pdf->GetY();





        $col = $page_width / 2;
//        $pdf->Cell($col,5,'CR'.$settings->cr,0,0,'C');
//        $pdf->Cell($col,5,'GSM'.$settings->phone,0,1,'C');
//        $pdf->Cell($col,5,'Email:'.$settings->email,0,0,'C');
//        $pdf->Cell( $col,5,$settings->address.'  Address',0,1,'C');


        if ($wb){
            $result_as_bs64 = $pdf->output('name.pdf', 'S');
//            Whatsapp::sendPdf($result_as_bs64, $order->customer->phone);
             $wa = new WaController();
                          $wa->sendDocument($request,$result_as_bs64,$order?->customer?->phone);

            //  $wa->sendDocument($request,$result_as_bs64);
        }

        if ($request->has('base64')) {
            if ($request->get('base64')== 2){
                $result_as_bs64 = $pdf->output('name.pdf', 'E');
               $data =  substr($result_as_bs64,strpos($result_as_bs64,'JVB'));
//               return  $data;
//                return  extractBase64FromOutput($result_as_bs64);

                $wa = new WaController();

              return  $wa->sendDocument($request,$data,$order?->customer?->phone);

            }else{
                $result_as_bs64 = $pdf->output('name.pdf', 'E');
                return $result_as_bs64;
            }


        } else {
            $pdf->output();

        }

    }

}
