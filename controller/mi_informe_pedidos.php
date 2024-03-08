<?php
/*
 * This file is part of pedidos_y_pedidos
 * Copyright (C) 2015-2017    Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2017         Itaca Software Libre contacta@itacaswl.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/facturacion_base/controller/informe_albaranes.php';

require_model('pedido_proveedor.php');
require_model('articulo_proveedor.php');
require_model('proveedor.php');

/**
 * Heredamos del controlador de informe_albaranes, para reaprovechar el código.
 */
class mi_informe_pedidos extends informe_albaranes
{

    public $estado;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Mis_PEDIDOS', 'informes');
    }

    protected function private_core()
    {
        /// declaramos los objetos sólo para asegurarnos de que existen las tablas
        $pedido_cli = new pedido_cliente();
        $pedido_pro = new pedido_proveedor();

        $this->nombre_docs = FS_PEDIDOS;
        $this->table_compras = 'pedidosprov';
        $this->table_ventas = 'pedidoscli';

        parent::private_core();
    }

    protected function ini_filters()
    {
        parent::ini_filters();

        $this->estado = '';
        if (isset($_REQUEST['estado'])) {
            $this->estado = $_REQUEST['estado'];
        }
    }
    
    protected function generar_xls($tipo = 'compra')
    {
        /// desactivamos el motor de plantillas
        $this->template = FALSE;

        header("Content-Disposition: attachment; filename=\"informe_" . $this->nombre_docs . "_" . time() . ".xlsx\"");
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        $header = array(
            // 'serie' => 'string',
            'Pedido' => 'string',
            // 'num2' => 'string',
            // 'num.proveedor' => 'string',
            'fecha' => 'string',
            'cliente' => 'string',
            'Proveedor' => 'string',
            // FS_CIFNIF => 'string',
            // 'neto' => '#,##0.00;[RED]-#,##0.00',
            // 'iva' => '#,##0.00;[RED]-#,##0.00',
            // 're' => '#,##0.00;[RED]-#,##0.00',
            // 'irpf' => '#,##0.00;[RED]-#,##0.00',
            // 'total' => '#,##0.00;[RED]-#,##0.00',
            'Fecha deseada' => 'string',
            'Ref' => 'string',
            'Q'=> 'string',
        );

        if ($tipo == 'compra') {
            $tabla = $this->table_compras;
            unset($header['num2']);
            unset($header['cliente']);
        } else {
            $tabla = $this->table_ventas;
            unset($header['num.proveedor']);
            unset($header['proveedor']);
        }

        $writter = new XLSXWriter();
        $writter->setAuthor('grupo CROVISA');
        $writter->writeSheetHeader($this->nombre_docs, $header);
        foreach ($this->get_documentos($tabla) as $doc) {
 /*            if ($tipo == 'compra') {
                // $linea['num.proveedor'] = $doc->numproveedor;
                $linea['Proveedor'] = $doc->nombre;
                unset($linea['num2']);
                unset($linea['cliente']);
            } else {
                $linea['num2'] = $doc->numero2;
                $linea['cliente'] = $doc->nombrecliente;
                unset($linea['num.proveedor']);
                unset($linea['proveedor']);
            } */
            $ped = new pedido_proveedor();
            $this->documento = $ped->get($doc->idpedido);
            $lineas = $this->documento->get_lineas();
            if ($lineas) {
                for ($i = 0; $i < count($lineas); $i++) {
                    $articulo = new articulo();
                    $art = $articulo->get($lineas[$i]->referencia);
                    if($lineas[$i]->referencia){
                        if (is_null($lineas[$i]->referencia)){
                            $linea['Proveedor'] = 'NULL';
                        } else {
                            $referencia = $lineas[$i]->referencia;
                            $cantidad = strval($lineas[$i]->cantidad);
                            $linea = array(
                                //'serie' => $doc->codserie,
                                'Pedido' => $doc->codigo,
                                // 'num2' => '',
                                // 'num.proveedor' => '',
                                'fecha' => $doc->fecha,
                                'Proveedor' => $doc->nombre,
                                // FS_CIFNIF => $doc->cifnif,
                                // 'neto' => $doc->neto,
                                // 'iva' => $doc->totaliva,
                                // 're' => $doc->totalrecargo,
                                // 'irpf' => $doc->totalirpf,
                                // 'total' => $doc->total,
                                'Fecha deseada' => $doc->fechasalida,
                                'Ref' => $referencia,
                                'Q'=> $cantidad,
                                );
                                $writter->writeSheetRow($this->nombre_docs, $linea);
                       }   
                    }
                }
            }
        }

        $writter->writeToStdOut();
    }

    protected function set_where()
    {
        parent::set_where();

        if ($this->estado != '') {
            switch ($this->estado) {
                case '0':
                    $this->where_compras .= " AND idalbaran IS NULL";
                    $this->where_ventas .= " AND idalbaran IS NULL AND status = '0'";
                    break;

                case '1':
                    $this->where_compras .= " AND idalbaran IS NOT NULL";
                    $this->where_ventas .= " AND status = '1'";
                    break;

                case '2':
                    $this->where_compras .= " AND 1 = 2";
                    $this->where_ventas .= " AND status = '2'";
                    break;
            }
        }
    }

    public function stats_series($tabla = 'pedidosprov')
    {
        return parent::stats_series($tabla);
    }

    public function stats_agentes($tabla = 'pedidosprov')
    {
        return parent::stats_agentes($tabla);
    }

    public function stats_almacenes($tabla = 'pedidosprov')
    {
        return parent::stats_almacenes($tabla);
    }

    public function stats_formas_pago($tabla = 'pedidosprov')
    {
        return parent::stats_formas_pago($tabla);
    }

    public function stats_estados($tabla = 'pedidosprov')
    {
        $stats = array();

        if ($tabla == 'pedidoscli') {
            $stats = $this->stats_estados_pedidoscli();
        } else {
            /// aprobados
            $sql = "select sum(neto) as total from " . $tabla;
            $sql .= $this->where_compras;
            $sql .= " and idalbaran is not null order by total desc;";

            $data = $this->db->select($sql);
            if ($data) {
                if (floatval($data[0]['total'])) {
                    $stats[] = array(
                        'txt' => 'aprobado',
                        'total' => round(floatval($data[0]['total']), FS_NF0)
                    );
                }
            }

            /// pendientes
            $sql = "select sum(neto) as total from " . $tabla;
            $sql .= $this->where_compras;
            $sql .= " and idalbaran is null order by total desc;";

            $data = $this->db->select($sql);
            if ($data) {
                if (floatval($data[0]['total'])) {
                    $stats[] = array(
                        'txt' => 'pendiente',
                        'total' => round(floatval($data[0]['total']), FS_NF0)
                    );
                }
            }
        }

        return $stats;
    }

    private function stats_estados_pedidoscli()
    {
        $stats = array();
        $tabla = 'pedidoscli';

        $sql = "select status,sum(neto) as total from " . $tabla;
        $sql .= $this->where_ventas;
        $sql .= " group by status order by total desc;";

        $data = $this->db->select($sql);
        if ($data) {
            $estados = array(
                0 => 'pendiente',
                1 => 'aprobado',
                2 => 'rechazado',
                3 => 'validado parcialmente'
            );

            foreach ($data as $d) {
                $stats[] = array(
                    'txt' => $estados[$d['status']],
                    'total' => round(floatval($d['total']), FS_NF0)
                );
            }
        }

        return $stats;
    }

    protected function get_documentos($tabla)
    {
        $doclist = array();

        $where = $this->where_compras;
        if ($tabla == $this->table_ventas) {
            $where = $this->where_ventas;
        }

        $sql = "select * from " . $tabla . $where . " order by fecha asc, hora asc;";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                if ($tabla == $this->table_ventas) {
                    $doclist[] = new pedido_cliente($d);
                } else {
                    $doclist[] = new pedido_proveedor($d);
                }
            }
        }

        return $doclist;
    }
}
