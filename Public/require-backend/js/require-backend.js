require.config({
    urlArgs: "v="+requirejs.s.contexts._.config.config.site.version,
    packages: [{
        name: 'moment',
        location: '/Public/require-backend/libs/moment',
        main: 'moment'
    }
    ],
    //在打包压缩时将会把include中的模块合并到主文件中
    include: ['css', 'layer', 'toastr', 'fast', 'backend', 'backend-init', 'table', 'form', 'dragsort', 'drag', 'drop', 'addtabs', 'selectpage'],
    paths: {
        'lang': "empty:",
        'form': '/Public/require-backend/js/require-form',
        'table': '/Public/require-backend/js/require-table',
        'upload': '/Public/require-backend/js/require-upload',
        'validator': '/Public/require-backend/js/require-validator',
        'drag': '/Public/require-backend/js/jquery.drag.min',
        'drop': '/Public/require-backend/js/jquery.drop.min',
        'echarts': '/Public/require-backend/js/echarts.min',
        'echarts-theme': '/Public/require-backend/js/echarts-theme',
        'adminlte': '/Public/require-backend/js/adminlte',
        'bootstrap-table-commonsearch': '/Public/require-backend/js/bootstrap-table-commonsearch',
        'bootstrap-table-template': '/Public/require-backend/js/bootstrap-table-template',

        //
        // 以下的包从bower的libs目录加载
        'jquery': '/Public/require-backend/libs/jquery/dist/jquery.min',
        'bootstrap': '/Public/require-backend/libs/bootstrap/dist/js/bootstrap.min',
        'bootstrap-datetimepicker': '/Public/require-backend/libs/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min',
        'bootstrap-daterangepicker': '/Public/require-backend/libs/bootstrap-daterangepicker/daterangepicker',
        'bootstrap-select': '/Public/require-backend/libs/bootstrap-select/dist/js/bootstrap-select.min',
        'bootstrap-select-lang': '/Public/require-backend/libs/bootstrap-select/dist/js/i18n/defaults-zh_CN',
        'bootstrap-table': '/Public/require-backend/libs/bootstrap-table/dist/bootstrap-table.min',
        'bootstrap-table-export': '/Public/require-backend/libs/bootstrap-table/dist/extensions/export/bootstrap-table-export.min',
        'bootstrap-table-mobile': '/Public/require-backend/libs/bootstrap-table/dist/extensions/mobile/bootstrap-table-mobile',
        'bootstrap-table-lang': '/Public/require-backend/libs/bootstrap-table/dist/locale/bootstrap-table-zh-CN',
        'tableexport': '/Public/require-backend/libs/tableExport.jquery.plugin/tableExport.min',
        'dragsort': '/Public/require-backend/libs/fastadmin-dragsort/jquery.dragsort',
        'sortable': '/Public/require-backend/libs/Sortable/Sortable.min',
        'addtabs': '/Public/require-backend/libs/fastadmin-addtabs/jquery.addtabs',
        'slimscroll': '/Public/require-backend/libs/jquery-slimscroll/jquery.slimscroll',
        'validator-core': '/Public/require-backend/libs/nice-validator/dist/jquery.validator',
        'validator-lang': '/Public/require-backend/libs/nice-validator/dist/local/zh-CN',
        'plupload': '/Public/require-backend/libs/plupload/js/plupload.min',
        'toastr': '/Public/require-backend/libs/toastr/toastr',
        'jstree': '/Public/require-backend/libs/jstree/dist/jstree.min',
        'layer': '/Public/require-backend/libs/fastadmin-layer/dist/layer',
        'cookie': '/Public/require-backend/libs/jquery.cookie/jquery.cookie',
        'cxselect': '/Public/require-backend/libs/fastadmin-cxselect/js/jquery.cxselect',
        'template': '/Public/require-backend/libs/art-template/dist/template-native',
        'selectpage': '/Public/require-backend/libs/fastadmin-selectpage/selectpage',
        'citypicker': '/Public/require-backend/libs/fastadmin-citypicker/dist/js/city-picker.min',
        'citypicker-data': '/Public/require-backend/libs/fastadmin-citypicker/dist/js/city-picker.data',
    },
    // shim依赖配置
    shim: {
        'addons': ['backend'],
        'bootstrap': ['jquery'],
        'bootstrap-table': {
            deps: [
                'bootstrap',
               'css!/Public/require-backend/libs/bootstrap-table/dist/bootstrap-table.min.css'
            ],
            exports: '$.fn.bootstrapTable'
        },
        'bootstrap-table-lang': {
            deps: ['bootstrap-table'],
            exports: '$.fn.bootstrapTable.defaults'
        },
        'bootstrap-table-export': {
            deps: ['bootstrap-table', 'tableexport'],
            exports: '$.fn.bootstrapTable.defaults'
        },
        'bootstrap-table-mobile': {
            deps: ['bootstrap-table'],
            exports: '$.fn.bootstrapTable.defaults'
        },
        'bootstrap-table-advancedsearch': {
            deps: ['bootstrap-table'],
            exports: '$.fn.bootstrapTable.defaults'
        },
        'bootstrap-table-commonsearch': {
            deps: ['bootstrap-table'],
            exports: '$.fn.bootstrapTable.defaults'
        },
        'bootstrap-table-template': {
            deps: ['bootstrap-table', 'template'],
            exports: '$.fn.bootstrapTable.defaults'
        },
        'tableexport': {
            deps: ['jquery'],
            exports: '$.fn.extend'
        },
        'slimscroll': {
            deps: ['jquery'],
            exports: '$.fn.extend'
        },
        'adminlte': {
            deps: ['bootstrap', 'slimscroll'],
            exports: '$.AdminLTE'
        },
        'bootstrap-datetimepicker': [
            'moment/locale/zh-cn',
//            'css!/Public/require-backend/libs/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css',
        ],
//        'bootstrap-select': ['css!/Public/require-backend/libs/bootstrap-select/dist/css/bootstrap-select.min.css',],
        'bootstrap-select-lang': ['bootstrap-select'],
//        'toastr': ['css!/Public/require-backend/libs/toastr/toastr.min.css'],
        'jstree': ['css!/Public/require-backend/libs/jstree/dist/themes/default/style.css',],
        'plupload': {
            deps: ['/Public/require-backend/libs/plupload/js/moxie.min.js'],
            exports: "plupload"
        },
//        'layer': ['css!/Public/require-backend/libs/fastadmin-layer/dist/theme/default/layer.css'],
//        'validator-core': ['css!/Public/require-backend/libs/nice-validator/dist/jquery.validator.css'],
        'validator-lang': ['validator-core'],
//        'selectpage': ['css!/Public/require-backend/libs/fastadmin-selectpage/selectpage.css'],
        'citypicker': ['citypicker-data', 'css!/Public/require-backend/libs/fastadmin-citypicker/dist/css/city-picker.css']
    },
    baseUrl: requirejs.s.contexts._.config.config.site.cdnurl + '/Public/require-backend/js/', //资源基础路径
    map: {
        '*': {
            'css': '/Public/require-backend/libs/require-css/css.min.js'
        }
    },

    charset: 'utf-8' // 文件编码
});

require(['jquery', 'bootstrap'], function ($, undefined) {
    //初始配置
    var Config = requirejs.s.contexts._.config.config;
    //将Config渲染到全局
    window.Config = Config;
    // 配置语言包的路径
    var paths = {};
    paths['lang'] = Config.fastadmin.api_url + 'zzkp.php/ajax/lang?callback=define&controllername=' + Config.controllername;
    // 避免目录冲突
    paths['backend/'] = 'backend/';
    require.config({paths: paths});

    // 初始化
    $(function () {
        require(['fast'], function (Fast) {
            require(['backend', 'backend-init', 'addons'], function (Backend, undefined, Addons) {
                //加载相应模块
                if (Config.jsname) {
                    require([Config.jsname], function (Controller) {
                        Controller[Config.actionname] != undefined && Controller[Config.actionname]();
                    }, function (e) {
                        console.log(e)
                        console.error(e);
                        // 这里可捕获模块加载的错误
                    });
                }
            });
        });
    });
});
