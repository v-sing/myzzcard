<?php
function  crul_post($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
	curl_exec ($ch);
	curl_close ($ch);
}

$url = 'http://10.1.1.37/zzkp.php/Timer/panterTradeDaily';

crul_post($url);
?>
