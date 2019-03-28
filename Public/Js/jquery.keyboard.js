$(function() {
    // Variables
    var string = "",
    isActive = 0,
    isShift = 0,
    field = $('#field'),
    keyboard = $('#keyboard'),
    clear = $('#clear'),
    space = $('#space'),
    bspace = $('#bspace'),
    shift = $('#shift');

    // 当密码框获取焦点时，显示虚拟键盘
    field.focusin(function() {
        keyboard.fadeIn('slow');
        string = '';
        field.attr('value', "");
        field.css('background', 'rgba(170,255,86,.3)');
        isActive = 1;
    });

    // 验证码输入框获取焦点时，隐藏虚拟键盘
    $('#verify').focusin(function(){
    	if (isActive) {
            keyboard.fadeOut('slow');
            field.css('background', '#FFF');
            isActive = 0;
        }
    });

    // 点击验证码图片时，隐藏虚拟键盘
    $('#verifyImg').click(function(){
    	if (isActive) {
            keyboard.fadeOut('slow');
            field.css('background', '#FFF');
            isActive = 0;
        }
    });

    // 点击更换验证码文字时，隐藏虚拟键盘
    $('#verifyText').click(function(){
    	if (isActive) {
            keyboard.fadeOut('slow');
            field.css('background', '#FFF');
            isActive = 0;
        }
    });

    $('#close').click(function() {
        if (isActive) {
            keyboard.fadeOut('slow');
            field.css('background', '#FFF');
            isActive = 0;
        }
    });
    // Numbers to Symbols
    function numbersToSymbols() {
        $('#1').html("!");
        $('#2').html("@");
        $('#3').html("#");
        $('#4').html("$");
        $('#5').html("%");
        $('#6').html("^");
        $('#7').html("&");
        $('#8').html("*");
        $('#9').html("(");
        $('#0').html(")");
        $('#-').html("_");
    }
    // Symbols to Numbers 
    function symbolsToNumbers() {
        $('#1').html("1");
        $('#2').html("2");
        $('#3').html("3");
        $('#4').html("4");
        $('#5').html("5");
        $('#6').html("6");
        $('#7').html("7");
        $('#8').html("8");
        $('#9').html("9");
        $('#0').html("0");
        $('#-').html("-");
    }
    // Shift and Clear 
    shift.click(function() {
        if (isShift) {
            $('.char,.number').css("text-transform", "none");
            symbolsToNumbers();
            isShift = 0;
        } else {
            $('.char,.number').css("text-transform", "uppercase");
            numbersToSymbols();
            isShift = 1;
        }
    });
    clear.click(function() {
        string = "";
        field.attr("value", string);
    });
    // Space and BackSpace
    space.click(function() {
        if (isActive) {
            keyboard.fadeOut('slow');
            field.css('background', '#FFF');
            isActive = 0;
            $('#verify').val('');
            $('#verify').focus();
        }
    });
    bspace.click(function() {
        string = string.substring(0, string.length - 1);
        field.attr("value", string);
        field.focus();
    });
    // Char Buttons
    $('.char,.number').click(function() {
        var symbol = $(this).text();
        if (isShift) {
            $('.char,.number').css("text-transform", "none");
            isShift = 0;
            string += symbol.toUpperCase();
            symbolsToNumbers();
        } else {
            string += symbol;
        }
        field.attr("value", string);
    });

    // 禁止键盘输入
    /*function forbidKeyboard(){
    	var value = '';
    	$('#field').keydown(function(){
	    	value = $(this).val();
	    });

	    $('#field').keyup(function(){
	    	if (value.length == 0) {
	    		$(this).val('');
	    	} else {
	    		$(this).val(value.substr(0, value.length));
	    	}
	    });
    }

    forbidKeyboard();*/

})