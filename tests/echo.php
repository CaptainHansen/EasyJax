<?
include("../EasyJax.php"); 

$easyj = new EasyJax();
$easyj -> set_ret_data("returned_text",$easyj -> getData('echo_me'));
$easyj -> send_resp();