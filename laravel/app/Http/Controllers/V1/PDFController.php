<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\Votante;
use Illuminate\Http\Request;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;


class PDFController extends Controller
{
    protected $user;
    protected $model;

    public function __construct(Request $request)
    {
        $token = $request->header('Authorization');
        $this->model=Persona::class;
        if($token != '')
            //En caso de que requiera autentifiaci��n la ruta obtenemos el usuario y lo almacenamos en una variable, nosotros no lo utilizaremos.
            $this->user = JWTAuth::parseToken()->authenticate();
    }

    public function votantesByLider(Request $request){

        $data = $request->only('lider', 'sublider', 'ordernar', 'forma');

        $lider=$request->lider;
        $sublider=$request->sublider;
        $logo=asset("/laravel/resources/css/LogoJulio.png");
        $logoPartidos=asset("/laravel/resources/css/LogoPartidos.png");
        $marcaagua=asset("/laravel/resources/css/marcadeagua.png");
        $banner=asset("/laravel/resources/css/Banner.png");
        $base64String="";

            $infoLider=Persona::getLider($lider);
            $infoSublider=Persona::getDatosSublider($sublider);
            if(!empty($request->ordenar)){
                $votantes=Votante::getByLider($lider, $sublider)->orderBy($request->ordenar, $request->forma)->get();
            }else{
                $votantes=Votante::getByLider($lider, $sublider)->get();
            }
            $pdf = app('Fpdf',);
        $pdf->AddPage('L', 'Legal');
        $pdf->Image($logo, 10, 10, 50);
        $pdf->Image($logoPartidos, 300, 10, -300);
        //$pdf->Image($marcaagua, 10, 40, 400);
        $pdf->SetFont('Arial','B',20);
        $pdf->SetXY(120, 20);
        $pdf->Cell(40,10,"ASAMBLEA 2024 -2027");
        $pdf->SetFont('Arial','B',16);
        $pdf->SetXY(135, 30);
        $pdf->Image($banner, 135, 30, 50);
        //$pdf->Cell(40,10,"#SucreMiPrioridad");
        $pdf->SetXY(20, 40);
        //Responsable
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(40,10,"RESPONSABLE");
        $pdf->SetXY(20, 50);
        $pdf->Cell(40,10,"NOMBRE: ".utf8_decode($infoLider->nombrecompleto),0);
        $pdf->SetXY(130, 50);
        $pdf->Cell(40,10,utf8_decode("CEDULA: ").utf8_decode($infoLider->numerodocumento),0);
        $pdf->SetXY(20, 58);
        $pdf->Cell(40,10,"CELULAR: ".utf8_decode($infoLider->telefono),0);
        $pdf->SetXY(130, 58);
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(40,10,"BARRIO: ".utf8_decode($infoLider->barrio->descripcion),0);
        //Fin

        if(!empty($sublider)){
            //Colaborador
            $pdf->SetXY(185, 40);
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(40,10,"COLABORADOR");
            $pdf->SetXY(185, 50);
            $pdf->Cell(40,10,"NOMBRE: ".utf8_decode($infoSublider->nombrecompleto),0);
            $pdf->SetXY(290, 50);
            $pdf->Cell(40,10,utf8_decode("CEDULA: ").utf8_decode($infoSublider->numerodocumento),0);
            $pdf->SetXY(185, 58);
            $pdf->Cell(40,10,"CELULAR: ".utf8_decode($infoSublider->telefono),0);
            $pdf->SetXY(290, 58);
            $pdf->SetFont('Arial','B',8);
            $pdf->Cell(40,10,"BARRIO: ".utf8_decode($infoSublider->barrio->descripcion),0);
            //Fin
        }
        $pdf->SetFillColor(232,232,232);
        $pdf->SetXY(10, 70);
        $pdf->Cell(10,7,utf8_decode("N"), 1,0, 'C', 1);
        $pdf->Cell(25,7,utf8_decode("CEDULA"), 1,0, 'C', 1);
        $pdf->Cell(100,7,"NOMBRE",1,0, 'C', 1);
        $pdf->Cell(25,7,"CELULAR",1,0, 'C', 1);
        $pdf->Cell(50,7,"MUNICIPIO",1,0, 'C', 1);
        $pdf->Cell(95,7,"PUESTO",1,0,'C', 1);
        $pdf->Cell(12,7,"MESA",1, 0, 'C', 1);
         $pdf->Cell(15,7,"CONF",1, 0, 'C', 1);
        $pdf->Ln();
        $pos=1;

        if(empty($sublider)){
            $sublideres=Persona::getSublideres($lider);
            foreach($sublideres as $item){
                $pdf->SetFillColor(255,255,255);
                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(10,5, $pos,1);
                $pdf->Cell(25,5, $item->numerodocumento,1);
                $pdf->Cell(100,5,utf8_decode($item->nombrecompleto),1);
                $pdf->Cell(25,5,$item->telefono,1);
                $pdf->Cell(50,5,$item->municipio,1);
            if( $item->puestovotacion){
                //$pdf->Cell(60,5,$item->puesto->descripcion,1);
                $pdf->Cell(95,5, utf8_decode( $item->puestovotacion),1);
            }else{
                $pdf->Cell(95,5,"",1);
            }
                //$pdf->Cell(20,5,$item->mesa,1);
                $pdf->Cell(12,5,$item->mesavotacion,1);
                $pdf->Cell(15,5,'NO',1);
                $pos++;
                $pdf->Ln();
            }
        }

        foreach($votantes as $item){
            $pdf->SetFillColor(255,255,255);
            $pdf->SetFont('Arial','',9);
            $pdf->Cell(10,5, $pos,1);
            $pdf->Cell(25,5, $item->numerodocumento,1);
            $pdf->Cell(100,5,utf8_decode($item->nombrecompleto),1);
            $pdf->Cell(25,5,$item->telefono,1);
            $pdf->Cell(50,5,$item->municipio,1);
           if( $item->puesto){
            //$pdf->Cell(60,5,$item->puesto->descripcion,1);
             $pdf->Cell(95,5, utf8_decode( $item->puestovotacion),1);
           }else{
            $pdf->Cell(95,5,"",1);
           }
            //$pdf->Cell(20,5,$item->mesa,1);
             $pdf->Cell(12,5,$item->mesavotacion,1);
              $pdf->Cell(15,5,'NO',1);
            $pos++;
            $pdf->Ln();
        }
         $base64String = chunk_split(base64_encode($pdf->Output('S')));
        return response()->json([
            'code'=>200,
            'pdf' => $base64String
        ], Response::HTTP_OK);

    }

