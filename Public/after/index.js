$(function () {
  obj = {
		search : function () {
			$('#box').datagrid('load', {
				cardno : $('input[name="cardno"]').val(),
			});
		},
	};
	$('#box').datagrid({
		width : '95%',
		//url : 'content.json',
		url : 'cancleCondition',
		title : '充值撤销',
		iconCls : 'icon-search',
		striped : true,
		nowrap : true,
		rownumbers : true,
		singleSelect : false,
		fitColumns : true,
		columns : [[
			{
				field : 'purchaseid',
				title : '流水号',
				align : 'center',
				width: 100,
				sortable : true,
			},
			{
				field : 'cardno',
				title : '卡号',
				align : 'center',
				width: 100,
				sortable : true,
			},
			{
				field : 'customid',
				title : '会员编号',
				align : 'center',
				width: 100,
				sortable : true,
			},
			{
				field : 'namechinese',
				title : '会员名',
				align : 'center',
				width: 100,
				sortable : true,
			},
			{
				field : 'linktel',
				title : '联系电话',
				align : 'center',
				width: 100,
				sortable : true,
			},
			{
				field : 'amount',
				title : '充值金额',
				align : 'center',
				width: 100,
				sortable : true,
			},
			{
				field : 'cplaceddate',
				title : '审核日期',
				align : 'center',
				width: 100,
				sortable : true,
			},
			{
				field : 'cplacedtime',
				title : '审核时间',
				align : 'center',
				width: 100,
				sortable : true,
			},
			{
				field : 'placeddate',
				title : '充值日期',
				align : 'center',
				width: 100,
				sortable : true,
			},
			{
				field : 'placedtime',
				title : '充值时间',
				width: 100,
				align : 'center',
				sortable : true,
			},
			{
				field : 'pur',
				title : '操作',
				width: 100,
				align : 'center',
				sortable : true,
				formatter: function (val, rowdata, index){
					if(1==1){
						return '<button value='+val+' onclick="cancle(this,'+val+')" id="pur">' +'撤销'+ '</button>';
					}
				}
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
	function onb(){
	alert(11111);
}
});
