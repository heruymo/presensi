<?php
// admin/export_users.php
require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

require __DIR__.'/../includes/session.php';
require __DIR__.'/../includes/auth.php'; require_role('admin');
require __DIR__.'/../includes/db.php';

$sheet=new Spreadsheet();
$ws=$sheet->getActiveSheet();
$ws->setTitle('Users');
$hdr=['ID','Username','Nama','Role','Created'];
$c='A';
foreach($hdr as $h){
  $ws->setCellValue("$c"."1",$h);
  $ws->getStyle("$c"."1")->getFont()->setBold(true);
  $ws->getColumnDimension($c)->setAutoSize(true);
  $c++;
}

$sql="SELECT id,username,nama,role,created_at FROM users";
$data=$pdo->query($sql)->fetchAll();

$r=2;
foreach($data as $u){
  $ws->setCellValue("A{$r}",$u['id']);
  $ws->setCellValue("B{$r}",$u['username']);
  $ws->setCellValue("C{$r}",$u['nama']);
  $ws->setCellValue("D{$r}",$u['role']);
  $ws->setCellValue("E{$r}",$u['created_at']);
  $r++;
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="users.xls"');
$w=new Xls($sheet);
$w->save('php://output');
exit;