    public function reporteLiderYSublider(Request $request){
        $infoLideres=Persona::getEstadisticasLider();

        $pdf = app('Fpdf',);
        $pdf->AddPage('H', 'Legal');
        $pdf->SetFont('Arial','B',11);
            $pdf->Cell(120,5, "NOMBRE LIDER", 1);
             $pdf->Cell(45,5, "NUMERO DOCUMENTO", 1);
            $pdf->Cell(40,5, "CELULAR", 1);
            $pdf->Cell(50,5, "CANTIDAD DE VOTOS", 1);
            $pdf->Cell(50,5, "CANTIDAD SULIDERES",1);
            $pdf->Ln();
        foreach ($infoLideres as $item) {
            $pdf->SetFont('Arial','B',11);
            $pdf->SetFillColor(232,232,232);
            $pdf->Cell(120,5, utf8_decode($item->nombrecompleto),1,0,1);
            $pdf->Cell(45,5, utf8_decode($item->numerodocumento),1,0,1);
            $pdf->Cell(40,5, utf8_decode($item->telefono),1,0,1);
            $pdf->Cell(50,5, $item->totalvotos,1,0,1);
            $pdf->Cell(50,5, $item->totalsublider,1,0,1);
            $pdf->Ln();
            $infoSublider=Persona::getEstadisticasSublider($item->id);
            foreach ($infoSublider as $sublider) {
                $pdf->SetFont('Arial','',11);
                $pdf->SetFillColor(255,255,255);
                $pdf->Cell(120,5, utf8_decode($sublider->nombrecompleto),1);
                $pdf->Cell(45,5, utf8_decode($sublider->numerodocumento),1,0,1);
                $pdf->Cell(40,5, utf8_decode($sublider->telefono),1,0,1);
                $pdf->Cell(50,5, $sublider->totalvotos,1);
                $pdf->Ln();
            }

        }
        //$pdf->Output();
        $base64String = chunk_split(base64_encode($pdf->Output('S')));
        return response()->json([
            'code'=>200,
            'pdf' => $base64String
        ], Response::HTTP_OK);

    }

