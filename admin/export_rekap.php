<?php
// admin/export_rekap.php
require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

require __DIR__.'/../includes/session.php';
require __DIR__.'/../includes/auth.php'; require_role('admin');
require __DIR__.'/../includes/db.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap');

$hdr = ['Nama','Tanggal','Masuk','Pulang','Foto Masuk'];
$col='A';
foreach($hdr as $h){
  $sheet->setCellValue($col.'1',$h);
  $sheet->getStyle($col.'1')->getFont()->setBold(true);
  $sheet->getColumnDimension($col)->setAutoSize(true);
  $col++;
}

$sql = "
  SELECT u.nama, DATE(a.timestamp) AS tanggal,
    MAX(CASE WHEN a.type='masuk'  THEN TIME(a.timestamp) END) AS masuk,
    MAX(CASE WHEN a.type='pulang' THEN TIME(a.timestamp) END) AS pulang,
    MAX(CASE WHEN a.type='masuk'  THEN a.photo END) AS photo
  FROM attendance a
  JOIN users u ON u.id=a.user_id
  GROUP BY u.nama, DATE(a.timestamp)
  ORDER BY tanggal DESC
";
$data = $pdo->query($sql)->fetchAll();

$r=2;
foreach($data as $row){
  $sheet->setCellValue("A{$r}",$row['nama']);
  $sheet->setCellValue("B{$r}",$row['tanggal']);
  $sheet->setCellValue("C{$r}",$row['masuk']  ?? '-');
  $sheet->setCellValue("D{$r}",$row['pulang'] ?? '-');
  $sheet->setCellValue("E{$r}",$row['photo']  ?? '-');
  $r++;
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="rekap.xls"');
$writer = new Xls($spreadsheet);
$writer->save('php://output');
exit;