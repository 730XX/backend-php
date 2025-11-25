<?php

namespace Inventario\Controllers;

use Inventario\Config\Database;
use Inventario\Utils\Logger;
use Inventario\Utils\ResponseHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Controlador de Reportes
 * Maneja la exportación de datos a Excel
 */
class ReportesController
{
    private $db;
    private $logger;
    private $conn;

    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->conn = $db->getConnection();
    }

    /**
     * Exportar movimientos a Excel
     */
    public function exportarMovimientos($app)
    {
        try {
            // Obtener filtros opcionales
            $request = $app->request;
            $fechaInicio = $request->get('fecha_inicio');
            $fechaFin = $request->get('fecha_fin');
            $tipo = $request->get('tipo');
            $productoId = $request->get('producto_id');

            // Construir query con filtros
            $sql = "SELECT 
                        m.movimientos_id,
                        m.movimientos_fecha,
                        m.movimientos_tipo,
                        m.movimientos_cantidad,
                        m.movimientos_motivo,
                        m.movimientos_comentario,
                        m.movimientos_stock_historico,
                        p.productos_id,
                        p.productos_nombre,
                        p.productos_codigo,
                        p.productos_unidad,
                        u.usuarios_nombre
                    FROM movimientos m
                    INNER JOIN productos p ON m.productos_id = p.productos_id
                    LEFT JOIN usuarios u ON m.usuarios_id = u.usuarios_id
                    WHERE 1=1";

            $params = [];

            if ($fechaInicio) {
                $sql .= " AND DATE(m.movimientos_fecha) >= ?";
                $params[] = $fechaInicio;
            }

            if ($fechaFin) {
                $sql .= " AND DATE(m.movimientos_fecha) <= ?";
                $params[] = $fechaFin;
            }

            if ($tipo && in_array($tipo, ['ENTRADA', 'SALIDA'])) {
                $sql .= " AND m.movimientos_tipo = ?";
                $params[] = $tipo;
            }

            if ($productoId) {
                $sql .= " AND m.productos_id = ?";
                $params[] = $productoId;
            }

            $sql .= " ORDER BY m.movimientos_fecha DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $movimientos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Crear Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Movimientos');

            // Configurar encabezados
            $headers = ['ID', 'Fecha', 'Producto', 'Código', 'Tipo', 'Cantidad', 'Unidad', 'Stock Histórico', 'Motivo', 'Comentario', 'Usuario'];
            $columna = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($columna . '1', $header);
                $columna++;
            }

            // Estilo para encabezados
            $sheet->getStyle('A1:K1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1e293b']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);

            // Llenar datos
            $fila = 2;
            foreach ($movimientos as $mov) {
                $sheet->setCellValue('A' . $fila, $mov['movimientos_id']);
                $sheet->setCellValue('B' . $fila, date('d/m/Y H:i', strtotime($mov['movimientos_fecha'])));
                $sheet->setCellValue('C' . $fila, $mov['productos_nombre']);
                $sheet->setCellValue('D' . $fila, $mov['productos_codigo']);
                $sheet->setCellValue('E' . $fila, $mov['movimientos_tipo']);
                $sheet->setCellValue('F' . $fila, (int)$mov['movimientos_cantidad']);
                $sheet->setCellValue('G' . $fila, $mov['productos_unidad']);
                $sheet->setCellValue('H' . $fila, (int)$mov['movimientos_stock_historico']);
                $sheet->setCellValue('I' . $fila, $mov['movimientos_motivo']);
                $sheet->setCellValue('J' . $fila, $mov['movimientos_comentario'] ?: '-');
                $sheet->setCellValue('K' . $fila, $mov['usuarios_nombre'] ?: 'Sistema');

                // Color según tipo
                $color = $mov['movimientos_tipo'] === 'ENTRADA' ? 'd4edda' : 'f8d7da';
                $sheet->getStyle('E' . $fila)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($color);

                $fila++;
            }

            // Ajustar anchos de columna
            foreach (range('A', 'K') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Bordes
            $sheet->getStyle('A1:K' . ($fila - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ]);

            // Generar archivo
            $filename = 'movimientos_' . date('Y-m-d_His') . '.xlsx';
            
            // Headers CORS primero
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-API-Key, x-api-key, X-User-Id, Authorization');
            header('Access-Control-Expose-Headers: Content-Type, Content-Disposition');
            
            // Headers para descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            
            $this->logger->info("Reporte de movimientos exportado: {$filename}");
            exit;

        } catch (\Exception $e) {
            $this->logger->error("Error exportando movimientos: " . $e->getMessage());
            $response = ResponseHelper::error(
                ['Error al generar reporte', $e->getMessage()],
                null
            );
            ResponseHelper::send($app, $response, 500);
        }
    }

    /**
     * Exportar productos a Excel
     */
    public function exportarProductos($app)
    {
        try {
            $request = $app->request;
            $stockMinimo = $request->get('stock_minimo');
            $estado = $request->get('estado');

            $sql = "SELECT * FROM productos WHERE 1=1";
            $params = [];

            if ($stockMinimo !== null) {
                $sql .= " AND productos_stock <= ?";
                $params[] = $stockMinimo;
            }

            if ($estado !== null) {
                $sql .= " AND productos_estado = ?";
                $params[] = $estado;
            }

            $sql .= " ORDER BY productos_nombre ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Crear Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Productos');

            // Encabezados
            $headers = ['ID', 'Código', 'Nombre', 'Unidad', 'Precio', 'Stock', 'Estado'];
            $columna = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($columna . '1', $header);
                $columna++;
            }

            // Estilo encabezados
            $sheet->getStyle('A1:G1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e293b']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            // Datos
            $fila = 2;
            foreach ($productos as $prod) {
                $sheet->setCellValue('A' . $fila, $prod['productos_id']);
                $sheet->setCellValue('B' . $fila, $prod['productos_codigo']);
                $sheet->setCellValue('C' . $fila, $prod['productos_nombre']);
                $sheet->setCellValue('D' . $fila, $prod['productos_unidad']);
                $sheet->setCellValue('E' . $fila, 'S/ ' . number_format((float)$prod['productos_precio'], 2));
                $sheet->setCellValue('F' . $fila, (int)$prod['productos_stock']);
                $sheet->setCellValue('G' . $fila, $prod['productos_estado'] == 1 ? 'Activo' : 'Inactivo');
                $fila++;
            }

            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'productos_' . date('Y-m-d_His') . '.xlsx';
            
            // Headers CORS
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-API-Key, x-api-key, X-User-Id, Authorization');
            header('Access-Control-Expose-Headers: Content-Type, Content-Disposition');
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            
            $this->logger->info("Reporte de productos exportado: {$filename}");
            exit;

        } catch (\Exception $e) {
            $this->logger->error("Error exportando productos: " . $e->getMessage());
            $response = ResponseHelper::error(['Error al generar reporte', $e->getMessage()], null);
            ResponseHelper::send($app, $response, 500);
        }
    }

    /**
     * Exportar ventas a Excel
     */
    public function exportarVentas($app)
    {
        try {
            $request = $app->request;
            $fechaInicio = $request->get('fecha_inicio');
            $fechaFin = $request->get('fecha_fin');

            $sql = "SELECT 
                        v.ventas_id,
                        v.ventas_fecha,
                        v.ventas_total,
                        v.cliente_nombre,
                        v.observaciones,
                        dv.detalle_id,
                        dv.productos_id,
                        p.productos_nombre,
                        p.productos_codigo,
                        dv.detalle_cantidad,
                        dv.detalle_precio,
                        dv.detalle_subtotal
                    FROM ventas v
                    INNER JOIN detalle_ventas dv ON v.ventas_id = dv.ventas_id
                    INNER JOIN productos p ON dv.productos_id = p.productos_id
                    WHERE 1=1";

            $params = [];

            if ($fechaInicio) {
                $sql .= " AND DATE(v.ventas_fecha) >= ?";
                $params[] = $fechaInicio;
            }

            if ($fechaFin) {
                $sql .= " AND DATE(v.ventas_fecha) <= ?";
                $params[] = $fechaFin;
            }

            $sql .= " ORDER BY v.ventas_fecha DESC, v.ventas_id DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $ventas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Crear Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Ventas');

            // Encabezados
            $headers = ['Venta ID', 'Fecha', 'Cliente', 'Producto', 'Código', 'Cantidad', 'Precio Unit.', 'Subtotal', 'Total Venta', 'Observaciones'];
            $columna = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($columna . '1', $header);
                $columna++;
            }

            $sheet->getStyle('A1:J1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e293b']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            // Datos
            $fila = 2;
            foreach ($ventas as $venta) {
                $sheet->setCellValue('A' . $fila, $venta['ventas_id']);
                $sheet->setCellValue('B' . $fila, date('d/m/Y H:i', strtotime($venta['ventas_fecha'])));
                $sheet->setCellValue('C' . $fila, $venta['cliente_nombre']);
                $sheet->setCellValue('D' . $fila, $venta['productos_nombre']);
                $sheet->setCellValue('E' . $fila, $venta['productos_codigo']);
                $sheet->setCellValue('F' . $fila, (int)$venta['detalle_cantidad']);
                $sheet->setCellValue('G' . $fila, 'S/ ' . number_format((float)$venta['detalle_precio'], 2));
                $sheet->setCellValue('H' . $fila, 'S/ ' . number_format((float)$venta['detalle_subtotal'], 2));
                $sheet->setCellValue('I' . $fila, 'S/ ' . number_format((float)$venta['ventas_total'], 2));
                $sheet->setCellValue('J' . $fila, $venta['observaciones'] ?: '-');
                $fila++;
            }

            foreach (range('A', 'J') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'ventas_' . date('Y-m-d_His') . '.xlsx';
            
            // Headers CORS
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-API-Key, x-api-key, X-User-Id, Authorization');
            header('Access-Control-Expose-Headers: Content-Type, Content-Disposition');
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            
            $this->logger->info("Reporte de ventas exportado: {$filename}");
            exit;

        } catch (\Exception $e) {
            $this->logger->error("Error exportando ventas: " . $e->getMessage());
            $response = ResponseHelper::error(['Error al generar reporte', $e->getMessage()], null);
            ResponseHelper::send($app, $response, 500);
        }
    }
}
