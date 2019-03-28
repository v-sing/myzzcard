<?php
namespace Org\Util;
class Des{
	private $key;
	public function __construct($key){
		if (empty($key)) {
			echo 'key and iv is not valid';
			exit();
		}
		$this->key = $key;
		$this->keyLen= strlen($key);
	}

	//加密数据初始化
	public function doEncrypt($str){
		$len = strlen($str);
		//$str = self::PaddingPKCS7($str);
		$data = self::keyPack($str, $len);
		$key = self::keyPack($this->key, $this->keyLen);
		$result = self::encrypt($key, $data);
		$sign = strtoupper(bin2hex($result));
		return $sign;
	}

	//加密
	private function encrypt($key, $data){
		$td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
		$size = mcrypt_enc_get_iv_size($td);
		$iv = mcrypt_create_iv($size, MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$result = mcrypt_generic($td, $data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $result;
	}

	//解密数据初始化
	public function doDecrypt($str){
		$len = strlen($str);
		$data = self::keyPack($str);
		$key = self::keyPack($this->key, $this->keyLen);
		$result = self::decrypt($key, $data);
		$result = bin2hex($result);
		//$result = self::UnPaddingPKCS7($result);
		return $result;
	}

	//解密
	private function decrypt($key, $data){
		$td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
		$size = mcrypt_enc_get_iv_size($td);
		$iv = mcrypt_create_iv($size, MCRYPT_RAND);
		mcrypt_generic_init($td, $key, $iv);
		$result = mdecrypt_generic($td, $data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $result;
	}

	//进行二进制转换
	private function keyPack($key, $len = '*'){
		return pack('H'.$len, $key);
	}

	//填充
	private  function PaddingPKCS7($data) {
		$srcdata = $data;
		$block_size = mcrypt_get_block_size(MCRYPT_3DES,MCRYPT_MODE_ECB);
		$padding_char = $block_size - (strlen($data) % $block_size);
		$srcdata .= str_repeat(chr($padding_char),$padding_char);
		return $srcdata;
	}

	private function UnPaddingPKCS7($text) {
		$pad = ord($text{strlen($text) - 1});
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
			return false;
		}
		return substr($text, 0, -1 * $pad);
	}
}