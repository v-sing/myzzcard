<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/5
 * Time: 15:33
 */

namespace Service;


class ReceiptClient extends BaseService
{
       public $fplxdm;
       public $fpdm;
       public $fphm;
       public $order;
       public $html;
       public $key = "songyun";
       public $parseArr;
       public $fplx;
       public $ip = "http://123.161.209.66:8899";
       public function  __construct($ip=null,$port=null)
       {
                    if(!is_null($ip) && is_null($port)){
                             $this->ip = $ip.":".$port;
                    }
       }


    public function  getKpXml($param=[],$invoiceType=null,$existsSL=null)
      {
            $this->order = $this->getlsh();
            $html = $this->getHeader($this->order,$invoiceType);
            $html .=$this->PanterInfo($param,$existsSL);
            $html .=$this->footer($existsSL);
            $this->html = $html;
            return $this->order;
      }

    /**
     * 获取打印信息
     */
     public function getResultXml()
     {
               $html = base64_encode($this->html);
         if(!in_array($this->fplx,['025','026'])) {
             $htmls = <<<HTML
<?xml version="1.0" encoding="gbk"?>
<business comment="发票开具打印" id="FP_PRINTYTH">
<body yylxdm="1">
<input>
<KPXML>{$html}</KPXML>
<ISPRINT>1</ISPRINT>
<ISPREVIEW>1</ISPREVIEW>
</input>
</body>
</business>
HTML;
         }else{
             $htmls=<<<HTML
<?xml version="1.0" encoding="gbk"?>
<business comment="电子发票开具" id="DZFPKJ">
<body yylxdm="1">
<input>
<DJBH>{$this->order}</DJBH>
<FPXML>{$html}</FPXML>
</input>
</body>
</business>
HTML;
         }



        $this->sendXml($htmls);
        $this->send();
     }

    /**发送XML信息
     * @param $htmls
     */
    public function  sendXml($htmls)
    {
        $html = base64_encode($htmls);
        $xml = $this->filePost($this->ip,$html);
        $xml = base64_decode($xml);
        $parse = $this->simplest_xml_to_array($xml);
        $this->parseArr = $parse['body']['output'];
    }


    /**
     * 打开打印窗口
     */
     public function  send()
     {
         $html = <<<HTML
<?xml version="1.0" encoding="gbk"?>
<business comment="发票打印" id="FP_DY">
<body yylxdm="1">
<input>
  <fplxdm>{$this->fplx}</fplxdm>
  <fpdm>{$this->parseArr['fpdm']}</fpdm>
  <fphm>{$this->parseArr['fphm']}</fphm>
</input>
</body>
</business>
HTML;
         $this->sendXml($html);

     }


