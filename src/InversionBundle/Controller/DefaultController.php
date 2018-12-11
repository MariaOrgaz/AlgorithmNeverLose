<?php

namespace InversionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\DateTime;


class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('InversionBundle:Templates:index.html.twig');
    }

    public function santanderAction()
    {
        return $this->render('InversionBundle:Templates:santander.html.twig');
    }

    public function repsolAction()
    {
        return $this->render('InversionBundle:Templates:repsol.html.twig');
    }

    public function telefonicaAction()
    {
        return $this->render('InversionBundle:Templates:telefonica.html.twig');
    }

    public function inditexAction()
    {
        return $this->render('InversionBundle:Templates:inditex.html.twig');
    }

    public function jsonDatosAction()
    {
        $csvFile = file('public/csv/SAN.csv');
        $arrayDJ = [];
        foreach ($csvFile as $key => $line) {
            if ($key > 0) {
                $linea = explode(',', $line);
                $data = [];
                $data['x'] = strtotime($linea[0]);
                //open, high, low, close
                $data['y'] = array($linea[1], $linea[2], $linea[3], $linea[4]);
                $arrayDJ[] = $data;
            }
        }
        $dawJones = json_encode($arrayDJ, JSON_NUMERIC_CHECK);

        return new JsonResponse(array('dawJones' => $dawJones));
    }

    public function ibexAction()
    {
        return $this->render('InversionBundle:Templates:ibex.html.twig');
    }

    public function daxAction()
    {
        return $this->render('InversionBundle:Templates:dax.html.twig');
    }

    public function eurostoxxAction()
    {
        return $this->render('InversionBundle:Templates:eurostoxx.html.twig');
    }

    public function buscarAlgoritmoAction()
    {
        $arrayDatos = $_POST['datos'];
        $data = $_POST['data'];

        $datosProcesados = self::procesarDatos($data);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($datosProcesados as $key => $row) {
            if($datosProcesados[$key]['y'][3] == 'null'){
                unset($datosProcesados[$key]);
            }
        }

        $datosProcesados = array_values($datosProcesados);

        $riesgo = $arrayDatos[0];
        $margen = $arrayDatos[1];
        $periodo = $arrayDatos[2];
        $tipo = $arrayDatos[3];
        $fechaIni = $arrayDatos[4];
        $fechaFin = $arrayDatos[5];
        $capital = $arrayDatos[6];
        $beneficio = $arrayDatos[7];
        $margenInferior = $arrayDatos[8];

        $numCompras = $arrayDatos[9];
        $invCompra = $arrayDatos[10];
        $invInicial = $arrayDatos[11];
        $porcentajeCompra = $arrayDatos[12];

        $compraVenta = [];

        $comprasRsi = [];
        $comprasEstocastico = [];
        $comprasMacd = [];
        $comprasMacd2 = [];
        $comprasRoc = [];
        $comprasWilliams = [];
        $comprasSma = [];
        $comprasAdx = [];

        //$comprasRsi = self::indicadorRsiAction($fechaIni, $fechaFin, $capital, $data);
        //$comprasEstocastico = self::indicadorEstocasticoAction($fechaIni, $fechaFin, $capital, $data);
        //$comprasMacd = self::indicadorMacdAction($fechaIni, $fechaFin, $capital, $data);
        //$comprasMacd2 = self::indicadorMacd2Action($fechaIni, $fechaFin, $capital, $data);
        //$comprasRoc = self::indicadorRocAction($fechaIni, $fechaFin, $capital, $data);
        //$comprasWilliams = self::indicadorWilliamsAction($fechaIni, $fechaFin, $capital, $data);
        //$comprasSma = self::indicadorSmaAction($fechaIni, $fechaFin, $capital, $data);
        //$comprasAdx = self::indicadorAdxAction($fechaIni, $fechaFin, $capital, $data);

        //tenemos que sacar intervalos cuyo máximo sea el mismo
        if ($tipo == "días") {
            $arrayMaximos = [];

            foreach ($datosProcesados as $key => $dia) {
                if (strtotime($dia['x']) > strtotime($fechaFin)) {
                    break;
                }
                if (strtotime($dia['x']) >= strtotime($fechaIni)) {
                    $max = 0;
                    $min = 10000;
                    $date = new \DateTime($dia['x']);
                    $date->sub(new \DateInterval('P' . $periodo . 'M'));
                    $comienzo = $date->format('Y-m-d');

                    foreach ($datosProcesados as $key2 => $previo) {
                        //recorro los días hasta situarme en la fecha de comienzo
                        if ((strtotime($previo['x']) >= strtotime($comienzo)) && (strtotime($previo['x']) <= strtotime($dia['x']))) {
                            if ($previo['y'][1] > $max) {
                                $max = $previo['y'][1];
                            }

                            if ($previo['y'][2] < $min) {
                                $min = $previo['y'][2];
                            }
                        }
                        if (strtotime($previo['x']) > strtotime($dia['x'])) {
                            break;
                        }
                    }

                    // fecha, maximo ultimos x dias/meses, valor cierre, minimo ultimos x dias/meses
                    $arrayMaximos[] = [$dia['x'], $max, $dia['y'][3], $min, $dia['y'][2], $dia['y'][1]];
                }
            }
        } else {
            $arrayMaximos = [];

            //fechaInicio es la fecha en la que se empiezan a buscar los max y min, es decir X meses atras
            //date es la fecha en la que comienzan las compras, la introducida en el formulario
            foreach ($datosProcesados as $key => $dia) {
                if (strtotime($dia['x']) > strtotime($fechaFin)) {
                    break;
                }

                if (strtotime($dia['x']) >= strtotime($fechaIni)) {
                    $max = 0;
                    $min = 10000;
                    $date = new \DateTime($dia['x']);
                    $date->sub(new \DateInterval('P' . $periodo . 'M'));
                    $comienzo = $date->format('Y-m-d');

                    foreach ($datosProcesados as $key2 => $previo) {
                        //recorro los días hasta situarme en la fecha de comienzo
                        if ((strtotime($previo['x']) >= strtotime($comienzo)) && (strtotime($previo['x']) <= strtotime($dia['x']))) {
                            if ($previo['y'][1] > $max) {
                                $max = $previo['y'][1];
                            }

                            if ($previo['y'][2] < $min) {
                                $min = $previo['y'][2];
                            }
                        }
                        if (strtotime($previo['x']) > strtotime($dia['x'])) {
                            break;
                        }
                    }

                    // fecha, maximo ultimos x meses, valor cierre, minimo ultimos x meses, low, high
                    $arrayMaximos[] = [$dia['x'], $max, $dia['y'][3], $min, $dia['y'][2], $dia['y'][1]];
                }
            }
        }

        //saco un array con los periodos que comparten el mismo máximo
        $arrayFinal = [];
        foreach ($arrayMaximos as $key => $maximo) {
            if ($key == 0) {
                $array['diaInicio'] = $maximo[0];
                $array['maximo'] = $maximo[1];
            } else {
                if ($maximo[1] != $arrayMaximos[$key - 1][1]) {
                    $array['diaFin'] = $arrayMaximos[$key - 1][0];
                    $arrayFinal[] = $array;

                    $array['diaInicio'] = $maximo[0];
                    $array['maximo'] = $maximo[1];
                }
            }
            if ($key == (count($arrayMaximos) - 1)) {
                $array['diaFin'] = $maximo[0];
                $arrayFinal[] = $array;
            }
        }

        //saco un array con los periodos que comparten el mismo mínimo
        $arrayFinalMinimos = array();
        $array = array();
        foreach ($arrayMaximos as $key => $maximo) {
            if ($key == 0) {
                $array['diaInicio'] = $maximo[0];
                $array['minimo'] = $maximo[3];
            } else {
                if ($maximo[3] != $arrayMaximos[$key - 1][3]) {
                    $array['diaFin'] = $arrayMaximos[$key - 1][0];
                    $arrayFinalMinimos[] = $array;

                    $array['diaInicio'] = $maximo[0];
                    $array['minimo'] = $maximo[3];
                }
            }
            if ($key == (count($arrayMaximos) - 1)) {
                $array['diaFin'] = $maximo[0];
                $arrayFinalMinimos[] = $array;
            }
        }

        $compra = false;
        $subcompras = false;
        $venderPerdidas = false;
        $contCompras = 0;
        $capitalRestante = $capital;

        //Aquí están todos los días de los rangos seleccionados
        foreach ($arrayMaximos as $key => $maximo) {
            //por cada día compruebo si puedo comprar, es decir si hay una distancia > margen+beneficio hasta el máximo
            // y una distancia < hasta el mínimo
            if (!$compra) {
                //acciones por tanda de compra y dinero gastado
                $acciones = 0;
                $gastado = 0;
                $objetivo = 0;
                $topePerdidas = 0;
                $topePerdidasCompra = 0;

                //usando % beneficio sobre inversion realizada
                $inversionFicticia = ($capitalRestante * $invInicial) / 100;
                $accionesFicticias = intval($inversionFicticia / $maximo[4]);
                $inversionReal = $accionesFicticias * $maximo[4];
                $beneficioEsperado = ($beneficio * $inversionReal) / 100;
                $gananciaEsperada = $inversionReal + $beneficioEsperado;

                if ($accionesFicticias == 0) {
                    $precioVentaMinimo = 0;
                } else {
                    $precioVentaMinimo = $gananciaEsperada / $accionesFicticias;
                }

                //ahora calculo la distancia mínima entre la realización de la venta y el máximo de los últimos x meses/dias
                //si el margen es del 2% haremos 2*precioVentaMinimo/100
                $margenSeguridadSuperior = ($margen * $precioVentaMinimo) / 100;

                //necesitamos que precioVentaMinimo+margenSeguridadSuperior sea menor que el máximo de los últimos x meses/dias
                foreach ($arrayFinal as $d) {
                    if ($maximo[0] >= $d['diaInicio'] && $maximo[0] <= $d['diaFin']) {
                        $topeSuperior = $d['maximo'];
                        break;
                    }
                }

                //deberia hacerse con el precio minimo no con el de cierre $maximo[3]
                $margenSeguridadinferior = ($margenInferior * $maximo[4]) / 100; //2% 0.212
                //necesitamos que la distancia entre el precio de compra y el mínimo de los últimos x meses/dias
                //sea menor que el margen de seguridad inferior
                foreach ($arrayFinalMinimos as $d) {
                    if ($maximo[0] >= $d['diaInicio'] && $maximo[0] <= $d['diaFin']) {
                        $topeinferior = $d['minimo'];
                        break;
                    }
                }
            }

            //condición de compra
            if ((($precioVentaMinimo + $margenSeguridadSuperior) <= $topeSuperior) && ((($maximo[4] - $margenSeguridadinferior) <= $topeinferior) || $maximo[4] <= $topeinferior) && ($compra == false)) {
                //if ((($precioVentaMinimo + $margenSeguridadSuperior) <= $topeSuperior) && ($compra == false)) {

                $compra = true;
                $inversion = ($capitalRestante * $invInicial) / 100;
                $inicio = $maximo[0];

                $arrayCompra ['inicio'] = $inicio;
                $arrayCompra ['precioCompra'] = round($maximo[4], 2, PHP_ROUND_HALF_UP);
                $arrayCompra ['acciones'] = intval($inversion / $maximo[4]);
                $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];

                $acciones += $arrayCompra ['acciones'];
                $gastado += ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                $capitalRestante -= ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);

                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
            }

            //no vendo el mismo dia pues estoy usando el precio de cierre como referencia
            if ($compra && $arrayCompra['inicio'] != $maximo[0]) {
                if ($subcompras) {
                    //Primero compruebo si puedo vender al precio objetivo
                    if ($maximo[4] <= $objetivo && $maximo[5] >= $objetivo) {
                        $arrayCompra['fin'] = $maximo[0];
                        $arrayCompra['precioVenta'] = 'fin subcompras ' . round($objetivo, 2, PHP_ROUND_HALF_UP);
                        $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                        $ganado = $objetivo * $arrayCompra['acciones'];
                        $ganancia = $ganado - $gastado;
                        $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                        $capitalRestante += ($acciones * $objetivo);
                        $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                        $compraVenta[] = $arrayCompra;
                        $compra = false;
                        $venderPerdidas = false;
                        $subcompras = false;
                        $contCompras = 0;
                    } else {
                        //Compruebo si el precio ha bajado de nuevo el % indicado en las compras
                        if ($maximo[4] <= $topePerdidasCompra) {
                            //continuo las subcompras
                            //Primero compruebo que tengo dinero para seguir comprando al menos 1 accion
                            if ($capitalRestante >= $topePerdidasCompra) {
                                $contCompras++;
                                $arrayCompra['precioVenta'] = 'colgada (inicio subcompra ' . $contCompras . ')';
                                $arrayCompra['fin'] = $maximo[0];
                                $arrayCompra['ganancia'] = '-------';
                                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;
                                //Compruebo si es la ultima compra, en cuyo caso usare el 100% del dinero
                                if ($contCompras == $numCompras) {
                                    $arrayCompra ['inicio'] = $maximo[0];
                                    $arrayCompra ['precioCompra'] = round($topePerdidasCompra, 2, PHP_ROUND_HALF_UP);
                                    $arrayCompra ['acciones'] = intval(($capitalRestante / $arrayCompra ['precioCompra']));
                                    $capitalRestante -= ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                                    $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];
                                    $acciones += $arrayCompra['acciones'];
                                    $gastado += ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                                    $objetivo = $gastado / $acciones;
                                } else {
                                    $inversionSubcompra = ($capitalRestante * $invCompra) / 100;
                                    $arrayCompra ['inicio'] = $maximo[0];
                                    $arrayCompra ['precioCompra'] = round($topePerdidasCompra, 2, PHP_ROUND_HALF_UP);
                                    $arrayCompra['acciones'] = intval($inversionSubcompra / $topePerdidasCompra);
                                    $capitalRestante -= ($arrayCompra ['acciones'] * $topePerdidasCompra);
                                    $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];
                                    $acciones += $arrayCompra['acciones'];
                                    $gastado += ($arrayCompra ['acciones'] * $arrayCompra ['precioCompra']);
                                    $objetivo = $gastado / $acciones;

                                }

                                $perdidaSubcompra = $porcentajeCompra * $topePerdidasCompra / 100;
                                $topePerdidasCompra = $topePerdidasCompra - $perdidaSubcompra;
                            }
                        }

                    }
                } else {
                    if ($venderPerdidas) {
                        if ($arrayCompra['precioCompra'] >= $maximo[4] && $arrayCompra['precioCompra'] <= $maximo[5]) {
                            $arrayCompra['fin'] = $maximo[0];
                            $arrayCompra['precioVenta'] = 'deshecha ' . $arrayCompra['precioCompra'];
                            $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                            $ganado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                            $ganancia = $ganado - $gastado;
                            $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                            $compraVenta[] = $arrayCompra;
                            $compra = false;
                            $venderPerdidas = false;
                            $subcompras = false;
                            $capitalRestante += ($arrayCompra ['acciones'] * $arrayCompra['precioCompra']);
                            $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                        } elseif ($arrayCompra['precioCompra'] <= $maximo[4]) {
                            $arrayCompra['fin'] = $maximo[0];
                            $arrayCompra['precioVenta'] = 'deshecha ' . $maximo[4];
                            $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                            $ganado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                            $ganancia = $ganado - $gastado;
                            $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                            $compraVenta[] = $arrayCompra;
                            $compra = false;
                            $venderPerdidas = false;
                            $subcompras = false;
                            $capitalRestante += ($arrayCompra ['acciones'] * $arrayCompra['precioCompra']);
                            $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                        } else {
                            //compruebo si sigue bajando el precio y sobrepaso el % indicado para hacer una subcompra
                            $perdidaSubcompra = $porcentajeCompra * $topePerdidas / 100;
                            $topePerdidasCompra = $topePerdidas - $perdidaSubcompra;

                            if ($maximo[4] <= $topePerdidasCompra) {
                                //comienzo las subcompras
                                $subcompras = true;
                                $venderPerdidas = false;
                                $contCompras++;
                                $arrayCompra['precioVenta'] = 'colgada (inicio Subcompra ' . $contCompras . ')';
                                $arrayCompra['fin'] = $maximo[0];
                                $arrayCompra['ganancia'] = '-------';
                                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;

                                if ($numCompras == 1) {
                                    $inversion = $capitalRestante;
                                } else {
                                    $inversion = ($capitalRestante * $invCompra) / 100;
                                }

                                $arrayCompra ['precioCompra'] = round($topePerdidasCompra, 2, PHP_ROUND_HALF_UP);
                                $arrayCompra['acciones'] = intval($inversion / $arrayCompra ['precioCompra']);
                                $arrayCompra['inicio'] = $maximo[0];
                                $capitalRestante -= ($arrayCompra ['acciones'] * $arrayCompra ['precioCompra']);
                                $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];
                                $acciones += $arrayCompra['acciones'];
                                $gastado += ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                                $objetivo = $gastado / $acciones;
                            }

                            //Compruebo si el precio ha bajado de nuevo el % indicado en las compras
                            $perdidaSubcompra = $porcentajeCompra * $topePerdidasCompra / 100;
                            $topePerdidasCompra = $topePerdidasCompra - $perdidaSubcompra;
                        }
                    } else {
                        //busco el momento de vender por  ganancias
                        //compruebo que el precio de venta esté entre el minimo y maximo del día
                        if ($precioVentaMinimo >= $maximo[4] && $precioVentaMinimo <= $maximo[5]) {
                            $fin = $maximo[0];
                            $arrayCompra['fin'] = $fin;
                            $arrayCompra['precioVenta'] = $precioVentaMinimo;
                            $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                            $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                            $ganancia = $ganado - $gastado;
                            $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                            $capitalRestante += ($arrayCompra ['acciones'] * $arrayCompra['precioVenta']);
                            $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                            $compraVenta[] = $arrayCompra;
                            $compra = false;
                        } elseif ($precioVentaMinimo <= $maximo[4]) {
                            //puede que el precio de un salto y se pase el valor al que queríamos vender
                            //el precio de venta no va a ser el que fijamos porque se lo ha saltado pero sí el mínimo del día
                            $fin = $maximo[0];
                            $arrayCompra['fin'] = $fin;
                            $arrayCompra['precioVenta'] = $maximo[4];
                            $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                            $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                            $ganancia = $ganado - $gastado;
                            $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                            $capitalRestante += ($arrayCompra ['acciones'] * $arrayCompra['precioVenta']);
                            $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                            $compraVenta[] = $arrayCompra;
                            $compra = false;
                        }

                        //busco el momento de vender por pérdidas y si no lo consigo realizo subcompra
                        $perdida = $riesgo * $arrayCompra['precioCompra'] / 100;
                        $topePerdidas = $arrayCompra['precioCompra'] - $perdida;

                        if ($maximo[5] <= $topePerdidas) {
                            $venderPerdidas = true;
                            $objetivo = $arrayCompra['precioCompra'];
                        }
                    }
                }
            }
        }

        //compruebo si tengo alguna compra abierta al finalizar el bucle
        if ($compra) {
            $arrayCompra['fin'] = 'pendiente';
            $arrayCompra['precioVenta'] = 'colgada';
            $arrayCompra['ganancia'] = '-------';
            $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
            $compraVenta[] = $arrayCompra;
        }

        return new JsonResponse(array("compras" => $compraVenta, "comprasRsi" => $comprasRsi, "comprasMacd" => $comprasMacd, "comprasMacd2" => $comprasMacd2, "comprasEstocastico" => $comprasEstocastico, "comprasRoc" => $comprasRoc, "comprasWilliams" => $comprasWilliams, "comprasSma" => $comprasSma, "comprasAdx" => $comprasAdx, "intervalos" => $arrayFinal, "intervalos2" => $arrayFinalMinimos, "maximos" => $arrayMaximos, "resultado" => true));
    }

    //$parámetro va a ser un string que se llamara igual que las variables que tengo definidas para facilitar el codigo
    //riesgo, mss, msi, beneficio, invInicial, invCompra, numCompras
    public function mejoraParametrosAction()
    {
        $arrayDatos = $_POST['datos'];
        $data = $_POST['data'];

        $datosProcesados = self::procesarDatos($data);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($datosProcesados as $key => $row) {
            if($datosProcesados[$key]['y'][3] == 'null'){
                unset($datosProcesados[$key]);
            }
        }

        $datosProcesados = array_values($datosProcesados);

        $riesgo = $arrayDatos[0];
        $margen = $arrayDatos[1];
        $periodo = $arrayDatos[2];
        $tipo = $arrayDatos[3];
        $fechaIni = $arrayDatos[4];
        $fechaFin = $arrayDatos[5];
        $capital = $arrayDatos[6];
        $beneficio = $arrayDatos[7];
        $margenInferior = $arrayDatos[8];

        $numCompras = $arrayDatos[9];
        $invCompra = $arrayDatos[10];
        $invInicial = $arrayDatos[11];
        $porcentajeCompra = $arrayDatos[12];
        $parametro = $arrayDatos[13];

        //Como resultados necesitaremos: ganancia obtenida, numCompras
        $resultadosFinal = [];
        $resultados = [];


        for ($m = 1; $m <= 10; $m++) {
            ${$parametro}= $m;

            //Y ejecuto el código 10 veces para obtener los resultados que necesitamos
            $compraVenta = [];

            //tenemos que sacar intervalos cuyo máximo sea el mismo
            if ($tipo == "días") {
                $arrayMaximos = [];

                foreach ($datosProcesados as $key => $dia) {
                    if (strtotime($dia['x']) > strtotime($fechaFin)) {
                        break;
                    }
                    if (strtotime($dia['x']) >= strtotime($fechaIni)) {
                        $max = 0;
                        $min = 10000;
                        $date = new \DateTime($dia['x']);
                        $date->sub(new \DateInterval('P' . $periodo . 'M'));
                        $comienzo = $date->format('Y-m-d');

                        foreach ($datosProcesados as $key2 => $previo) {
                            //recorro los días hasta situarme en la fecha de comienzo
                            if ((strtotime($previo['x']) >= strtotime($comienzo)) && (strtotime($previo['x']) <= strtotime($dia['x']))) {
                                if ($previo['y'][1] > $max) {
                                    $max = $previo['y'][1];
                                }

                                if ($previo['y'][2] < $min) {
                                    $min = $previo['y'][2];
                                }
                            }
                            if (strtotime($previo['x']) > strtotime($dia['x'])) {
                                break;
                            }
                        }

                        // fecha, maximo ultimos x dias/meses, valor cierre, minimo ultimos x dias/meses
                        $arrayMaximos[] = [$dia['x'], $max, $dia['y'][3], $min];
                    }
                }
            } else {
                $arrayMaximos = [];

                //fechaInicio es la fecha en la que se empiezan a buscar los max y min, es decir X meses atras
                //date es la fecha en la que comienzan las compras, la introducida en el formulario
                foreach ($datosProcesados as $key => $dia) {
                    if (strtotime($dia['x']) > strtotime($fechaFin)) {
                        break;
                    }

                    if (strtotime($dia['x']) >= strtotime($fechaIni)) {
                        $max = 0;
                        $min = 10000;
                        $date = new \DateTime($dia['x']);
                        $date->sub(new \DateInterval('P' . $periodo . 'M'));
                        $comienzo = $date->format('Y-m-d');

                        foreach ($datosProcesados as $key2 => $previo) {
                            //recorro los días hasta situarme en la fecha de comienzo
                            if ((strtotime($previo['x']) >= strtotime($comienzo)) && (strtotime($previo['x']) <= strtotime($dia['x']))) {
                                if ($previo['y'][1] > $max) {
                                    $max = $previo['y'][1];
                                }

                                if ($previo['y'][2] < $min) {
                                    $min = $previo['y'][2];
                                }
                            }
                            if (strtotime($previo['x']) > strtotime($dia['x'])) {
                                break;
                            }
                        }

                        // fecha, maximo ultimos x dias/meses, valor cierre, minimo ultimos x dias/meses, low, high
                        $arrayMaximos[] = [$dia['x'], $max, $dia['y'][3], $min, $dia['y'][2], $dia['y'][1]];
                    }
                }
            }

            //saco un array con los periodos que comparten el mismo máximo
            $arrayFinal = [];
            foreach ($arrayMaximos as $key => $maximo) {
                if ($key == 0) {
                    $array['diaInicio'] = $maximo[0];
                    $array['maximo'] = $maximo[1];
                } else {
                    if ($maximo[1] != $arrayMaximos[$key - 1][1]) {
                        $array['diaFin'] = $arrayMaximos[$key - 1][0];
                        $arrayFinal[] = $array;

                        $array['diaInicio'] = $maximo[0];
                        $array['maximo'] = $maximo[1];
                    }
                }
                if ($key == (count($arrayMaximos) - 1)) {
                    $array['diaFin'] = $maximo[0];
                    $arrayFinal[] = $array;
                }
            }

            //saco un array con los periodos que comparten el mismo mínimo
            $arrayFinalMinimos = array();
            $array = array();
            foreach ($arrayMaximos as $key => $maximo) {
                if ($key == 0) {
                    $array['diaInicio'] = $maximo[0];
                    $array['minimo'] = $maximo[3];
                } else {
                    if ($maximo[3] != $arrayMaximos[$key - 1][3]) {
                        $array['diaFin'] = $arrayMaximos[$key - 1][0];
                        $arrayFinalMinimos[] = $array;

                        $array['diaInicio'] = $maximo[0];
                        $array['minimo'] = $maximo[3];
                    }
                }
                if ($key == (count($arrayMaximos) - 1)) {
                    $array['diaFin'] = $maximo[0];
                    $arrayFinalMinimos[] = $array;
                }
            }

            $compra = false;
            $subcompras = false;
            $venderPerdidas = false;
            $contCompras = 0;
            $capitalRestante = $capital;

            //Aquí están todos los días de los rangos seleccionados
            foreach ($arrayMaximos as $key => $maximo) {
                //por cada día compruebo si puedo comprar, es decir si hay una distancia > margen+beneficio hasta el máximo
                // y una distancia < hasta el mínimo
                if (!$compra) {
                    //acciones por tanda de compra y dinero gastado
                    $acciones = 0;
                    $gastado = 0;
                    $objetivo = 0;
                    $topePerdidas = 0;
                    $topePerdidasCompra = 0;

                    //usando % beneficio sobre inversion realizada
                    $inversionFicticia = ($capitalRestante * $invInicial) / 100; // 50000
                    $accionesFicticias = intval($inversionFicticia / $maximo[4]); // 9920 acciones
                    $inversionReal = $accionesFicticias * $maximo[4]; // 49996,8
                    $beneficioEsperado = ($beneficio * $inversionReal) / 100; //2499.84 euros
                    $gananciaEsperada = $inversionReal + $beneficioEsperado; // 52496,64 euros

                    if ($accionesFicticias == 0) {
                        $precioVentaMinimo = 0;
                    } else {
                        $precioVentaMinimo = $gananciaEsperada / $accionesFicticias; //5,292 euros
                    }

                    //ahora calculo la distancia mínima entre la realización de la venta y el máximo de los últimos x meses/dias
                    //si el margen es del 2% haremos 2*precioVentaMinimo/100
                    $margenSeguridadSuperior = ($margen * $precioVentaMinimo) / 100; //2% 0.212

                    //necesitamos que precioVentaMinimo+margenSeguridadSuperior sea menor que el máximo de los últimos x meses/dias
                    foreach ($arrayFinal as $d) {
                        if ($maximo[0] >= $d['diaInicio'] && $maximo[0] <= $d['diaFin']) {
                            $topeSuperior = $d['maximo'];
                            break;
                        }
                    }

                    $margenSeguridadinferior = ($margenInferior * $maximo[4]) / 100; //2% 0.212
                    //necesitamos que la distancia entre el precio de compra y el mínimo de los últimos x meses/dias
                    //sea menor que el margen de seguridad inferior
                    foreach ($arrayFinalMinimos as $d) {
                        if ($maximo[0] >= $d['diaInicio'] && $maximo[0] <= $d['diaFin']) {
                            $topeinferior = $d['minimo'];
                            break;
                        }
                    }
                }

                //condición de compra
                if ((($precioVentaMinimo + $margenSeguridadSuperior) <= $topeSuperior) && ((($maximo[4] - $margenSeguridadinferior) <= $topeinferior) || $maximo[4] <= $topeinferior) && ($compra == false)) {
                    //if ((($precioVentaMinimo + $margenSeguridadSuperior) <= $topeSuperior) && ($compra == false)) {
                    $compra = true;
                    $inversion = ($capitalRestante * $invInicial) / 100;
                    $inicio = $maximo[0];

                    $arrayCompra ['inicio'] = $inicio;
                    $arrayCompra ['precioCompra'] = round($maximo[4], 2, PHP_ROUND_HALF_UP);
                    $arrayCompra ['acciones'] = intval($inversion / $maximo[4]);
                    $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];

                    $acciones += $arrayCompra ['acciones'];
                    $gastado += ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                    $capitalRestante -= ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);

                    $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                }

                //no vendo el mismo dia pues estoy usando el precio de cierre como referencia
                if ($compra && $arrayCompra['inicio'] != $maximo[0]) {
                    if ($subcompras) {
                        //Primero compruebo si puedo vender al precio objetivo
                        if ($maximo[4] <= $objetivo && $maximo[5] >= $objetivo) {
                            $arrayCompra['fin'] = $maximo[0];
                            $arrayCompra['precioVenta'] = 'fin subcompras ' . round($objetivo, 2, PHP_ROUND_HALF_UP);
                            $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                            $ganado = $objetivo * $arrayCompra['acciones'];
                            $ganancia = $ganado - $gastado;
                            $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                            $capitalRestante += ($acciones * $objetivo);
                            $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                            $compraVenta[] = $arrayCompra;
                            $compra = false;
                            $venderPerdidas = false;
                            $subcompras = false;
                            $contCompras = 0;
                        } else {
                            //Compruebo si el precio ha bajado de nuevo el % indicado en las compras
                            if ($maximo[4] <= $topePerdidasCompra) {
                                //continuo las subcompras
                                //Primero compruebo que tengo dinero para seguir comprando al menos 1 accion
                                if ($capitalRestante >= $topePerdidasCompra) {
                                    $contCompras++;
                                    $arrayCompra['precioVenta'] = 'colgada (inicio subcompra ' . $contCompras . ')';
                                    $arrayCompra['fin'] = $maximo[0];
                                    $arrayCompra['ganancia'] = '-------';
                                    $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                    $compraVenta[] = $arrayCompra;
                                    //Compruebo si es la ultima compra, en cuyo caso usare el 100% del dinero
                                    if ($contCompras == $numCompras) {
                                        $arrayCompra ['inicio'] = $maximo[0];
                                        $arrayCompra ['precioCompra'] = round($topePerdidasCompra, 2, PHP_ROUND_HALF_UP);
                                        $arrayCompra ['acciones'] = intval(($capitalRestante / $arrayCompra ['precioCompra']));
                                        $capitalRestante -= ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                                        $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];
                                        $acciones += $arrayCompra['acciones'];
                                        $gastado += ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                                        $objetivo = $gastado / $acciones;
                                    } else {
                                        $inversionSubcompra = ($capitalRestante * $invCompra) / 100;
                                        $arrayCompra ['inicio'] = $maximo[0];
                                        $arrayCompra ['precioCompra'] = round($topePerdidasCompra, 2, PHP_ROUND_HALF_UP);
                                        $arrayCompra['acciones'] = intval($inversionSubcompra / $topePerdidasCompra);
                                        $capitalRestante -= ($arrayCompra ['acciones'] * $topePerdidasCompra);
                                        $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];
                                        $acciones += $arrayCompra['acciones'];
                                        $gastado += ($arrayCompra ['acciones'] * $arrayCompra ['precioCompra']);
                                        $objetivo = $gastado / $acciones;

                                    }

                                    $perdidaSubcompra = $porcentajeCompra * $topePerdidasCompra / 100;
                                    $topePerdidasCompra = $topePerdidasCompra - $perdidaSubcompra;
                                }
                            }

                        }
                    } else {
                        if ($venderPerdidas) {
                            if ($arrayCompra['precioCompra'] >= $maximo[4] && $arrayCompra['precioCompra'] <= $maximo[5]) {
                                $arrayCompra['fin'] = $maximo[0];
                                $arrayCompra['precioVenta'] = 'deshecha ' . $arrayCompra['precioCompra'];
                                $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganancia = $ganado - $gastado;
                                $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;
                                $compra = false;
                                $venderPerdidas = false;
                                $subcompras = false;
                                $capitalRestante += ($arrayCompra ['acciones'] * $maximo[2]);
                                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                            } elseif ($arrayCompra['precioCompra'] <= $maximo[4]) {
                                $arrayCompra['fin'] = $maximo[0];
                                $arrayCompra['precioVenta'] = 'deshecha ' . $maximo[4];
                                $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganado = $maximo[4] * $arrayCompra['acciones'];
                                $ganancia = $ganado - $gastado;
                                $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;
                                $compra = false;
                                $venderPerdidas = false;
                                $subcompras = false;
                                $capitalRestante += ($arrayCompra ['acciones'] * $maximo[2]);
                                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                            } else {
                                //compruebo si sigue bajando el precio y sobrepaso el % indicado para hacer una subcompra
                                $perdidaSubcompra = $porcentajeCompra * $topePerdidas / 100;
                                $topePerdidasCompra = $topePerdidas - $perdidaSubcompra;

                                if ($maximo[4] <= $topePerdidasCompra) {
                                    //comienzo las subcompras
                                    $subcompras = true;
                                    $venderPerdidas = false;
                                    $contCompras++;
                                    $arrayCompra['precioVenta'] = 'colgada (inicio Subcompra ' . $contCompras . ')';
                                    $arrayCompra['fin'] = $maximo[0];
                                    $arrayCompra['ganancia'] = '-------';
                                    $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                    $compraVenta[] = $arrayCompra;

                                    if ($numCompras == 1) {
                                        $inversion = $capitalRestante;
                                    } else {
                                        $inversion = ($capitalRestante * $invCompra) / 100;
                                    }

                                    $arrayCompra ['precioCompra'] = round($topePerdidasCompra, 2, PHP_ROUND_HALF_UP);
                                    $arrayCompra['acciones'] = intval($inversion / $arrayCompra ['precioCompra']);
                                    $arrayCompra['inicio'] = $maximo[0];
                                    $capitalRestante -= ($arrayCompra ['acciones'] * $arrayCompra ['precioCompra']);
                                    $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];
                                    $acciones += $arrayCompra['acciones'];
                                    $gastado += ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                                    $objetivo = $gastado / $acciones;
                                }

                                //Compruebo si el precio ha bajado de nuevo el % indicado en las compras
                                $perdidaSubcompra = $porcentajeCompra * $topePerdidasCompra / 100;
                                $topePerdidasCompra = $topePerdidasCompra - $perdidaSubcompra;
                            }
                        } else {
                            //busco el momento de vender por  ganancias
                            //compruebo que el precio de venta esté entre el minimo y maximo del día
                            if ($precioVentaMinimo >= $maximo[4] && $precioVentaMinimo <= $maximo[5]) {
                                $fin = $maximo[0];
                                $arrayCompra['fin'] = $fin;
                                $arrayCompra['precioVenta'] = $precioVentaMinimo;
                                $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                                $ganancia = $ganado - $gastado;
                                $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                                $capitalRestante += ($arrayCompra ['acciones'] * $arrayCompra['precioVenta']);
                                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;
                                $compra = false;
                            } elseif ($precioVentaMinimo <= $maximo[4]) {
                                //puede que el precio de un salto y se pase el valor al que queríamos vender
                                //el precio de venta no va a ser el que fijamos porque se lo ha saltado pero sí el mínimo del día
                                $fin = $maximo[0];
                                $arrayCompra['fin'] = $fin;
                                $arrayCompra['precioVenta'] = $maximo[4];
                                $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                                $ganancia = $ganado - $gastado;
                                $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                                $capitalRestante += ($arrayCompra ['acciones'] * $arrayCompra['precioVenta']);
                                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;
                                $compra = false;
                            }

                            //busco el momento de vender por pérdidas y si no lo consigo realizo subcompra
                            $perdida = $riesgo * $arrayCompra['precioCompra'] / 100;
                            $topePerdidas = $arrayCompra['precioCompra'] - $perdida;

                            if ($maximo[5] <= $topePerdidas) {
                                $venderPerdidas = true;
                                $objetivo = $arrayCompra['precioCompra'];
                            }
                        }
                    }
                }
            }

            //compruebo si tengo alguna compra abierta al finalizar el bucle
            if ($compra) {
                $arrayCompra['fin'] = 'pendiente';
                $arrayCompra['precioVenta'] = 'colgada';
                $arrayCompra['ganancia'] = '-------';
                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                $compraVenta[] = $arrayCompra;
            }

            $ganancia = 0;
            $numCom = count($compraVenta);
            foreach ($compraVenta as $compra) {
                if ($compra['ganancia'] != '-------') {
                    $ganancia += $compra['ganancia'];
                }
            }
            $array = [];
            $array['valorParam'] = $m;
            $array['ganancia'] = $ganancia;
            $array['numCompras'] = $numCom;

            $resultados[] = $array;
        }

        //$array_x = [];
        //$array_y = [];
        //$array_xy = [];

        chdir('tablasResultados');
        $numAle = rand(1,1000);

        $fp = fopen('fichero' . $arrayDatos[13] . $periodo .'.csv', 'a+');
        foreach ($resultados as $key => $res) {
            //$array_xy [] =[$res['valorParam'],$res['ganancia']];
            //$array_x [] = $res['valorParam'];
            //$array_y [] = $res['ganancia'];
            fputcsv($fp, [$res['valorParam'], $res['ganancia'] , $res['numCompras']]);
        }
        fclose($fp);

        //$regresion = self::linear_regression($array_x, $array_y);

        return new JsonResponse(array("resParam" => $resultadosFinal, "resultado" => true));
    }

    public function multilinealAction()
    {
        $arrayDatos = $_POST['datos'];
        $data = $_POST['data'];

        $datosProcesados = self::procesarDatos($data);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($datosProcesados as $key => $row) {
            if($datosProcesados[$key]['y'][3] == 'null'){
                unset($datosProcesados[$key]);
            }
        }

        $datosProcesados = array_values($datosProcesados);

        $periodo = $arrayDatos[2];
        $tipo = $arrayDatos[3];
        $fechaIni = $arrayDatos[4];
        $fechaFin = $arrayDatos[5];
        $capital = $arrayDatos[6];
        $numCompras = $arrayDatos[9];
        $invCompra = $arrayDatos[10];
        $invInicial = $arrayDatos[11];

        //Como resultados necesitaremos: ganancia obtenida, numCompras
        $resultadosFinal = [];
        $resultados = [];

        //genero 15 casos
        for ($m = 1; $m <= 15; $m++) {
            $riesgo = rand ( 1 , 10 );
            $margen = rand ( 1 , 10 );
            $beneficio = rand ( 1 , 10 );
            $margenInferior = rand ( 1 , 10 );
            $porcentajeCompra = rand ( 1 , 10 );

            //Y ejecuto el código 10 veces para obtener los resultados que necesitamos
            $compraVenta = [];

            //tenemos que sacar intervalos cuyo máximo sea el mismo
            if ($tipo == "días") {
                $arrayMaximos = [];

                foreach ($datosProcesados as $key => $dia) {
                    if (strtotime($dia['x']) > strtotime($fechaFin)) {
                        break;
                    }
                    if (strtotime($dia['x']) >= strtotime($fechaIni)) {
                        $max = 0;
                        $min = 10000;
                        $date = new \DateTime($dia['x']);
                        $date->sub(new \DateInterval('P' . $periodo . 'M'));
                        $comienzo = $date->format('Y-m-d');

                        foreach ($datosProcesados as $key2 => $previo) {
                            //recorro los días hasta situarme en la fecha de comienzo
                            if ((strtotime($previo['x']) >= strtotime($comienzo)) && (strtotime($previo['x']) <= strtotime($dia['x']))) {
                                if ($previo['y'][1] > $max) {
                                    $max = $previo['y'][1];
                                }

                                if ($previo['y'][2] < $min) {
                                    $min = $previo['y'][2];
                                }
                            }
                            if (strtotime($previo['x']) > strtotime($dia['x'])) {
                                break;
                            }
                        }

                        // fecha, maximo ultimos x dias/meses, valor cierre, minimo ultimos x dias/meses
                        $arrayMaximos[] = [$dia['x'], $max, $dia['y'][3], $min];
                    }
                }
            } else {
                $arrayMaximos = [];

                //fechaInicio es la fecha en la que se empiezan a buscar los max y min, es decir X meses atras
                //date es la fecha en la que comienzan las compras, la introducida en el formulario
                foreach ($datosProcesados as $key => $dia) {
                    if (strtotime($dia['x']) > strtotime($fechaFin)) {
                        break;
                    }

                    if (strtotime($dia['x']) >= strtotime($fechaIni)) {
                        $max = 0;
                        $min = 10000;
                        $date = new \DateTime($dia['x']);
                        $date->sub(new \DateInterval('P' . $periodo . 'M'));
                        $comienzo = $date->format('Y-m-d');

                        foreach ($datosProcesados as $key2 => $previo) {
                            //recorro los días hasta situarme en la fecha de comienzo
                            if ((strtotime($previo['x']) >= strtotime($comienzo)) && (strtotime($previo['x']) <= strtotime($dia['x']))) {
                                if ($previo['y'][1] > $max) {
                                    $max = $previo['y'][1];
                                }

                                if ($previo['y'][2] < $min) {
                                    $min = $previo['y'][2];
                                }
                            }
                            if (strtotime($previo['x']) > strtotime($dia['x'])) {
                                break;
                            }
                        }

                        // fecha, maximo ultimos x dias/meses, valor cierre, minimo ultimos x dias/meses, low, high
                        $arrayMaximos[] = [$dia['x'], $max, $dia['y'][3], $min, $dia['y'][2], $dia['y'][1]];
                    }
                }
            }

            //saco un array con los periodos que comparten el mismo máximo
            $arrayFinal = [];
            foreach ($arrayMaximos as $key => $maximo) {
                if ($key == 0) {
                    $array['diaInicio'] = $maximo[0];
                    $array['maximo'] = $maximo[1];
                } else {
                    if ($maximo[1] != $arrayMaximos[$key - 1][1]) {
                        $array['diaFin'] = $arrayMaximos[$key - 1][0];
                        $arrayFinal[] = $array;

                        $array['diaInicio'] = $maximo[0];
                        $array['maximo'] = $maximo[1];
                    }
                }
                if ($key == (count($arrayMaximos) - 1)) {
                    $array['diaFin'] = $maximo[0];
                    $arrayFinal[] = $array;
                }
            }

            //saco un array con los periodos que comparten el mismo mínimo
            $arrayFinalMinimos = array();
            $array = array();
            foreach ($arrayMaximos as $key => $maximo) {
                if ($key == 0) {
                    $array['diaInicio'] = $maximo[0];
                    $array['minimo'] = $maximo[3];
                } else {
                    if ($maximo[3] != $arrayMaximos[$key - 1][3]) {
                        $array['diaFin'] = $arrayMaximos[$key - 1][0];
                        $arrayFinalMinimos[] = $array;

                        $array['diaInicio'] = $maximo[0];
                        $array['minimo'] = $maximo[3];
                    }
                }
                if ($key == (count($arrayMaximos) - 1)) {
                    $array['diaFin'] = $maximo[0];
                    $arrayFinalMinimos[] = $array;
                }
            }

            $compra = false;
            $subcompras = false;
            $venderPerdidas = false;
            $contCompras = 0;
            $capitalRestante = $capital;

            //Aquí están todos los días de los rangos seleccionados
            foreach ($arrayMaximos as $key => $maximo) {
                //por cada día compruebo si puedo comprar, es decir si hay una distancia > margen+beneficio hasta el máximo
                // y una distancia < hasta el mínimo
                if (!$compra) {
                    //acciones por tanda de compra y dinero gastado
                    $acciones = 0;
                    $gastado = 0;
                    $objetivo = 0;
                    $topePerdidas = 0;
                    $topePerdidasCompra = 0;

                    //usando % beneficio sobre inversion realizada
                    $inversionFicticia = ($capitalRestante * $invInicial) / 100; // 50000
                    $accionesFicticias = intval($inversionFicticia / $maximo[4]); // 9920 acciones
                    $inversionReal = $accionesFicticias * $maximo[4]; // 49996,8
                    $beneficioEsperado = ($beneficio * $inversionReal) / 100; //2499.84 euros
                    $gananciaEsperada = $inversionReal + $beneficioEsperado; // 52496,64 euros

                    if ($accionesFicticias == 0) {
                        $precioVentaMinimo = 0;
                    } else {
                        $precioVentaMinimo = $gananciaEsperada / $accionesFicticias; //5,292 euros
                    }

                    //ahora calculo la distancia mínima entre la realización de la venta y el máximo de los últimos x meses/dias
                    //si el margen es del 2% haremos 2*precioVentaMinimo/100
                    $margenSeguridadSuperior = ($margen * $precioVentaMinimo) / 100; //2% 0.212

                    //necesitamos que precioVentaMinimo+margenSeguridadSuperior sea menor que el máximo de los últimos x meses/dias
                    foreach ($arrayFinal as $d) {
                        if ($maximo[0] >= $d['diaInicio'] && $maximo[0] <= $d['diaFin']) {
                            $topeSuperior = $d['maximo'];
                            break;
                        }
                    }

                    $margenSeguridadinferior = ($margenInferior * $maximo[4]) / 100; //2% 0.212
                    //necesitamos que la distancia entre el precio de compra y el mínimo de los últimos x meses/dias
                    //sea menor que el margen de seguridad inferior
                    foreach ($arrayFinalMinimos as $d) {
                        if ($maximo[0] >= $d['diaInicio'] && $maximo[0] <= $d['diaFin']) {
                            $topeinferior = $d['minimo'];
                            break;
                        }
                    }
                }

                //condición de compra
                if ((($precioVentaMinimo + $margenSeguridadSuperior) <= $topeSuperior) && ((($maximo[4] - $margenSeguridadinferior) <= $topeinferior) || $maximo[4] <= $topeinferior) && ($compra == false)) {
                    //if ((($precioVentaMinimo + $margenSeguridadSuperior) <= $topeSuperior) && ($compra == false)) {
                    $compra = true;
                    $inversion = ($capitalRestante * $invInicial) / 100;
                    $inicio = $maximo[0];

                    $arrayCompra ['inicio'] = $inicio;
                    $arrayCompra ['precioCompra'] = round($maximo[4], 2, PHP_ROUND_HALF_UP);
                    $arrayCompra ['acciones'] = intval($inversion / $maximo[4]);
                    $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];

                    $acciones += $arrayCompra ['acciones'];
                    $gastado += ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                    $capitalRestante -= ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);

                    $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                }

                //no vendo el mismo dia pues estoy usando el precio de cierre como referencia
                if ($compra && $arrayCompra['inicio'] != $maximo[0]) {
                    if ($subcompras) {
                        //Primero compruebo si puedo vender al precio objetivo
                        if ($maximo[4] <= $objetivo && $maximo[5] >= $objetivo) {
                            $arrayCompra['fin'] = $maximo[0];
                            $arrayCompra['precioVenta'] = 'fin subcompras ' . round($objetivo, 2, PHP_ROUND_HALF_UP);
                            $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                            $ganado = $objetivo * $arrayCompra['acciones'];
                            $ganancia = $ganado - $gastado;
                            $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                            $capitalRestante += ($acciones * $objetivo);
                            $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                            $compraVenta[] = $arrayCompra;
                            $compra = false;
                            $venderPerdidas = false;
                            $subcompras = false;
                            $contCompras = 0;
                        } else {
                            //Compruebo si el precio ha bajado de nuevo el % indicado en las compras
                            if ($maximo[4] <= $topePerdidasCompra) {
                                //continuo las subcompras
                                //Primero compruebo que tengo dinero para seguir comprando al menos 1 accion
                                if ($capitalRestante >= $topePerdidasCompra) {
                                    $contCompras++;
                                    $arrayCompra['precioVenta'] = 'colgada (inicio subcompra ' . $contCompras . ')';
                                    $arrayCompra['fin'] = $maximo[0];
                                    $arrayCompra['ganancia'] = '-------';
                                    $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                    $compraVenta[] = $arrayCompra;
                                    //Compruebo si es la ultima compra, en cuyo caso usare el 100% del dinero
                                    if ($contCompras == $numCompras) {
                                        $arrayCompra ['inicio'] = $maximo[0];
                                        $arrayCompra ['precioCompra'] = round($topePerdidasCompra, 2, PHP_ROUND_HALF_UP);
                                        $arrayCompra ['acciones'] = intval(($capitalRestante / $arrayCompra ['precioCompra']));
                                        $capitalRestante -= ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                                        $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];
                                        $acciones += $arrayCompra['acciones'];
                                        $gastado += ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                                        $objetivo = $gastado / $acciones;
                                    } else {
                                        $inversionSubcompra = ($capitalRestante * $invCompra) / 100;
                                        $arrayCompra ['inicio'] = $maximo[0];
                                        $arrayCompra ['precioCompra'] = round($topePerdidasCompra, 2, PHP_ROUND_HALF_UP);
                                        $arrayCompra['acciones'] = intval($inversionSubcompra / $topePerdidasCompra);
                                        $capitalRestante -= ($arrayCompra ['acciones'] * $topePerdidasCompra);
                                        $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];
                                        $acciones += $arrayCompra['acciones'];
                                        $gastado += ($arrayCompra ['acciones'] * $arrayCompra ['precioCompra']);
                                        $objetivo = $gastado / $acciones;

                                    }

                                    $perdidaSubcompra = $porcentajeCompra * $topePerdidasCompra / 100;
                                    $topePerdidasCompra = $topePerdidasCompra - $perdidaSubcompra;
                                }
                            }

                        }
                    } else {
                        if ($venderPerdidas) {
                            if ($arrayCompra['precioCompra'] >= $maximo[4] && $arrayCompra['precioCompra'] <= $maximo[5]) {
                                $arrayCompra['fin'] = $maximo[0];
                                $arrayCompra['precioVenta'] = 'deshecha ' . $arrayCompra['precioCompra'];
                                $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganancia = $ganado - $gastado;
                                $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;
                                $compra = false;
                                $venderPerdidas = false;
                                $subcompras = false;
                                $capitalRestante += ($arrayCompra ['acciones'] * $maximo[2]);
                                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                            } elseif ($arrayCompra['precioCompra'] <= $maximo[4]) {
                                $arrayCompra['fin'] = $maximo[0];
                                $arrayCompra['precioVenta'] = 'deshecha ' . $maximo[4];
                                $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganado = $maximo[4] * $arrayCompra['acciones'];
                                $ganancia = $ganado - $gastado;
                                $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;
                                $compra = false;
                                $venderPerdidas = false;
                                $subcompras = false;
                                $capitalRestante += ($arrayCompra ['acciones'] * $maximo[2]);
                                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                            } else {
                                //compruebo si sigue bajando el precio y sobrepaso el % indicado para hacer una subcompra
                                $perdidaSubcompra = $porcentajeCompra * $topePerdidas / 100;
                                $topePerdidasCompra = $topePerdidas - $perdidaSubcompra;

                                if ($maximo[4] <= $topePerdidasCompra) {
                                    //comienzo las subcompras
                                    $subcompras = true;
                                    $venderPerdidas = false;
                                    $contCompras++;
                                    $arrayCompra['precioVenta'] = 'colgada (inicio Subcompra ' . $contCompras . ')';
                                    $arrayCompra['fin'] = $maximo[0];
                                    $arrayCompra['ganancia'] = '-------';
                                    $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                    $compraVenta[] = $arrayCompra;

                                    if ($numCompras == 1) {
                                        $inversion = $capitalRestante;
                                    } else {
                                        $inversion = ($capitalRestante * $invCompra) / 100;
                                    }

                                    $arrayCompra ['precioCompra'] = round($topePerdidasCompra, 2, PHP_ROUND_HALF_UP);
                                    $arrayCompra['acciones'] = intval($inversion / $arrayCompra ['precioCompra']);
                                    $arrayCompra['inicio'] = $maximo[0];
                                    $capitalRestante -= ($arrayCompra ['acciones'] * $arrayCompra ['precioCompra']);
                                    $arrayCompra ['gastado'] = $arrayCompra ['precioCompra'] * $arrayCompra ['acciones'];
                                    $acciones += $arrayCompra['acciones'];
                                    $gastado += ($arrayCompra ['precioCompra'] * $arrayCompra ['acciones']);
                                    $objetivo = $gastado / $acciones;
                                }

                                //Compruebo si el precio ha bajado de nuevo el % indicado en las compras
                                $perdidaSubcompra = $porcentajeCompra * $topePerdidasCompra / 100;
                                $topePerdidasCompra = $topePerdidasCompra - $perdidaSubcompra;
                            }
                        } else {
                            //busco el momento de vender por  ganancias
                            //compruebo que el precio de venta esté entre el minimo y maximo del día
                            if ($precioVentaMinimo >= $maximo[4] && $precioVentaMinimo <= $maximo[5]) {
                                $fin = $maximo[0];
                                $arrayCompra['fin'] = $fin;
                                $arrayCompra['precioVenta'] = $precioVentaMinimo;
                                $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                                $ganancia = $ganado - $gastado;
                                $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                                $capitalRestante += ($arrayCompra ['acciones'] * $arrayCompra['precioVenta']);
                                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;
                                $compra = false;
                            } elseif ($precioVentaMinimo <= $maximo[4]) {
                                //puede que el precio de un salto y se pase el valor al que queríamos vender
                                //el precio de venta no va a ser el que fijamos porque se lo ha saltado pero sí el mínimo del día
                                $fin = $maximo[0];
                                $arrayCompra['fin'] = $fin;
                                $arrayCompra['precioVenta'] = $maximo[4];
                                $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                                $ganancia = $ganado - $gastado;
                                $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                                $capitalRestante += ($arrayCompra ['acciones'] * $arrayCompra['precioVenta']);
                                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;
                                $compra = false;
                            }

                            //busco el momento de vender por pérdidas y si no lo consigo realizo subcompra
                            $perdida = $riesgo * $arrayCompra['precioCompra'] / 100;
                            $topePerdidas = $arrayCompra['precioCompra'] - $perdida;

                            if ($maximo[5] <= $topePerdidas) {
                                $venderPerdidas = true;
                                $objetivo = $arrayCompra['precioCompra'];
                            }
                        }
                    }
                }
            }

            //compruebo si tengo alguna compra abierta al finalizar el bucle
            if ($compra) {
                $arrayCompra['fin'] = 'pendiente';
                $arrayCompra['precioVenta'] = 'colgada';
                $arrayCompra['ganancia'] = '-------';
                $arrayCompra ['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                $compraVenta[] = $arrayCompra;
            }

            $ganancia = 0;
            $numCom = count($compraVenta);
            foreach ($compraVenta as $compra) {
                if ($compra['ganancia'] != '-------') {
                    $ganancia += $compra['ganancia'];
                }
            }
            $array = [];
            $array['beneficio'] = $beneficio;
            $array['riesgo'] = $riesgo;
            $array['mss'] = $margen;
            $array['msi'] = $margenInferior;
            $array['porcentajeCompra'] = $porcentajeCompra;
            $array['ganancia'] = $ganancia;
            $array['numCompras'] = $numCom;

            $resultados[] = $array;
        }

        chdir('tablasResultados');
        $numAle = rand(1,1000);

        $fp = fopen('ficheromultivariante' . $numAle .'.csv', 'a+');
        foreach ($resultados as $key => $res) {
            fputcsv($fp, [$res['beneficio'], $res['riesgo'],$res['mss'],$res['msi'],$res['porcentajeCompra'], $res['ganancia'] , $res['numCompras']]);
        }
        fclose($fp);

        //$regresion = self::linear_regression($array_x, $array_y);

        return new JsonResponse(array("resParam" => $resultadosFinal, "resultado" => true));
    }

    public function procesarDatos($datos)
    {
        $lineas = explode("\n", $datos);
        $dataPoints = [];

        foreach ($lineas as $i => $linea) {
            $array = [];
            if ($i > 0) {
                $campos = explode(',', $linea);
                if (count($campos) == 7) {
                    $x = $campos[0];
                    //open, high, low, close
                    $y = [$campos[1], $campos[2], $campos[3], $campos[4]];
                    $array['x'] = $x;
                    $array['y'] = $y;
                    $dataPoints[] = $array;
                }
            }
        }

        return $dataPoints;
    }

    public function procesarDatosIndicadores($datos)
    {
        $lineas = explode("\n", $datos);
        $dataPoints = [];

        foreach ($lineas as $i => $linea) {
            $array = [];
            if ($i > 0) {
                $campos = explode(',', $linea);
                if (count($campos) == 7) {
                    $array['date'] = $campos[0];
                    $array['open'] = $campos[1];
                    $array['high'] = $campos[2];
                    $array['low'] = $campos[3];
                    $array['close'] = $campos[4];
                    $dataPoints[] = $array;
                }
            }
        }
        return $dataPoints;
    }

    public function rsi($datos, $period = 14)
    {
        $change_array = array();
        $data = self::procesarDatosIndicadores($datos);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($data as $key => $row) {
            if($data[$key]['close'] == 'null'){
                unset($data[$key]);
            }
        }

        $data = array_values($data);

        //loop data
        foreach ($data as $key => $row) {

            //need 2 points to get change
            if ($key >= 1) {
                $change = $data[$key]['close'] - $data[$key - 1]['close'];
                //add to front
                array_unshift($change_array, $change);
                //pop back if too long
                if (count($change_array) > $period)
                    array_pop($change_array);
            }
            //have enough data to calc rsi
            if ($key > $period) {
                //reduce change array getting sum loss and sum gains
                $res = array_reduce($change_array, function ($result, $item) {
                    if ($item >= 0)
                        $result['sum_gain'] += $item;

                    if ($item < 0)
                        $result['sum_loss'] += abs($item);
                    return $result;
                }, array('sum_gain' => 0, 'sum_loss' => 0));
                $avg_gain = $res['sum_gain'] / $period;
                $avg_loss = $res['sum_loss'] / $period;
                //check divide by zero
                if ($avg_loss == 0) {
                    $rsi = 100;
                } else {
                    //calc and normalize
                    $rs = $avg_gain / $avg_loss;
                    $rsi = 100 - (100 / (1 + $rs));
                }
                //save
                $data[$key]['val'] = $rsi;
            }
        }
        return $data;
    }

    public function adx($datos, $period = 14)
    {
        $true_range_array = array();
        $plus_dm_array = array();
        $minus_dm_array = array();
        $dx_array = array();
        $previous_adx = null;

        $data = self::procesarDatosIndicadores($datos);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($data as $key => $row) {
            if($data[$key]['close'] == 'null'){
                unset($data[$key]);
            }
        }

        $data = array_values($data);

        //loop data
        foreach ($data as $key => $row) {
            //need 2 data points
            if ($key > 0) {
                //calc true range
                $true_range = max(($data[$key]['high'] - $data[$key]['low']), abs($data[$key]['high'] - $data[$key - 1]['close']), abs($data[$key]['low'] - $data[$key - 1]['close']));
                //calc +DM 1
                $plus_dm_1 = (($data[$key]['high'] - $data[$key - 1]['high']) > ($data[$key - 1]['low'] - $data[$key]['low'])) ? max($data[$key]['high'] - $data[$key - 1]['high'], 0) : 0;
                //calc -DM 1
                $minus_dm_1 = (($data[$key - 1]['low'] - $data[$key]['low']) > ($data[$key]['high'] - $data[$key - 1]['high'])) ? max($data[$key - 1]['low'] - $data[$key]['low'], 0) : 0;
                //add to front
                array_unshift($true_range_array, $true_range);
                array_unshift($plus_dm_array, $plus_dm_1);
                array_unshift($minus_dm_array, $minus_dm_1);
                //pop back if too long
                if (count($true_range_array) > $period)
                    array_pop($true_range_array);
                if (count($plus_dm_array) > $period)
                    array_pop($plus_dm_array);
                if (count($minus_dm_array) > $period)
                    array_pop($minus_dm_array);
            }
            //calc dx
            if (count($true_range_array) == $period) {
                $sum_true_range = array_reduce($true_range_array, function ($result, $item) {
                    $result += $item;
                    return $result;
                }, 0);
                $sum_plus_dm = array_reduce($plus_dm_array, function ($result, $item) {
                    $result += $item;
                    return $result;
                }, 0);
                $sum_minus_dm = array_reduce($minus_dm_array, function ($result, $item) {
                    $result += $item;
                    return $result;
                }, 0);
                //Esta es +DI la linea verde
                $plus_di = ($sum_plus_dm / $sum_true_range) * 100;
                $data[$key]['+di'] = $plus_di;
                //Esta es -DI la linea roja
                $minus_di = ($sum_minus_dm / $sum_true_range) * 100;
                $data[$key]['-di'] = $minus_di;

                //ahora se calcula DX (Directional Movement Index)
                $di_diff = abs($plus_di - $minus_di);
                $di_sum = $plus_di + $minus_di;
                $dx = ($di_diff / $di_sum) * 100;
                //add to front
                array_unshift($dx_array, $dx);
                //pop back if too long
                if (count($dx_array) > $period)
                    array_pop($dx_array);
            }
            //calculo el primer adx haciendo la media de los últimos "x" DX
            if (count($dx_array) == $period) {
                $sum = array_reduce($dx_array, function ($result, $item) {
                    $result += $item;
                    return $result;
                }, 0);
                $adx = $sum / $period;
                //save
                $data[$key]['val'] = $adx;
                $previous_adx = $adx;
            }

            //calculo del adx
            if (isset($previous_adx)) {
                //multiplicamos el adx previo por periodo-1, le sumamos el dx más reciente
                // y lo dividimostodo por el periodo
                $adx = (($previous_adx * ($period - 1)) + $dx) / $period;
                //save
                $data[$key]['val'] = $adx;
                $previous_adx = $adx;
            }
        }
        return $data;
    }

    //swing trading y hacer más operaciones, te recomiendo que uses el MACD rápido, que sería configurando 5. 13, 10
    public function macd($datos, $ema1 = 12, $ema2 = 26, $signal = 9)
    {
        $smoothing_constant_1 = 2 / ($ema1 + 1);
        $smoothing_constant_2 = 2 / ($ema2 + 1);
        $previous_EMA1 = null;
        $previous_EMA2 = null;
        $ema1_value = null;
        $ema2_value = null;
        $macd_array = array();

        $data = self::procesarDatosIndicadores($datos);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($data as $key => $row) {
            if($data[$key]['close'] == 'null'){
                unset($data[$key]);
            }
        }

        $data = array_values($data);

        //loop data
        foreach ($data as $key => $row) {

            //ema 1
            if ($key >= $ema1) {
                //first
                if (!isset($previous_EMA1)) {
                    $sum = 0;
                    for ($i = $key - ($ema1 - 1); $i <= $key; $i++)
                        $sum += $data[$i]['close'];
                    //calc sma
                    $sma = $sum / $ema1;
                    //save
                    $previous_EMA1 = $sma;
                    $ema1_value = $sma;
                } else {
                    //ema formula
                    $ema = ($data[$key]['close'] - $previous_EMA1) * $smoothing_constant_1 + $previous_EMA1;
                    //save
                    $previous_EMA1 = $ema;
                    $ema1_value = $ema;
                }
            }
            //ema 2
            if ($key >= $ema2) {
                //first
                if (!isset($previous_EMA2)) {
                    $sum = 0;
                    for ($i = $key - ($ema2 - 1); $i <= $key; $i++)
                        $sum += $data[$i]['close'];
                    //calc sma
                    $sma = $sum / $ema2;
                    //save
                    $previous_EMA2 = $sma;
                    $ema2_value = $sma;
                } else {
                    //ema formula
                    $ema = ($data[$key]['close'] - $previous_EMA2) * $smoothing_constant_2 + $previous_EMA2;
                    //save
                    $previous_EMA2 = $ema;
                    $ema2_value = $ema;
                }
            }
            //check if we have 2 values to calc MACD Line
            if (isset($ema1_value) && isset($ema2_value)) {
                $macd_line = $ema1_value - $ema2_value;

                //add to front
                array_unshift($macd_array, $macd_line);
                //pop back if too long
                if (count($macd_array) > $signal)
                    array_pop($macd_array);
                //save
                $data[$key]['val'] = $macd_line;
            }
            //have enough data to calc signal sma
            if (count($macd_array) == $signal) {

                //k moving average
                $sum = array_reduce($macd_array, function ($result, $item) {
                    $result += $item;
                    return $result;
                }, 0);
                $sma = $sum / $signal;

                //save
                $data[$key]['val2'] = $sma;
            }
        }
        return $data;
    }

    public function roc($datos, $period = 12)
    {
        $close_array = array();
        $data = self::procesarDatosIndicadores($datos);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($data as $key => $row) {
            if($data[$key]['close'] == 'null'){
                unset($data[$key]);
            }
        }

        $data = array_values($data);

        //loop data
        foreach ($data as $key => $row) {

            //first ROC
            if ($key >= $period) {
                //calc
                $roc = (($data[$key]['close'] - $close_array[$period - 1]) / $close_array[$period - 1]) * 100;

                //save
                $data[$key]['val'] = $roc;
            }
            //add to front
            array_unshift($close_array, $data[$key]['close']);
            //pop back if too long
            if (count($close_array) > $period)
                array_pop($close_array);
        }
        return $data;
    }

    public function bullsBears($datos, $period = 13)
    {
        $smoothing_constant = 2 / ($period + 1);
        $previous_EMA = null;
        $data = self::procesarDatosIndicadores($datos);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($data as $key => $row) {
            if($data[$key]['close'] == 'null'){
                unset($data[$key]);
            }
        }

        $data = array_values($data);

        //loop data
        foreach ($data as $key => $row) {

            //skip init rows
            if ($key >= $period) {
                //first
                if (!isset($previous_EMA)) {
                    $sum = 0;
                    for ($i = $key - ($period - 1); $i <= $key; $i++)
                        $sum += $data[$i]['close'];
                    //calc sma
                    $sma = $sum / $period;
                    //save
                    $data[$key]['val'] = $sma;
                    $previous_EMA = $sma;
                } else {
                    //ema formula
                    $ema = ($data[$key]['close'] - $previous_EMA) * $smoothing_constant + $previous_EMA;
                    //save
                    $data[$key]['val'] = $ema;
                    $previous_EMA = $ema;
                }
                //calc bull bear power
                $bull_power = $data[$key]['high'] - $previous_EMA;
                $bear_power = $data[$key]['low'] - $previous_EMA;
                $diff = $bull_power - $bear_power;

                //save
                $data[$key]['val'] = $diff;
            }
        }
        return $data;
    }

    public function atr($datos, $period = 14)
    {
        //init
        $High_minus_Low = null;
        $High_minus_Close_past = null;
        $Low_minus_Close_past = null;
        $TR = null;
        $TR_sum = 0;
        $data = self::procesarDatosIndicadores($datos);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($data as $key => $row) {
            if($data[$key]['close'] == 'null'){
                unset($data[$key]);
            }
        }

        $data = array_values($data);

        //loop data
        foreach ($data as $key => $row) {
            $High_minus_Low = $data[$key]['high'] - $data[$key]['low'];
            if ($key >= 1) {
                $High_minus_Close_past = abs($data[$key]['high'] - $data[$key - 1]['close']);
                $Low_minus_Close_past = abs($data[$key]['low'] - $data[$key - 1]['close']);
            }

            if (isset($High_minus_Close_past) && isset($Low_minus_Close_past)) {
                $TR = max($High_minus_Low, $High_minus_Close_past, $Low_minus_Close_past);
                //sum first TRs for first ATR avg
                if ($key <= $period)
                    $TR_sum += $TR;
            }
            //first ATR
            if ($key == $period) {
                $atr = $TR_sum / $period;
                $data[$key]['val'] = $atr;
                $previous_ATR = $atr;
            }
            //remaining ATR
            if ($key > $period) {
                $atr = (($previous_ATR * ($period - 1)) + $TR) / $period;
                $data[$key]['val'] = $atr;
                $previous_ATR = $atr;
            }
        }
        return $data;
    }

    public function sma($datos, $period = 5)
    {
        $data = self::procesarDatosIndicadores($datos);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($data as $key => $row) {
            if($data[$key]['close'] == 'null'){
                unset($data[$key]);
            }
        }

        $data = array_values($data);

        //loop data
        foreach ($data as $key => $row) {

            //Add logic here
            if ($key >= $period) {
                $sum = 0;
                for ($i = $key - ($period - 1); $i <= $key; $i++)
                    $sum += $data[$i]['close'];

                $sma = $sum / $period;

                //add sma field and value
                $data[$key]['val'] = $sma;
            }
        }
        return $data;
    }

    function exponentialMovingAverage($datos, $n = 5)
    {
        $datos = array_reverse($datos);
        $m = count($datos);
        $α = 2 / ($n + 1);
        $EMA = [];

        // Start off by seeding with the first data point
        $EMA[] = $datos[0]['close'];

        // Each day after: EMAtoday = α⋅xtoday + (1-α)EMAyesterday
        for ($i = 1; $i < $m; $i++) {
            $EMA[] = ($α * $datos[$i]['close']) + ((1 - $α) * $EMA[$i - 1]);
        }
        $EMA = array_reverse($EMA);
        return $EMA;
    }

    public function williamsR($datos, $period = 14)
    {
        $high_array = array();
        $low_array = array();
        $data = self::procesarDatosIndicadores($datos);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($data as $key => $row) {
            if($data[$key]['close'] == 'null'){
                unset($data[$key]);
            }
        }

        $data = array_values($data);

        //loop data
        foreach ($data as $key => $row) {
            //add to front
            array_unshift($high_array, $data[$key]['high']);
            //pop back if too long
            if (count($high_array) > $period)
                array_pop($high_array);
            //add to front
            array_unshift($low_array, $data[$key]['low']);
            //pop back if too long
            if (count($low_array) > $period)
                array_pop($low_array);
            //have enough data to calc perc r
            if ($key >= $period) {
                //max of highs
                $init = $high_array[0];
                $h = array_reduce($high_array, function ($v, $w) {
                    $v = max($w, $v);
                    return $v;
                }, $init);
                //low of lows
                $init = $low_array[0];
                $l = array_reduce($low_array, function ($v, $w) {
                    $v = min($w, $v);
                    return $v;
                }, $init);
                //calc percent R
                $perc_r = ($h - $data[$key]['close']) / ($h - $l) * -100;

                //save
                $data[$key]['val'] = $perc_r;
            }
        }

        return $data;
    }

    public function estocastico($datos, $period = 14, $sma_period = 3)
    {
        $high_array = array();
        $low_array = array();
        $k_array = array();
        $data = self::procesarDatosIndicadores($datos);

        //proceso los datos y elimino aquellos que tengan null
        foreach ($data as $key => $row) {
            if($data[$key]['close'] == 'null'){
                unset($data[$key]);
            }
        }

        $data = array_values($data);

        //loop data
        foreach ($data as $key => $row) {
            //add to front
            array_unshift($high_array, $data[$key]['high']);
            //pop back if too long
            if (count($high_array) > $period)
                array_pop($high_array);
            //add to front
            array_unshift($low_array, $data[$key]['low']);
            //pop back if too long
            if (count($low_array) > $period)
                array_pop($low_array);
            //have enough data to calc stoch
            if ($key >= $period) {
                //max of highs
                $init = $high_array[0];
                $h = array_reduce($high_array, function ($v, $w) {
                    $v = max($w, $v);
                    return $v;
                }, $init);
                //low of lows
                $init = $low_array[0];
                $l = array_reduce($low_array, function ($v, $w) {
                    $v = min($w, $v);
                    return $v;
                }, $init);
                //calc
                $k = ($data[$key]['close'] - $l) / ($h - $l) * 100;
                //add to front
                array_unshift($k_array, $k);
                //pop back if too long
                if (count($k_array) > $sma_period)
                    array_pop($k_array);

                //save
                $data[$key]['val'] = $k;
            }
            //have enough data to calc sma
            if (count($k_array) == $sma_period) {

                //k moving average
                $sum = array_reduce($k_array, function ($result, $item) {
                    $result += $item;
                    return $result;
                }, 0);
                $sma = $sum / $sma_period;
                //save
                $data[$key]['val2'] = $sma;
            }
        }
        return $data;
    }


    public function indicadorRsiAction($a, $b, $c, $data)
    {
        $fechaIni = $a;
        $fechaFin = $b;
        $capital = $c;
        $capitalRestante = $capital;
        $acciones = 0;

        //traigo los resultados de los diferentes indicadores
        $rsi = self::rsi($data);
        $abajo = false;
        $arriba = false;
        $compraRsi = false;
        $compraVenta = array();



        //70 a 100 sobrecompra y 0 a 30 sobreventa, se compra o se vende cuando se vuelve a la zona intermedia
        foreach ($rsi as $i => $diaRsi) {
            if ($diaRsi['date'] >= $fechaIni and $diaRsi['date'] <= $fechaFin && $capitalRestante > 0) {
                if (!$compraRsi) {
                    //intento abrirla
                    if (!$abajo) {
                        if ($diaRsi['val'] <= 30) {
                            $abajo = true;
                        }
                    } else {
                        if ($diaRsi['val'] > 30) {
                            $compraRsi = true;
                            $abajo = false;
                            //compro
                            $inversion = $capitalRestante;
                            $acciones = intval($inversion / $diaRsi['close']);
                            $arrayCompra ['inicio'] = $diaRsi['date'];
                            $arrayCompra ['precioCompra'] = round($diaRsi['close'], 2, PHP_ROUND_HALF_UP);
                            $arrayCompra ['acciones'] = $acciones;

                            $capitalRestante -= (round($diaRsi['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                        }
                    }
                } else {
                    //intento cerrarla
                    if (!$arriba) {
                        if ($diaRsi['val'] >= 70) {
                            $arriba = true;
                        }
                    } else {
                        if ($diaRsi['val'] < 70) {
                            $compraRsi = false;
                            $arriba = false;
                            $arrayCompra['fin'] = $diaRsi['date'];
                            $arrayCompra['precioVenta'] = round($diaRsi['close'], 2, PHP_ROUND_HALF_UP);
                            $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                            $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                            $ganancia = $ganado - $gastado;
                            $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                            //vendo
                            $capitalRestante += (round($diaRsi['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                            $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                            $acciones = 0;
                            $compraVenta[] = $arrayCompra;
                        }
                    }
                }
            }
        }
        if ($compraRsi) {
            //cierro el array que hay abierto
            $arrayCompra['fin'] = 'pendiente';
            $arrayCompra['precioVenta'] = '-------';
            $arrayCompra['ganancia'] = '-------';
            $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
            $compraVenta[] = $arrayCompra;
        }
        return $compraVenta;
    }

    public function indicadorMacdAction($a, $b, $c, $data)
    {
        $fechaIni = $a;
        $fechaFin = $b;
        $capital = $c;
        $capitalRestante = $capital;
        $acciones = 0;
        $compraVenta = array();

        $compraMacd = false;
        $abajo = false;

        $macd = self::macd($data);

        //macd: sabemos que se cruzan porque la diferencia entre ambas cambia de signo
        //Cuando el MACD cruza por encima de Señal nos posicionamos alcistas
        //y cuando cruza por debajo, nos salimos del valor.
        foreach ($macd as $i => $diaMacd) {
            if ($diaMacd['date'] >= $fechaIni and $diaMacd['date'] <= $fechaFin && $capitalRestante > 0) {
                if (!$compraMacd) {
                    $valor = $diaMacd['val'] - $diaMacd['val2'];
                    if (!$abajo) {
                        if ($valor < 0) {
                            $abajo = true;
                        }
                    } else {
                        if ($valor > 0) {
                            $abajo = false;
                            $compraMacd = true;
                            //compro
                            $inversion = $capitalRestante;
                            $acciones = intval($inversion / $diaMacd['close']);
                            $arrayCompra ['inicio'] = $diaMacd['date'];
                            $arrayCompra ['precioCompra'] = round($diaMacd['close'], 2, PHP_ROUND_HALF_UP);
                            $arrayCompra ['acciones'] = $acciones;
                            $capitalRestante -= (round($diaMacd['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                        }
                    }
                } else {
                    $valor = $diaMacd['val'] - $diaMacd['val2'];
                    if ($valor < 0) {
                        $abajo = true;
                        $compraMacd = false;
                        //vendo
                        $arrayCompra['fin'] = $diaMacd['date'];
                        $arrayCompra['precioVenta'] = round($diaMacd['close'], 2, PHP_ROUND_HALF_UP);
                        $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                        $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                        $ganancia = $ganado - $gastado;
                        $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                        $capitalRestante += (round($diaMacd['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                        $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                        $acciones = 0;

                        $compraVenta[] = $arrayCompra;
                    }
                }
            }
        }
        if ($compraMacd) {
            //cierro el array que hay abierto
            $arrayCompra['fin'] = 'pendiente';
            $arrayCompra['precioVenta'] = '-------';
            $arrayCompra['ganancia'] = '-------';
            $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
            $compraVenta[] = $arrayCompra;
        }
        return $compraVenta;
    }

    public function indicadorMacd2Action($a, $b, $c, $data)
    {
        $fechaIni = $a;
        $fechaFin = $b;
        $capital = $c;
        $capitalRestante = $capital;
        $acciones = 0;
        $compraVenta = array();

        $compraMacd = false;
        $abajo = false;

        $macd = self::macd($data);


        //Comprar cuando la línea «delta» entre en zona positiva.
        //vender cuanto entre en zona negativa ¿?
        foreach ($macd as $i => $diaMacd) {
            if ($diaMacd['date'] >= $fechaIni and $diaMacd['date'] <= $fechaFin && $capitalRestante > 0) {
                if (!$compraMacd) {
                    $valor = $diaMacd['val'];
                    if (!$abajo) {
                        if ($valor < 0) {
                            $abajo = true;
                        }
                    } else {
                        if ($valor > 0) {
                            $abajo = false;
                            $compraMacd = true;
                            //compro
                            $inversion = $capitalRestante;
                            $acciones = intval($inversion / $diaMacd['close']);
                            $arrayCompra ['inicio'] = $diaMacd['date'];
                            $arrayCompra ['precioCompra'] = round($diaMacd['close'], 2, PHP_ROUND_HALF_UP);
                            $arrayCompra ['acciones'] = $acciones;
                            $capitalRestante -= (round($diaMacd['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                        }
                    }
                } else {
                    $valor = $diaMacd['val'];
                    if ($valor < 0) {
                        $abajo = true;
                        $compraMacd = false;
                        //vendo
                        $arrayCompra['fin'] = $diaMacd['date'];
                        $arrayCompra['precioVenta'] = round($diaMacd['close'], 2, PHP_ROUND_HALF_UP);
                        $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                        $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                        $ganancia = $ganado - $gastado;
                        $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                        $capitalRestante += (round($diaMacd['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                        $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                        $acciones = 0;

                        $compraVenta[] = $arrayCompra;
                    }
                }
            }
        }
        if ($compraMacd) {
            //cierro el array que hay abierto
            $arrayCompra['fin'] = 'pendiente';
            $arrayCompra['precioVenta'] = '-------';
            $arrayCompra['ganancia'] = '-------';
            $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
            $compraVenta[] = $arrayCompra;
        }
        return $compraVenta;
    }

    public function indicadorEstocasticoAction($a, $b, $c, $data)
    {
        $fechaIni = $a;
        $fechaFin = $b;
        $capital = $c;
        $capitalRestante = $capital;
        $acciones = 0;

        $compraVenta = array();

        $compraEstocastico = false;
        $abajo = false;
        $arriba = false;
        $estocastico = self::estocastico($data);

        //val = %K, val2 = %D
        //Estrategia de zona de salida,se trata de comprar cuando la línea lenta (%D) sale de
        //la zona de sobreventa (0-20) y vender cuando ésta misma línea salga de la zona de sobrecompra (80-100).
        // Si la línea vuelve a entrar en la zona de sobreventa se cierra la posición anticipadamente.
        foreach ($estocastico as $i => $diaEst) {
            if ($diaEst['date'] >= $fechaIni and $diaEst['date'] <= $fechaFin && $capitalRestante > 0) {
                if (!$compraEstocastico) {
                    if (!$abajo) {
                        if ($diaEst['val2'] <= 20) {
                            $abajo = true;
                        }
                    } else {
                        if ($diaEst['val2'] > 20) {
                            $abajo = false;
                            $compraEstocastico = true;
                            //compro
                            $inversion = $capitalRestante;
                            $acciones = intval($inversion / $diaEst['close']);
                            $arrayCompra ['inicio'] = $diaEst['date'];
                            $arrayCompra ['precioCompra'] = round($diaEst['close'], 2, PHP_ROUND_HALF_UP);
                            $arrayCompra ['acciones'] = $acciones;
                            $capitalRestante -= (round($diaEst['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                        }
                    }
                } else {
                    //si vuelvo a entrar en zona de sobreventa vendo anticipadamente
                    if ($diaEst['val2'] <= 20) {
                        $abajo = true;
                        //vendo
                        $compraEstocastico = false;
                        $capitalRestante += ($diaEst['close'] * $acciones);
                        $arrayCompra['fin'] = $diaEst['date'];
                        $arrayCompra['precioVenta'] = round($diaEst['close'], 2, PHP_ROUND_HALF_UP);
                        $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                        $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                        $ganancia = $ganado - $gastado;
                        $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                        $acciones = 0;
                        $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                        $compraVenta[] = $arrayCompra;
                    } elseif (!$arriba) {
                        if ($diaEst['val2']['value'] >= 80) {
                            $arriba = true;
                        } else {
                            if ($diaEst['val2'] < 70) {
                                $compraEstocastico = false;
                                $arriba = false;
                                //vendo
                                $capitalRestante += (round($diaEst['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                                $arrayCompra['fin'] = $diaEst['date'];
                                $arrayCompra['precioVenta'] = round($diaEst['close'], 2, PHP_ROUND_HALF_UP);
                                $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                                $ganancia = $ganado - $gastado;
                                $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                                $acciones = 0;
                                $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                $compraVenta[] = $arrayCompra;
                            }
                        }
                    }
                }
            }
        }
        if ($compraEstocastico) {
            //cierro el array que hay abierto
            $arrayCompra['fin'] = 'pendiente';
            $arrayCompra['precioVenta'] = '-------';
            $arrayCompra['ganancia'] = '-------';
            $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
            $compraVenta[] = $arrayCompra;
        }
        return $compraVenta;
    }

    public function indicadorAdxAction($a, $b, $c, $data)
    {
        $fechaIni = $a;
        $fechaFin = $b;
        $capital = $c;
        $capitalRestante = $capital;
        $acciones = 0;
        $compraVenta = array();

        $compraAdx = false;
        $abajo = false;
        $adx = self::adx($data);
        $macd = self::macd($data);
        $atr = self::atr($data);
        $precioObjetivo = 0;

        //-	El MACD cruza por encima de la línea de 0;
        //-	La línea azul ADX (+DI) está por encima de la línea roja (-DI);
        //-	Si la línea ADX está por encima de 25, indica una fuerte tendencia
        foreach ($macd as $i => $diaMacd) {
            if ($diaMacd['date'] >= $fechaIni and $diaMacd['date'] <= $fechaFin && $capitalRestante > 0) {
                if (!$compraAdx) {
                    $valor = $diaMacd['val'];
                    if (!$abajo) {
                        if ($valor < 0) {
                            $abajo = true;
                        }
                    } else {
                        if ($valor > 0) {
                            $abajo = false;
                            foreach ($adx as $k => $diaAdx) {
                                if ($diaAdx['date'] == $diaMacd['date']) {
                                    if ($diaAdx['+di'] > $diaAdx['-di']) {
                                        if ($diaAdx['val'] > 25) {
                                            $compraAdx = true;
                                            //compro
                                            $inversion = $capitalRestante;
                                            $acciones = intval($inversion / $diaMacd['close']);
                                            $arrayCompra ['inicio'] = $diaMacd['date'];
                                            $arrayCompra ['precioCompra'] = round($diaMacd['close'], 2, PHP_ROUND_HALF_UP);
                                            $arrayCompra ['acciones'] = $acciones;
                                            $capitalRestante -= (round($diaMacd['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                                            foreach ($atr as $diaAtr) {
                                                if ($diaAdx['date'] == $diaAtr['date']) {
                                                    $precioObjetivo = ($diaAtr['val'] * 3) + $arrayCompra['precioCompra'];
                                                    break;
                                                }
                                            }

                                        }
                                    }
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    $valor = $diaMacd['val'];
                    if ($diaMacd['close'] >= $precioObjetivo) {
                        //vendo
                        $compraAdx = false;
                        $arrayCompra['fin'] = $diaMacd['date'];
                        $arrayCompra['precioVenta'] = round($diaMacd['close'], 2, PHP_ROUND_HALF_UP);
                        $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                        $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                        $ganancia = $ganado - $gastado;
                        $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                        $capitalRestante += (round($diaMacd['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                        $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                        $acciones = 0;
                        $compraVenta[] = $arrayCompra;
                    }
                    if ($compraAdx && ($valor < 0)) {
                        $abajo = true;
                        foreach ($adx as $k => $diaAdx) {
                            if ($diaAdx['date'] == $diaMacd['date']) {
                                if ($diaAdx['+di'] < $diaAdx['-di']) {
                                    if ($diaAdx['val'] > 25) {
                                        //vendo
                                        $compraAdx = false;
                                        $arrayCompra['fin'] = $diaMacd['date'];
                                        $arrayCompra['precioVenta'] = round($diaMacd['close'], 2, PHP_ROUND_HALF_UP);
                                        $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                                        $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                                        $ganancia = $ganado - $gastado;
                                        $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                                        $capitalRestante += (round($diaMacd['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                                        $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                                        $acciones = 0;
                                        $compraVenta[] = $arrayCompra;
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }
        if ($compraAdx) {
            //cierro el array que hay abierto
            $arrayCompra['fin'] = 'pendiente';
            $arrayCompra['precioVenta'] = '-------';
            $arrayCompra['ganancia'] = '-------';
            $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
            $compraVenta[] = $arrayCompra;
        }
        return $compraVenta;
    }

    public function indicadorRocAction($a, $b, $c, $data)
    {
        $fechaIni = $a;
        $fechaFin = $b;
        $capital = $c;
        $capitalRestante = $capital;
        $acciones = 0;
        $compraVenta = array();

        $compraRoc = false;
        $abajo = false;
        $roc = self::roc($data);

        //Si se encuentra por encima del nivel de 0, quiere decir que los precios tienen empuje para subir.
        //Si el valor es negativo, significa que los precios están impulsados para bajar.
        //Si la línea del indicador supera el nivel de 0 de abajo a arriba, tendrás señal de compra;
        //si supera este nivel de arriba a abajo, tendrás señal de venta.
        //en laterales es peor
        foreach ($roc as $i => $diaRoc) {
            if ($diaRoc['date'] >= $fechaIni and $diaRoc['date'] <= $fechaFin && $capitalRestante > 0) {
                if (!$compraRoc) {
                    if (!$abajo) {
                        if ($diaRoc['val'] < 0) {
                            $abajo = true;
                        }
                    } else {
                        if ($diaRoc['val'] > 0) {
                            //señal de compra
                            $abajo = false;
                            $compraRoc = true;

                            $inversion = $capitalRestante;
                            $acciones = intval($inversion / $diaRoc['close']);
                            $arrayCompra ['inicio'] = $diaRoc['date'];
                            $arrayCompra ['precioCompra'] = round($diaRoc['close'], 2, PHP_ROUND_HALF_UP);
                            $arrayCompra ['acciones'] = $acciones;
                            $capitalRestante -= ($arrayCompra ['precioCompra'] * $acciones);
                        }
                    }
                } else {
                    if ($diaRoc['val'] < 0) {
                        $abajo = true;
                        $compraRoc = false;
                        //señal de venta
                        $arrayCompra['fin'] = $diaRoc['date'];
                        $arrayCompra['precioVenta'] = round($diaRoc['close'], 2, PHP_ROUND_HALF_UP);
                        $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                        $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                        $ganancia = $ganado - $gastado;
                        $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);
                        $capitalRestante += (round($diaRoc['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                        $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                        $acciones = 0;
                        $compraVenta[] = $arrayCompra;
                    }
                }
            }
        }
        if ($compraRoc) {
            //cierro el array que hay abierto
            $arrayCompra['fin'] = 'pendiente';
            $arrayCompra['precioVenta'] = '-------';
            $arrayCompra['ganancia'] = '-------';
            $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
            $compraVenta[] = $arrayCompra;
        }
        return $compraVenta;
    }

    public function indicadorWilliamsAction($a, $b, $c, $data)
    {
        $fechaIni = $a;
        $fechaFin = $b;
        $capital = $c;
        $capitalRestante = $capital;
        $acciones = 0;
        $compraVenta = array();

        $compraWilliam = false;
        $abajo = false;
        $arriba = false;
        $william = self::williamsR($data);

        //El Williams% R se mueve entre 0 y -100, lo que hace del nivel -50 el punto medio
        //La configuración tradicional utiliza -20 como el límite de sobrecompra y -80 como el límite de sobreventa.
        // Si cierra por debajo de 80 (80-100), se produce una situación de sobre venta y una oportunidad teórica de comprar.
        // bien en laterales
        foreach ($william as $i => $diaWill) {
            if ($diaWill['date'] >= $fechaIni and $diaWill['date'] <= $fechaFin && $capitalRestante > 0) {

                if (!$compraWilliam) {
                    //intento abrirla
                    if (!$abajo) {
                        if ($diaWill['val'] < -80) {
                            $abajo = true;
                        }
                    } else {
                        if ($diaWill['val'] > -80) {
                            $compraWilliam = true;
                            $abajo = false;
                            //compro
                            $inversion = $capitalRestante;
                            $acciones = intval($inversion / $diaWill['close']);
                            $arrayCompra ['inicio'] = $diaWill['date'];
                            $arrayCompra ['precioCompra'] = round($diaWill['close'], 2, PHP_ROUND_HALF_UP);
                            $arrayCompra ['acciones'] = $acciones;

                            $capitalRestante -= ($arrayCompra ['precioCompra'] * $acciones);
                        }
                    }
                } else {
                    //intento cerrarla
                    if (!$arriba) {
                        if ($diaWill['val'] > -20) {
                            $arriba = true;
                        }
                    } else {
                        if ($diaWill['val'] < -20) {
                            $compraWilliam = false;
                            $arriba = false;
                            $arrayCompra['fin'] = $diaWill['date'];
                            $arrayCompra['precioVenta'] = round($diaWill['close'], 2, PHP_ROUND_HALF_UP);
                            $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                            $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                            $ganancia = $ganado - $gastado;
                            $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);

                            //vendo
                            $capitalRestante += ($arrayCompra['precioVenta'] * $acciones);
                            $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                            $acciones = 0;
                            $compraVenta[] = $arrayCompra;
                        }
                    }
                }
            }
        }
        if ($compraWilliam) {
            //cierro el array que hay abierto
            $arrayCompra['fin'] = 'pendiente';
            $arrayCompra['precioVenta'] = '-------';
            $arrayCompra['ganancia'] = '-------';
            $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
            $compraVenta[] = $arrayCompra;
        }

        return $compraVenta;
    }

    public function indicadorSmaAction($a, $b, $c, $data)
    {
        $fechaIni = $a;
        $fechaFin = $b;
        $capital = $c;
        $capitalRestante = $capital;
        $acciones = 0;
        $compraVenta = array();

        $compraSma = false;
        $abajo = false;
        $smaRapida = self::sma($data, 20);
        $smaLenta = self::sma($data, 50);

        //media móvil de 20 periodos como la serie rápida y una media móvil de 50 periodos como la más lenta.
        // Si la Media Móvil (MM) de 20 periodos cruza hacia arriba la MM de 50 periodos será una señal de compra.
        // Si la MM de 20 periodos cruza hacia abajo, será una señal de venta.
        foreach ($smaRapida as $i => $diaSma) {
            if ($diaSma['date'] >= $fechaIni and $diaSma['date'] <= $fechaFin && $capitalRestante > 0) {
                if (!$compraSma) {
                    $valor = $smaLenta[$i]['val'] - $smaRapida[$i]['val'];
                    if (!$abajo) {
                        if ($valor > 0) {
                            $abajo = true;
                        }
                    } else {
                        if ($valor < 0) {
                            $abajo = false;
                            $compraSma = true;
                            //compro
                            $inversion = $capitalRestante;
                            $acciones = intval($inversion / $diaSma['close']);
                            $arrayCompra ['inicio'] = $diaSma['date'];
                            $arrayCompra ['precioCompra'] = round($diaSma['close'], 2, PHP_ROUND_HALF_UP);
                            $arrayCompra ['acciones'] = $acciones;
                            $capitalRestante -= (round($diaSma['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                        }
                    }
                } else {
                    $valor = $smaLenta[$i]['val'] - $smaRapida[$i]['val'];
                    if ($valor > 0) {
                        $abajo = true;
                        $compraSma = false;
                        //vendo
                        $arrayCompra['fin'] = $diaSma['date'];
                        $arrayCompra['precioVenta'] = round($diaSma['close'], 2, PHP_ROUND_HALF_UP);

                        $gastado = $arrayCompra['precioCompra'] * $arrayCompra['acciones'];
                        $ganado = $arrayCompra['precioVenta'] * $arrayCompra['acciones'];
                        $ganancia = $ganado - $gastado;
                        $arrayCompra['ganancia'] = round($ganancia, 2, PHP_ROUND_HALF_UP);

                        //$arrayCompra['ganancia'] = round($diaSma['close'], 2, PHP_ROUND_HALF_UP) * $arrayCompra['acciones'];
                        $capitalRestante += (round($diaSma['close'], 2, PHP_ROUND_HALF_UP) * $acciones);
                        $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
                        $acciones = 0;

                        $compraVenta[] = $arrayCompra;
                    }
                }
            }
        }
        if ($compraSma) {
            //cierro el array que hay abierto
            $arrayCompra['fin'] = 'pendiente';
            $arrayCompra['precioVenta'] = '-------';
            $arrayCompra['ganancia'] = '-------';
            $arrayCompra['capitalRestante'] = round($capitalRestante, 2, PHP_ROUND_HALF_UP);
            $compraVenta[] = $arrayCompra;
        }

        return $compraVenta;
    }

    public function descargarDatosAction()
    {
        $abengoa = "https://query1.finance.yahoo.com/v7/finance/download/ABG.MC?period1=946681200&period2=1540560501&interval=1d&events=history&crumb=CkAI5L0oIoi";

        file_put_contents("abengoa.csv", fopen("http://someurl/file.zip", 'r'));
    }

    /**
     * linear regression function
     * @param $x array
     * @param $y array
     * @returns array() m=>slope, b=>intercept
     */
    function linear_regression($x, $y)
    {
        // calculate number points
        $n = count($x);

        // ensure both arrays of points are the same size
        if ($n != count($y)) {
            trigger_error("linear_regression(): Number of elements in coordinate arrays do not match.", E_USER_ERROR);
        }

        // calculate sums
        $x_sum = array_sum($x);
        $y_sum = array_sum($y);

        $xx_sum = 0;
        $xy_sum = 0;
        $yy_sum = 0;

        for ($i = 0; $i < $n; $i++) {
            $xy_sum += ($x[$i] * $y[$i]);
            $xx_sum += ($x[$i] * $x[$i]);
            $yy_sum += ($y[$i] * $y[$i]);
        }

        // calculate slope
        $m = (($n * $xy_sum) - ($x_sum * $y_sum)) / (($n * $xx_sum) - ($x_sum * $x_sum));

        // calculate intercept
        $b = ($y_sum - ($m * $x_sum)) / $n;

        // calculate r
        $r = ($xy_sum - ((1 / $n) * $x_sum * $y_sum)) / (sqrt((($xx_sum) - ((1 / $n) * (pow($x_sum, 2)))) * (($yy_sum) - ((1 / $n) * (pow($y_sum, 2))))));
        $r2 = $r * $r;

        //con la m y la b tenemos la recta de regresión y = b + mx
        // return result
        return array("m" => $m, "b" => $b, "r" => $r, "r2" => $r2);
    }
}
