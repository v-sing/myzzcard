<?php
function  crul_post($url,$data){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_exec ($ch);
	curl_close ($ch);
}
$url = 'http://localhost/app/zzkp.php/Pos/Time/index';
$data = ['method'=>'undoHotelAuthorization','key'=>md5(md5(md5('timetaskundoHotelAuthorization')))];
$data = json_encode($data);
crul_post($url,$data);
file_put_contents('clear.txt',date("Y-m-d H:i:s")."\r\n",FILE_APPEND);
?>
