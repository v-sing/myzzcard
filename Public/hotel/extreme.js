$(function () {

	obj = {
		search : function () {
			$('#box').datagrid('load', {
				start : $.trim($('input[name="start"]').val()),
				end : $('input[name="end"]').val(),
				cardno : $('input[name="cardno"]').val(),
				panterid : $('input[name="panterid"]').val(),
				pname : $('input[name="pname"]').val(),
				cuname : $('input[name="cuname"]').val(),
				linktel : $('input[name="linktel"]').val(),
				tradetype : $('#tradetype').combobox('getValue')
			});
		},
	};
	$('#box').datagrid({
		width : '100%',
		url : 'getExtremeConsumeList',
		title : '酒店至尊卡消费明细报表',
		striped : true,
		nowrap : true,
		rownumbers : true,
		singleSelect : true,
		fitColumns : true,
		showFooter : true,
		columns : [[
			{
				field : 'cardno',
				title : '卡号',
				sortable : true,
			},
			{
				field : 'cuname',
				title : '会员名字',
				sortable : true,
			},
			{
				field : 'linktel',
				title : '联系电话',
				sortable : true,
			},
			{
				field : 'flag',
				title : '交易状态',
				sortable : true,
			},
			{
				field : 'pname',
				title : '商户名称',
				sortable : true,
			},
			{
				field : 'pname1',
				title : '卡所属机构',
				sortable : true,
			},
			{
				field : 'termposno',
				title : '终端号',
				sortable : true,
			},
			{
				field : 'tradetype',
				title : '交易类型',
				sortable : true,
			},
			{
				field : 'tradetime',
				title : '交易时间',
				sortable : true,
			},
			{
				field : 'tradeid',
				title : '交易流水号',
				sortable : true,
			},
			{
				field : 'quanname',
				title : '消费券',
				sortable : true,
			},
			{
				field : 'tradepoint',
				title : '消费通宝',
				sortable : true,
			},
			{
				field : 'tradeamount',
				title : '交易金额(券数量)',
				sortable : true,
			},
			{
				field : 'qprice',
				title : '券单价',
				sortable : true,
			},
			{
				field : 'zprice',
				title : '券总价',
				sortable : true,
			},
			{
				field : 'addpoint',
				title : '产生通宝',
				sortable : true,
			},
			{
				field : 'auditstatus',
				title : '审核状态',
				sortable : true,
			},
			{
				field : 'remark',
				title : '审核备注',
				sortable : true,
			},
		]],
		toolbar : '#tb',
		pagination : true,
		pageSize : 20,
		pageList : [20, 40, 60],
		pageNumber : 1,
		sortName : 'date',
		sortOrder : 'DESC'
	});

});
