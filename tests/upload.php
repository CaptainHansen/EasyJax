<?
include("../EasyJaxFiles.php");

$ejf = new EasyJaxFiles();
$ejf -> downloadTo("/tmp/");
$ejf -> send_resp();