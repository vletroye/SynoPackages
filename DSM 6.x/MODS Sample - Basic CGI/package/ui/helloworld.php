<?php
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
?>
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
        ?>
     </div>
   </div>
</div>
Served by: <?php echo $_SERVER['SERVER_SOFTWARE']?>
</body>
</html>