    public function reportePuestosVotacion(Request $request){
        $data=Votante::getTotalPuestoVotacion();
        $pdf = app('Fpdf',);
        $pdf->AddPage('H', 'Legal');
        $pdf->SetFont('Arial','B',11);
            $pdf->Cell(120,5, "NOMBRE PUESTO", 1);
            $pdf->Cell(120,5, "DIRECCION", 1);
             $pdf->Cell(50,5, "CANTIDAD DE VOTANTES", 1);
            $pdf->Ln();
        foreach ($data as $item) {
            $pdf->SetFont('Arial','',10);
            $pdf->SetFillColor(232,232,232);
            $pdf->Cell(120,5, utf8_decode($item->puestovotacion),1,0,1);
            $pdf->Cell(120,5, utf8_decode($item->direccion),1,0,1);
            $pdf->Cell(50,5, utf8_decode($item->totalvotos),1,0,1);
            $pdf->Ln();
        }
        //$pdf->Output();
        $base64String = chunk_split(base64_encode($pdf->Output('S')));
        return response()->json([
            'code'=>200,
            'pdf' => $base64String
        ], Response::HTTP_OK);

    }

    public function reportePuestoYMesa(Request $request){
        $data=Votante::getTotalPuestoVotacion();

        $pdf = app('Fpdf',);
        $pdf->AddPage('H', 'Legal');
        $pdf->SetFont('Arial','B',11);
            $pdf->Cell(120,5, "NOMBRE PUESTO", 1);
            $pdf->Cell(50,5, "CANTIDAD DE VOTANTES", 1);
            $pdf->Ln();
        foreach ($data as $item) {
            $pdf->SetFont('Arial','B',11);
            $pdf->SetFillColor(232,232,232);
            $pdf->Cell(120,5, utf8_decode($item->puestovotacion),1,0,1);
            $pdf->Cell(50,5, utf8_decode($item->totalvotos),1,0,1);
            $pdf->Ln();
            $mesas=Votante::getTotalMesaVotacion($item->puestovotacion);
            foreach ($mesas as $mesa) {
                $pdf->SetFont('Arial','',11);
                $pdf->SetFillColor(255,255,255);
                $pdf->Cell(120,5, utf8_decode($mesa->mesavotacion),1);
                $pdf->Cell(50,5, utf8_decode($mesa->totalvotos),1,0,1);
                $pdf->Ln();
            }

        }
        //$pdf->Output();
        $base64String = chunk_split(base64_encode($pdf->Output('S')));
        return response()->json([
            'code'=>200,
            'pdf' => $base64String
        ], Response::HTTP_OK);

    }

