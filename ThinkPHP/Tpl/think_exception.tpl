<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title>系统发生错误</title>
<style type="text/css">
*{ padding: 0; margin: 0; }
html{ overflow-y: scroll; }
body{ background: #fff; font-family: '微软雅黑'; color: #333; font-size: 16px; }
img{ border: 0; }
.error{ padding: 24px 48px; }
.face{ font-size: 100px; font-weight: normal; line-height: 120px; margin-bottom: 12px; }
h1{ font-size: 32px; line-height: 48px; }
.error .content{ padding-top: 10px}
.error .info{ margin-bottom: 12px; }
.error .info .title{ margin-bottom: 3px; }
.error .info .title h3{ color: #000; font-weight: 700; font-size: 16px; }
.error .info .text{ line-height: 24px; }
.copyright{ padding: 12px 48px; color: #999; }
.copyright a{ color: #000; text-decoration: none; }
</style>

<style>
@import url('https://fonts.googleapis.com/css?family=Montserrat:400,600,700');
@import url('https://fonts.googleapis.com/css?family=Catamaran:400,800');
.error-container {
  text-align: center;
  font-size: 180px;
  font-family: 'Catamaran', sans-serif;
  font-weight: 800;
  margin: 20px 15px;
}
.error-container > span {
  display: inline-block;
  line-height: 0.7;
  position: relative;
  color: #FFB485;
}
.error-container > span {
  display: inline-block;
  position: relative;
  vertical-align: middle;
}
.error-container > span:nth-of-type(1) {
  color: #D1F2A5;
  animation: colordancing 4s infinite;
}
.error-container > span:nth-of-type(3) {
  color: #F56991;
  animation: colordancing2 4s infinite;
}
.error-container > span:nth-of-type(2) {
  width: 120px;
  height: 120px;
  border-radius: 999px;
}
.error-container > span:nth-of-type(2):before,
.error-container > span:nth-of-type(2):after {
	border-radius: 0%;
	content:"";
	position: absolute;
	top: 0; left: 0;
	width: inherit; height: inherit;
  border-radius: 999px;
	box-shadow: inset 30px 0 0 rgba(209, 242, 165, 0.4),
				inset 0 30px 0 rgba(239, 250, 180, 0.4),
				inset -30px 0 0 rgba(255, 196, 140, 0.4),	
				inset 0 -30px 0 rgba(245, 105, 145, 0.4);
  animation: shadowsdancing 4s infinite;
}
.error-container > span:nth-of-type(2):before {
	-webkit-transform: rotate(45deg);
	   -moz-transform: rotate(45deg);
			transform: rotate(45deg);
}

.screen-reader-text {
    position: absolute;
    top: -9999em;
    left: -9999em;
}
@keyframes shadowsdancing {
  0% {
    box-shadow: inset 30px 0 0 rgba(209, 242, 165, 0.4),
				inset 0 30px 0 rgba(239, 250, 180, 0.4),
				inset -30px 0 0 rgba(255, 196, 140, 0.4),	
				inset 0 -30px 0 rgba(245, 105, 145, 0.4);
  }
  25% {
    box-shadow: inset 30px 0 0 rgba(245, 105, 145, 0.4),
				inset 0 30px 0 rgba(209, 242, 165, 0.4),
				inset -30px 0 0 rgba(239, 250, 180, 0.4),	
				inset 0 -30px 0 rgba(255, 196, 140, 0.4);
  }
  50% {
     box-shadow: inset 30px 0 0 rgba(255, 196, 140, 0.4),
				inset 0 30px 0 rgba(245, 105, 145, 0.4),
				inset -30px 0 0 rgba(209, 242, 165, 0.4),	
				inset 0 -30px 0 rgba(239, 250, 180, 0.4);
  }
  75% {
   box-shadow: inset 30px 0 0 rgba(239, 250, 180, 0.4),
				inset 0 30px 0 rgba(255, 196, 140, 0.4),
				inset -30px 0 0 rgba(245, 105, 145, 0.4),	
				inset 0 -30px 0 rgba(209, 242, 165, 0.4);
  }
  100% {
    box-shadow: inset 30px 0 0 rgba(209, 242, 165, 0.4),
				inset 0 30px 0 rgba(239, 250, 180, 0.4),
				inset -30px 0 0 rgba(255, 196, 140, 0.4),	
				inset 0 -30px 0 rgba(245, 105, 145, 0.4);
  }
}
@keyframes colordancing {
  0% {
    color: #D1F2A5;
  }
  25% {
    color: #F56991;
  }
  50% {
    color: #FFC48C;
  }
  75% {
    color: #EFFAB4;
  }
  100% {
    color: #D1F2A5;
  }
}
@keyframes colordancing2 {
  0% {
    color: #FFC48C;
  }
  25% {
    color: #EFFAB4;
  }
  50% {
    color: #D1F2A5;
  }
  75% {
    color: #F56991;
  }
  100% {
    color: #FFC48C;
  }
}

/* demo stuff */
* {
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
}
body {
  background-color: #416475;
  margin-bottom: 50px;
}
html, button, input, select, textarea {
    font-family: 'Montserrat', Helvetica, sans-serif;
    color: #92a4ad;
}
h1 {
  text-align: center;
  margin: 30px 15px;
}
.zoom-area { 
  max-width: 490px;
  margin: 30px auto 30px;
  font-size: 19px;
  text-align: center;
}
.link-container {
  text-align: center;
}
a.more-link {
  text-transform: uppercase;
  font-size: 13px;
    background-color: #92a4ad;
    padding: 10px 15px;
    border-radius: 0;
    color: #416475;
    display: inline-block;
    margin-right: 5px;
    margin-bottom: 5px;
    line-height: 1.5;
    text-decoration: none;
  margin-top: 50px;
  letter-spacing: 1px;
}</style>
</head>
<body>


<h1>404错误页面</h1>
<p class="zoom-area">Sorry, this page isn't here or has moved.</p>
<section class="error-container">
	<span>4</span>
	<span><span class="screen-reader-text">0</span></span>
	<span>4</span>
</section>

<div class="error">
<!-- <p class="face">:(</p> -->
<!-- <h1><?php echo strip_tags($e['message']);?></h1> -->
<div class="content">
<?php if(isset($e['file'])) {?>
	<div class="info">
		<div class="title">
			<h3>错误位置</h3>
		</div>
		<div class="text">
			<p>FILE: <?php echo $e['file'] ;?> &#12288;LINE: <?php echo $e['line'];?></p>
		</div>
	</div>
<?php }?>
<?php if(isset($e['trace'])) {?>
	<div class="info">
		<div class="title">
			<h3>TRACE</h3>
		</div>
		<div class="text">
			<p><?php echo nl2br($e['trace']);?></p>
		</div>
	</div>
<?php }?>
</div>
</div>
<!-- <div class="copyright">
<p><a title="官方网站" href="http://www.thinkphp.cn">ThinkPHP</a><sup><?php echo THINK_VERSION ?></sup> { Fast & Simple OOP PHP Framework } -- [ WE CAN DO IT JUST THINK ]</p>
</div> -->
</body>
</html>
