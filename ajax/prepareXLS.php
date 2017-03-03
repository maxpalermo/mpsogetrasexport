<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(__FILE__).'/../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../init.php');

$content = Tools::getValue("content");
$url = "xls" . DIRECTORY_SEPARATOR .  "sogetras_export_" . date('YmdHis') . ".xls";
$file    = dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $url;

$handle = fopen($file,"w");
$file_content = $content;
fwrite($handle, $file_content);
fclose($handle);
chmod($file, 0775);
/*
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename='.basename($file));
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
ob_clean();
flush();
readfile($file);
 */

print "../modules/mpsogetrasexport/" . $url;

exit();