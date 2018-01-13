<!DOCTYPE html>
<html>
<head>
<style>
body {
    margin : 0;
}

.outer-container {
    display: table;
    width: 100%;
    height: 120px;
    background: #ccc;
}

.inner-container {
    display: table-cell;
    vertical-align: middle;
    text-align: center;
}

.centered-content {
    display: inline-block;
    text-align: left;
    background: #fff;
    padding : 20px;
    border : 1px solid #000;
}
</style>
</head>
<body>
<div class="outer-container">
   <div class="inner-container">
     <div class="centered-content">
<?php
echo "Hello World";
$fp = fopen('/var/log/MODS_HelloWorld_CGI', 'a'); fwrite($fp, "helloworld.php executed\n"); fclose($fp);
?>
     </div>
   </div>
</div>
</body>
</html>
