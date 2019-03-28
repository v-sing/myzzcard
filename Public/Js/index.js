/*资讯-添加*/
function article_add(title,url){
	var index = layer.open({
		type: 2,
		title: title,
		content: url
	});
	layer.full(index);
}
/*图片-添加*/
function picture_add(title,url){
	var index = layer.open({
		type: 2,
		title: title,
		content: url
	});
	layer.full(index);
}
/*产品-添加*/
function product_add(title,url){
	var index = layer.open({
		type: 2,
		title: title,
		content: url
	});
	layer.full(index);
}
/*用户-添加*/
function member_add(title,url,w,h){
	layer_show(title,url,w,h);
}

$('#menu_seller').click(function(){
	$('.menu_dropdown').hide();
	$('#seller').show();
	$(this).parent().prop('class','current').siblings().prop('class','');
});

$('#menu_users').click(function(){
	$('.menu_dropdown').hide();
	$('#users').show();
	$(this).parent().prop('class','current').siblings().prop('class','');
});

$('#menu_cards').click(function(){
	$('.menu_dropdown').hide();
	$('#cards').show();
	$(this).parent().prop('class','current').siblings().prop('class','');
});

$('#menu_summary').click(function(){
	$('.menu_dropdown').hide();
	$('#summary').show();
	$(this).parent().prop('class','current').siblings().prop('class','');
});
$('#menu_hotel').click(function(){
	$('.menu_dropdown').hide();
	$('#hotel').show();
	$(this).parent().prop('class','current').siblings().prop('class','');
});

$('#menu_system').click(function(){
	$('.menu_dropdown').hide();
	$('#system').show();
	$(this).parent().prop('class','current').siblings().prop('class','');
});
$('#menu_tongbao').click(function(){
	$('.menu_dropdown').hide();
	$('#tongbao').show();
	$(this).parent().prop('class','current').siblings().prop('class','');
});
$('#menu_catering').click(function(){
    $('.menu_dropdown').hide();
    $('#catering').show();
    $(this).parent().prop('class','current').siblings().prop('class','');
});
$('#menu_fang').click(function(){
	$('.menu_dropdown').hide();
	$('#fang').show();
	$(this).parent().prop('class','current').siblings().prop('class','');
});
$(function () {
    $('#excel').on('click',function () {
        var excelTitle =$('#title').html();
        var  TH        = {};
        var data       = {};
        $.each($('#thead th'),function (index,val) {

            TH[index] = $(this).text();

        });
        $.each($('#tbody tr'),function (index,val) {

            var item = {};
            $.each($(this).children("td"),function (index,cval) {
                if(index<8){
                    item[index] = ($(this).text()).replace(/\s+/g,'');
                }
            });
            if(!$.isEmptyObject(item)){
                data[index] = item;
            }

        });

        DownLoadFile({
            url : urlExcel,
            data:{
                th:TH,
                data:data,
                title:excelTitle
            },
            method:'get'
        });
    });


    var DownLoadFile = function (options) {
        var config = $.extend(true, { method: 'post' }, options);
        var $iframe = $('<iframe id="down-file-iframe" />');
        var $form = $('<form target="down-file-iframe" method="' + config.method + '" />');
        $form.attr('action', config.url);
        for (var key in config.data) {
            if(key == "th"){

                $.each(config.data[key],function (index,val) {
                    $form.append('<input type="hidden" name="' + key + '[]" value="' + val + '" />');
                });
            }
            if(key == "data"){
                $.each(config.data[key],function (index,val) {
                    $.each(val,function (i,v) {
                        $form.append('<input type="hidden" name="' + key + '[]" value="' + v + '" />');
                    });
                });
            }else{
                $form.append('<input type="hidden" name="' + key + '[]" value="' + config.data[key] + '" />');
            }

        }
        $iframe.append($form);
        $(document.body).append($iframe);
        $form[0].submit();
        $iframe.remove();
    }
});