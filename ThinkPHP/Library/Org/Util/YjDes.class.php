<?php
namespace Org\Util;
class YjDes{

  private $appkey;
  private $appid;
  private $version;
  public function __construct($appkey='9a3183942c5831a267cbe4cf1f73a2672c5831a267cbe4cf'){
      $this->appkey = $appkey;
  }

  public function encrypt($data){
     $data = json_encode($data);//转换json
     $data = base64_encode($data);
     $data = md5($data);
     $data = self::PaddingPKCS7($data);
     $appkey = self::keyPack($this->appkey);
     $result = self::doEncrypt($appkey,$data);
     $sign = bin2hex($result);
     return $sign;
  }

  private function doEncrypt($key,$data){
    $td = mcrypt_module_open(MCRYPT_3DES,'',MCRYPT_MODE_ECB,'');
    $size = mcrypt_enc_get_iv_size($td);
    $iv = mcrypt_create_iv($size, MCRYPT_RAND);
    mcrypt_generic_init($td,$key,$iv);
    $result = mcrypt_generic($td,$data);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return $result;
  }

  private function keyPack($key){
    return pack('H*',$key);
  }

 private  function PaddingPKCS7($data) {
  $srcdata = $data;
  $block_size = mcrypt_get_block_size(MCRYPT_3DES,MCRYPT_MODE_ECB);
  $padding_char = $block_size - (strlen($data) % $block_size);
  $srcdata .= str_repeat(chr($padding_char),$padding_char);
  return $srcdata;
}
//
//   private $appkey;
//   private $appid;
//   private $version;
//   public function __construct($appkey,$appid,$version){
//       $this->appkey = $appkey;
//       $this->appid = $appid;
//       $this->version = $version;
//   }
//
//   public function encrypt($data){
//      $data = json_encode($data);//转换json
//      $data = $this->appid.$data;
//      $data = base64_encode($data);
//      $data = md5($data);
//      $data = self::PaddingPKCS7($data);
//      $appkey = self::keyPack($this->appkey);
//      $result = self::doEncrypt($appkey,$data);
//      $sign = bin2hex($result);
//      return $sign;
//   }
//
//   private function doEncrypt($key,$data){
//     $td = mcrypt_module_open(MCRYPT_3DES,'',MCRYPT_MODE_ECB,'');
//     $size = mcrypt_enc_get_iv_size($td);
//     $iv = mcrypt_create_iv($size, MCRYPT_RAND);
//     mcrypt_generic_init($td,$key,$iv);
//     $result = mcrypt_generic($td,$data);
//     mcrypt_generic_deinit($td);
//     mcrypt_module_close($td);
//     return $result;
//   }
//
//   private function keyPack($key){
//     return pack('H*',$key);
//   }
//
//  private  function PaddingPKCS7($data) {
//   $srcdata = $data;
//   $block_size = mcrypt_get_block_size(MCRYPT_3DES,MCRYPT_MODE_ECB);
//   $padding_char = $block_size - (strlen($data) % $block_size);
//   $srcdata .= str_repeat(chr($padding_char),$padding_char);
//   return $srcdata;
// }
//

}



 ?>
