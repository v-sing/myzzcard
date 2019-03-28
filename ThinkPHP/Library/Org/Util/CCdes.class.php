<?php
namespace Org\Util;
class CCdes
{
     public function encrypt($input, $ky) {
        $key = $ky;
        $iv = $ky;  //$iv为加解密向量
        $size = strlen($iv); //填充块的大小,单位为bite    初始向量iv的位数要和进行pading的分组块大小相等!!!
        $input =$this->pkcs5_pad($input, $size);  //对明文进行字符填充
        $td = mcrypt_module_open(MCRYPT_DES, '', 'cbc', '');    //MCRYPT_DES代表用DES算法加解密;'cbc'代表使用cbc模式进行加解密.
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);    //对$input进行加密
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);   //对加密后的密文进行base64编码
        return $data;
    }

/*
 * 在采用DES加密算法,cbc模式,pkcs5Padding字符填充方式,对密文进行解密函数
 */

    public  function decrypt($crypt, $ky) {
        $crypt = base64_decode($crypt);   //对加密后的密文进行解base64编码
        $key = $ky;
        $iv = $ky;  //$iv为加解密向量
        $td = mcrypt_module_open(MCRYPT_DES, '', 'cbc', '');    //MCRYPT_DES代表用DES算法加解密;'cbc'代表使用cbc模式进行加解密.
        mcrypt_generic_init($td, $key, $iv);
        $decrypted_data = mdecrypt_generic($td, $crypt);    //对$input进行解密
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $decrypted_data = $this->pkcs5_unpad($decrypted_data); //对解密后的明文进行去掉字符填充
        $decrypted_data = rtrim($decrypted_data);   //去空格
        return $decrypted_data;
    }

/*
 * 对明文进行给定块大小的字符填充
 */

   protected function pkcs5_pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

/*
 * 对解密后的已字符填充的明文进行去掉填充字符
 */

  protected  function pkcs5_unpad($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
            return false;
        return substr($text, 0, -1 * $pad);
    }
}
 ?>
