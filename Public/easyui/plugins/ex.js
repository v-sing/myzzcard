$.extend($.fn.validatebox.defaults.rules, {   
   eqpassword: {   
        validator: function(value,param){   
            return value == $(param[0]).val();   
        },   
        message: '密码不一致!!'  
    }   
    //接着在这里扩展
}); 
 
   //将form表单的值序列化为一个对象
 //接下来是扩展datetimebix
 $.extend($.fn.datagrid.defaults.editors, {   
    datetimebox: {   
        init: function(container, options){   
            var editor = $('<input />').appendTo(container);   
            options.editable=false;
            editor.datetimebox(options);
            return editor;   
        },   
        getValue: function(target){   
            return $(target).datetimebox('getValue');   
        },   
        setValue: function(target, value){   
            $(target).datetimebox('setValue',value);   
        },   
        resize: function(target, width){   
           $(target).datetimebox('resize',width);
        }   ,
        destroy: function(target){   
           $(target).datetimebox('destroy');
        }   
    }   
});    
//扩展编辑一个文件的上传
$.extend($.fn.datagrid.defaults.editors, {   
    file: {   
        init: function(container, options){   
            var input = $('<input type="file" class="datagrid-editable-input" enctype="multipart/form-data" >').appendTo(container);   
            return input;   
        },   
        getValue: function(target){   
            return $(target).val();   
        },   
        setValue: function(target, value){   
            $(target).val(value);   
        },   
        resize: function(target, width){   
            var input = $(target);   
            if ($.boxModel == true){   
                input.width(width - (input.outerWidth() - input.width()));   
            } else {   
                input.width(width);   
            }   
        }   
    }   
});