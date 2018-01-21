<?php
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
error_reporting(E_ERROR | E_PARSE);
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
     <center>
<?php
echo "Hello World<br />";
ini_set('track_errors', 1);
$fp = fopen('/var/log/MODS_AdvancedTestCGI', 'a');
if ( !$fp ) {
  echo "[Couldn't access log file]<br />";
}
fwrite($fp, date("D M j G:i:s T Y")." helloworld.php executed\n");
fclose($fp);
?>
     </center>
     </div>
   </div>
</div>
<center>Check the log of this package to see if this page was correctly handled by the cgi script!</center>
<br/><br/><hr/>
<a href="index.html" title="Check if Rewritting is enabled"><?php echo $_SERVER['SERVER_SOFTWARE']?></a> run by '<?php echo exec('whoami'); ?>' at <?php echo date("D M j G:i:s T Y")?><br />
<?php echo "Located in ".getcwd();?>
</body>
</html>