    public function votantesCodigosByLider(Request $request){

        $data = $request->only('lider', 'sublider', 'ordernar', 'forma');

        $lider=$request->lider;
        $sublider=$request->sublider;
        $logo=asset("/laravel/resources/css/LogoJulio.png");
        $logoPartidos=asset("/laravel/resources/css/LogoPartidos.png");
        $marcaagua=asset("/laravel/resources/css/marcadeagua.png");
        $banner=asset("/laravel/resources/css/Banner.png");
        $base64String="";
        $sincelejo=0;
        $otrasciudades=0;
        $total=0;

            $infoLider=Persona::getLider($lider);
            $infoSublider=Persona::getDatosSublider($sublider);
            if(!empty($request->ordenar)){
                $votantes=Votante::getByLiderCodigo($lider, $sublider);
            }else{
                $votantes=Votante::getByLiderCodigo($lider, $sublider);
            }
            $pdf = app('Fpdf',);
        $pdf->AddPage('L', 'Legal');
        $pdf->Image($logo, 10, 10, 50);
        $pdf->Image($logoPartidos, 300, 10, -300);
        //$pdf->Image($marcaagua, 10, 40, 400);
        $pdf->SetFont('Arial','B',20);
        $pdf->SetXY(120, 20);
        $pdf->Cell(40,10,"ASAMBLEA 2024 -2027");
        $pdf->SetFont('Arial','B',16);
        $pdf->SetXY(135, 30);
        $pdf->Image($banner, 135, 30, 50);
        //$pdf->Cell(40,10,"#SucreMiPrioridad");
        $pdf->SetXY(20, 40);
        //Responsable
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(40,10,"RESPONSABLE");
        $pdf->SetXY(20, 50);
        $pdf->Cell(40,10,"NOMBRE: ".utf8_decode($infoLider->nombrecompleto),0);
        $pdf->SetXY(130, 50);
        $pdf->Cell(40,10,utf8_decode("CEDULA: ").utf8_decode($infoLider->numerodocumento),0);
        $pdf->SetXY(20, 58);
        $pdf->Cell(40,10,"CELULAR: ".utf8_decode($infoLider->telefono),0);
        $pdf->SetXY(130, 58);
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(40,10,"BARRIO: ".utf8_decode($infoLider->barrio->descripcion),0);
        //Fin

        if(!empty($sublider)){
            //Colaborador
            $pdf->SetXY(185, 40);
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(40,10,"COLABORADOR");
            $pdf->SetXY(185, 50);
            $pdf->Cell(40,10,"NOMBRE: ".utf8_decode($infoSublider->nombrecompleto),0);
            $pdf->SetXY(290, 50);
            $pdf->Cell(40,10,utf8_decode("CEDULA: ").utf8_decode($infoSublider->numerodocumento),0);
            $pdf->SetXY(185, 58);
            $pdf->Cell(40,10,"CELULAR: ".utf8_decode($infoSublider->telefono),0);
            $pdf->SetXY(290, 58);
            $pdf->SetFont('Arial','B',8);
            $pdf->Cell(40,10,"BARRIO: ".utf8_decode($infoSublider->barrio->descripcion),0);
            //Fin
        }
        $pdf->SetFillColor(232,232,232);
        $pdf->SetXY(10, 70);
        $pdf->Cell(10,7,utf8_decode("N°"), 1,0, 'C', 1);
        $pdf->Cell(23,7,utf8_decode("CEDULA"), 1,0, 'C', 1);
        $pdf->Cell(100,7,"NOMBRE",1,0, 'C', 1);
        $pdf->Cell(20,7,"CELULAR",1,0, 'C', 1);
        $pdf->Cell(20,7,"COD",1,0, 'C', 1);
        $pdf->Cell(50,7,"MUNICIPIO",1,0, 'C', 1);
        $pdf->Cell(95,7,"PUESTO",1,0,'C', 1);
        $pdf->Cell(12,7,"MESA",1, 0, 'C', 1);
        $pdf->Ln();
        $pos=1;

        if(empty($sublider)){
            $sublideres=Persona::getSublideresCodigo($lider);
            foreach($sublideres as $item){
                if($item->municipio =="SINCELEJO"){
                    $sincelejo++;
                }else{
                    $otrasciudades++;
                }
                $pdf->SetFillColor(255,255,255);
                $pdf->SetFont('Arial','B',9);
                $pdf->Cell(10,5, $pos,1);
                $pdf->Cell(23,5, $item->numerodocumento,1);
                $pdf->Cell(100,5,utf8_decode($item->nombrecompleto),1);
                $pdf->Cell(20,5,$item->telefono,1);
                $pdf->Cell(20,5,$item->id,1);
                $pdf->Cell(50,5,$item->municipio,1);
                //$pdf->Cell(60,5,$item->puesto->descripcion,1);
                $pdf->Cell(95,5, utf8_decode( $item->puestovotacion),1);
                //$pdf->Cell(20,5,$item->mesa,1);
                $pdf->Cell(12,5,$item->mesavotacion,1);
                $pdf->Ln();
            }
        }

        foreach($votantes as $item){
            $pdf->SetFillColor(255,255,255);
            if($item->municipio =="SINCELEJO"){
                $sincelejo++;
                $pdf->SetFont('Arial','',9);
            }else{
                $pdf->SetFont('Arial','B',9);
                $otrasciudades++;
            }
            $pdf->Cell(10,5, $pos,1);
            $pdf->Cell(23,5, $item->numerodocumento,1);
            $pdf->Cell(100,5,utf8_decode($item->nombrecompleto),1);
            $pdf->Cell(20,5,$item->telefono,1);
            $pdf->Cell(20,5,$item->id,1);
            $pdf->Cell(50,5,$item->municipio,1);

                //$pdf->Cell(60,5,$item->puesto->descripcion,1);
                $pdf->Cell(95,5, utf8_decode( $item->puestovotacion),1);
            //$pdf->Cell(20,5,$item->mesa,1);
             $pdf->Cell(12,5,$item->mesavotacion,1);
            $pos++;
            $pdf->Ln();
        }
        $pdf->Cell(93,5, utf8_decode( "VOTANTES EN SINCELEJO"),1);
        $pdf->Cell(40,5, utf8_decode( $sincelejo),1);
        $pdf->Cell(90,5, utf8_decode( "VOTANTES EN OTRAS CIUDADES"),1);
        $pdf->Cell(95,5, utf8_decode( $otrasciudades),1);
         $base64String = chunk_split(base64_encode($pdf->Output('S')));
        return response()->json([
            'code'=>200,
            'pdf' => $base64String
        ], Response::HTTP_OK);

    }

