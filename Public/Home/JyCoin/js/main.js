//顶部高度固定浮动
$(document).ready(function() {
    var topHeight=$(".header").height();
    console.log(topHeight);
    $(document).scroll(function() {
        var top = $(document).scrollTop();
        if (top < topHeight) {
            console.log(topHeight);
            $(".item-menu").css({"top":"0.4rem","position":"absolute"});
        } else {
            console.log(topHeight+1);
            $(".item-menu").css({"top":"0","position":"fixed"});
        }
    });
    $(".housing-resources").click(function(){
        window.location.href="./house-source-list.html";
    });
    $(".collection").click(function(){
        window.location.href="./collection-list.html";
    });
    $(".orders").click(function(){
        window.location.href="./order-list.html";
    });
});