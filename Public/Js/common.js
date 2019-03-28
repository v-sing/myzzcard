/**
 * Created with JetBrains PhpStorm.
 * User: kk
 * Date: 13-8-28
 * Time: 下午4:44
 */
function U() {
    var url = arguments[0] || [];
    var param = arguments[1] || {};
    var url_arr = url.split('/');

    if (!$.isArray(url_arr) || url_arr.length < 2 || url_arr.length > 3) {
        return '';
    }

    if (url_arr.length == 2)
        url_arr.unshift(_GROUP_);

    var pre_arr = ['g', 'm', 'a'];

    var arr = [];
    for (d in pre_arr)
        arr.push(pre_arr[d] + '=' + url_arr[d]);

    for (d in param)
        arr.push(d + '=' + param[d]);

    return _APP_+'?'+arr.join('&');
}
/**
 * province.
 * User: daxing
 * Date: 15-8-16
 * Time: 下午4:44
 */
function loadRegion(sel,type_id,selName,url){
    jQuery("#"+selName+" option").each(function(){
        jQuery(this).remove();
    });
    jQuery("<option value=0>请选择</option>").appendTo(jQuery("#"+selName));
    if(type_id==2){
        jQuery("#town").empty();
        jQuery("<option value=0>请选择</option>").appendTo(jQuery("#town"));
    }
    if(jQuery("#"+sel).val()==0){
        return;
    }
    jQuery.getJSON(url,{pid:jQuery("#"+sel).val(),type:type_id},
        function(data){
            if(data){
                jQuery.each(data,function(idx,item){
                    jQuery("<option value="+item.cityid+">"+item.cityname+"</option>").appendTo(jQuery("#"+selName));
                });
            }else{
                jQuery("<option value='0'>请选择</option>").appendTo(jQuery("#"+selName));
            }
        }
    );
}
/*清除“数字”和“.”以外的字符*/
function clearNoNum(obj){
    obj.value = obj.value.replace(/[^\d.]/g,"");  //清除“数字”和“.”以外的字符
    obj.value = obj.value.replace(/^\./g,"");  //验证第一个字符是数字而不是.
    obj.value = obj.value.replace(/\.{2,}/g,"."); //只保留第一个. 清除多余的.
    obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
    obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3');
}
/*清除输入框里的数字以后的字符*/
function intOnly(obj){
    obj.value=obj.value.replace(/[^\d]/g,'');
}

