<?php
function  crul_post($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
	curl_exec ($ch);
	curl_close ($ch);
}
$url = 'http://localhost/app/zzkp.php/Timer/sendCoupon';
crul_post($url);
file_put_contents('b.txt',date("Y-m-d H:i:s")."\r\n",FILE_APPEND);
?>
