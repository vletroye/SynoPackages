<?php
$name = $_GET["service"];
$param = $_GET["parameters"];

$file_to_search = "@SYNOPKG_DOWNLOAD_*";
$volume = $param;
$target = "/".$volume."/@tmp/SynoCapture";
$stop = $target."/stop";
$found = $target."/found";
$cancel = $target."/cancel";

switch ($name) {
	case "cancel":
	  sleep(2);
	  $fs = fopen($cancel, "w");
	  fclose($fs);
	  $fs = fopen($stop, "w");
	  fclose($fs);
	  echo "Done";
	  break;
	
	case "capture":
		$capture = "sh capture.sh $volume > /dev/null 2>/dev/null &";
		exec($capture, $output, $int);
		sleep(3);
		$start = microtime(true);
		while (!file_exists($stop) && (microtime(true) - $start) < 120 ) {
		  if ( search_file($target,$file_to_search) ) {
			  $fs = fopen($found, "w");
			  fclose($fs);
			  $fs = fopen($stop, "w");
			  fclose($fs);
		  } else {
			  sleep(1);
		  }	  
		}

		if (!file_exists($found)) {
			if (!file_exists($cancel)) {
				header("HTTP/1.0 404 Not Found");
				echo "Timeout\n";
			} else {
				header("HTTP/1.0 404 Not Found");
				echo "Cancelled\n";			
			}
		} else {
			$start = microtime(true);
			while (!search_file($target,$file_to_search.".spk") && (microtime(true) - $start) < 10 ) {
				sleep(1);
			}
			$file = get_file($target,$file_to_search.".spk");
			if ($file == "") {
				header("HTTP/1.0 404 Not Found");
				echo "Error\n";
			} else {
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.basename($file).'"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));
				header('Set-Cookie: fileDownload=true; path=/');
				readfile($file);
			}
		}
		break;
}

function search_file($dir,$file_to_search){
  $found = false;
  $files = scandir($dir);
  
  foreach($files as $key => $value){
    $path = realpath($dir.DIRECTORY_SEPARATOR.$value);	  
	if(!is_dir($path)) {
        $found = stringMatchWithWildcard($value,$file_to_search);
		if ( $found ) break;
    } else if($value != "." && $value != "..") {
        $found = search_file($path, $file_to_search);
    }  
  }  
  return $found;
}

function get_file($dir,$file_to_search){
  $found = "";
  $files = scandir($dir);
  
  foreach($files as $key => $value){
    $path = realpath($dir.DIRECTORY_SEPARATOR.$value);		  
	if(!is_dir($path)) {
		if ( stringMatchWithWildcard($value,$file_to_search) ) {
			$found = $path;
            break;
        }
    } else if($value != "." && $value != "..") {
        $found = get_file($path, $file_to_search);		
    }  
  }  
  return $found;
}

function stringMatchWithWildcard($value,$pattern) {
    if ($pattern == $value) return true;	
    $pattern = preg_quote($pattern, '#');	
    $pattern = str_replace('\*', '.*', $pattern).'\z';
    return (bool) preg_match('#^'.$pattern.'#', $value);
}

?>