    public function votantesTicketByLider(Request $request){

        $data = $request->only('lider', 'sublider', 'ordernar', 'forma');

        $lider=$request->lider;
        $sublider=$request->sublider;

            $infoLider=Persona::getLider($lider);
            $infoSublider=Persona::getDatosSublider($sublider);
            if(!empty($request->ordenar)){
                $votantes=Votante::getByLiderTicket($lider, $sublider);
            }else{
                $votantes=Votante::getByLiderTicket($lider, $sublider);
            }

            $pdf = app('Fpdf',);
        $pdf->AddPage('L', 'Legal');
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(40,10,"NOMBRE LIDER: ".utf8_decode($infoLider->nombrecompleto),0);


        //Fin
        $total=0;
        if(!empty($sublider)){
            //Colaborador
            $pdf->SetXY(165, 10);
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(40,10,"NOMBRE SUBLIDER: ".utf8_decode($infoSublider->nombrecompleto),0);
            $total=count($votantes);
        }else{
            $sublideres=Persona::getSublideresCodigo($lider);
            $total=count($sublideres)+ count($votantes);
        }

           $pdf->SetXY(300, 10);
            $pdf->Cell(40,10,"  TOTAL: ".$total,0);
            $pdf->Ln();



        if(empty($sublider)){
            $i=0;
            foreach($sublideres as $item){
                $i++;
                $pdf->SetFont('Arial','B',28);
                $pdf->Cell(40, 20, $item->id."S", 1, 0, 'C');
                // Agrega un salto de línea si es necesario
                if (($i) % 8 == 0) {
                    $pdf->Ln();
                }
            }
        }
        $pdf->Ln();
        $i=0;
        foreach($votantes as $item){
            $pdf->SetFillColor(255,255,255);
            $i++;
                $pdf->SetFont('Arial','B',28);
                $pdf->Cell(40, 20, $item->id, 1, 0, 'C');
                // Agrega un salto de línea si es necesario
                if (($i) % 8 == 0) {
                    $pdf->Ln();
                }

        }
        $base64String = chunk_split(base64_encode($pdf->Output('S')));
        return response()->json([
            'code'=>200,
            'pdf' => $base64String
        ], Response::HTTP_OK);

    }




}
