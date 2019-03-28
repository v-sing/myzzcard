$(function () {

	$('#box').datagrid({
		width : '100%',
		url : 'getExtremeConsumeList',
		title : '酒店至尊卡消费明细报表',
		iconCls : 'icon-search',
		columns : [[
			{
				field : 'cardno',
				title : '卡号',
				sortable : true,
			},
			{
				field : 'cuname',
				title : '会员名称',
				sortable : true,
			},
			{
				field : 'flag',
				title : '交易状态',
				sortable : true,
			},
			{
				field : 'tradeamount',
				title : '消费金额',
				sortable : true,
			},
			{
				field : 'tradetime',
				title : '交易时间',
				sortable : true,
			},
			{
				field : 'tradetime',
				title : '交易类型',
				sortable : true,
			},
			{
				field : 'tradetime',
				title : '流水号',
				sortable : true,
			},
			{
				field : 'panterid1',
				title : '卡所属商户编号',
				sortable : true,
			},
			{
				field : 'pname1',
				title : '卡所属商户名称',
				sortable : true,
			},
			{
				field : 'panterid',
				title : '消费商户编号',
				sortable : true,
			},
			{
				field : 'pname',
				title : '消费商户',
				sortable : true,
			},
		]],
		pagination : true,
		pageSize : 10,
		pageList : [10, 20, 30],
		pageNumber : 1,
		pagePosition : 'bottom',
		sortName : 'date',
		sortOrder : 'DESC',
		remoteSort : false,
		//multiSort : true,
		//method : 'get',
		queryParams : {
			id : 1,
		}
	});

});