      public function  getHeader($order,$invoiceType='004')
      {
           $this->fplx = $invoiceType;
           if(!in_array($invoiceType,['025','026'])){
               $html = <<<HTML
<?xml version="1.0" encoding="gbk"?>
<business comment="发票开具" id="FPKJ">
<body yylxdm="1">
 <input>
<fplxdm>{$this->fplx}</fplxdm>
<fpqqlsh>{$order}</fpqqlsh> 
<kplx>0</kplx>
<tspz>00</tspz>
<xhdwsbh>499906924740</xhdwsbh>
<xhdwmc>郑州建业至尊商务服务有限公司</xhdwmc>
<xhdwdzdh>郑州市金水区建业路城市花园88号 0371-66517055</xhdwdzdh>
 <xhdwyhzh>中国银行 261105186212</xhdwyhzh>
HTML;
           }else{
               $html = <<<HTML
<?xml version="1.0" encoding="gbk"?>
<business id="FPKJ" comment="发票开具">
    <HTJS_DZFPKJ class="HTJS_DZFPKJ">
        <COMMON_FPKJ_FPT class="COMMON_FPKJ_FPT">
            <FPQQLSH>{$order}</FPQQLSH>
            <KPLX>0</KPLX>
            <BMB_BBH>13.0</BMB_BBH>
            <ZSFS>0</ZSFS>
            <XSF_NSRSBH>91410100675355299C</XSF_NSRSBH>
            <XSF_MC>郑州建业至尊商务服务有限公司</XSF_MC>
            <XSF_DZDH>郑州市金水区建业路城市花园88号 0371-66517055</XSF_DZDH>
            <XSF_YHZH>中国银行 261105186212</XSF_YHZH>
            <XSF_LXFS>511416304@qq.com</XSF_LXFS>
HTML;
           }

          return trim($html);
      }
      public function  PanterInfo($param=[],$isHs=null)
      {
          if(!is_null($isHs)){
                     $dj = bcdiv($param['receiptmoney'],1.06,2);
                     $sl = 0.06;
                     $se = bcsub($param['receiptmoney'],$dj,2);
                     $hsbz = 0;
                     $slbs = "";
                    $hjse = $param['receiptmoney'];
                    $param['receiptmoney'] = $dj;
              $bm = 3049900000000000000;
              $mc = "服务费";
          }else{
              $bm = 6010000000000000000;
              $mc = "预付卡销售和充值";
              $dj = $param['receiptmoney'];
              $sl = 0;
              $se = 0;
              $hsbz = 0;
              $slbs = 2;
              $hjse = $param['receiptmoney'];
          }
        if(!in_array($this->fplx,['025','026'])){
            $html = <<<HTML
<ghdwsbh>{$param['panterinfo']['orderno']}</ghdwsbh>
<ghdwmc>{$param['panterinfo']['pantername']}</ghdwmc>
 <ghdwdzdh>{$param['panterinfo']['address']}</ghdwdzdh>
 <ghdwyhzh>{$param['panterinfo']['bank']} {$param['panterinfo']['bankaccount']}</ghdwyhzh>
<bmbbbh>30.0</bmbbbh>
<hsslbs>0</hsslbs>
<fyxm count="1">
 <group xh="1">
 <fphxz>0</fphxz>
 <spmc>{$mc}</spmc>
<spsm></spsm>
<ggxh></ggxh>
<dw></dw>
<spsl>1</spsl>
 <dj>{$dj}</dj>
<je>{$dj}</je>
<kcje></kcje>
          <sl>{$sl}</sl>
          <se>{$se}</se>
          <hsbz>{$hsbz}</hsbz>
          <spbm>{$bm}</spbm>
          <zxbm></zxbm>
          <yhzcbs>0</yhzcbs>
          <slbs>{$slbs}</slbs>
          <zzstsgl></zzstsgl>
        </group>
</fyxm>
       <zhsl></zhsl>
      <hjje>{$param['receiptmoney']}</hjje>
      <hjse>{$se}</hjse>
      <jshj>{$hjse}</jshj>
HTML;
        }else{
             $html = <<<HTML
            <GMF_NSRSBH>{$param['panterinfo']['orderno']}</GMF_NSRSBH>
            <GMF_MC>{$param['panterinfo']['pantername']}</GMF_MC>
            <GMF_DZDH>{$param['panterinfo']['address']}</GMF_DZDH>
            <GMF_YHZH>{$param['panterinfo']['bank']} {$param['panterinfo']['bankaccount']}</GMF_YHZH>
            <GMF_LXFS>{$param['panterinfo']['linktel']}</GMF_LXFS>
            <KPR>李晓青</KPR>
            <FHR>史永华</FHR>
            <YFP_DM></YFP_DM>
            <YFP_HM></YFP_HM>
            <JSHJ>{$hjse}</JSHJ>
            <HJJE>{$dj}</HJJE>
            <HJSE>{$se}</HJSE>
            <KCE></KCE>
            <BZ></BZ>
        </COMMON_FPKJ_FPT>
 <COMMON_FPKJ_XMXXS class="COMMON_FPKJ_XMXX" size="1">
            <COMMON_FPKJ_XMXX>
                <FPHXZ>0</FPHXZ>
                <SPBM>{$bm}</SPBM>
                <ZXBM></ZXBM>
                <YHZCBS>0</YHZCBS>
                <LSLBS></LSLBS>
                <ZZSTSGL></ZZSTSGL>
                <XMMC>{$mc}</XMMC>
                <GGXH></GGXH>
                <DW></DW>
                <XMSL></XMSL>
                <XMDJ></XMDJ>
                <XMJE>{$hjse}</XMJE>
                <SL>{$sl}</SL>
                <SE>{$se}</SE>
            </COMMON_FPKJ_XMXX>
        </COMMON_FPKJ_XMXXS>
    </HTJS_DZFPKJ>
HTML;

        }


         return trim($html);
      }


      public function  footer($invoiceType=null)
      {
          if(!in_array($this->fplx,['025','026'])) {
              $html = <<<HTML
      <bz></bz>
      <skr>李晓青</skr>
      <fhr>吴玉臻</fhr>
      <kpr>史永华</kpr>
      <zyspmc></zyspmc>
      <spsm></spsm>
      <qdbz>0</qdbz>
      <ssyf></ssyf>
      <tzdbh></tzdbh>
      <yfpdm></yfpdm>
      <yfphm></yfphm>
    </input>
  </body>
</business>
HTML;
      }else{
              $html = <<<HTML
</business>
HTML;

}

        return trim($html);
      }


      public function  getlsh()
      {
           $date = date("Ymd");
           $time    = substr(microtime(),0,5) ;
           return $date.$time;
      }



}