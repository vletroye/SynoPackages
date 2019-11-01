<?php
$name = $_GET["service"];
$param = $_GET["parameters"];

$file_prefix = "@SYNOPKG_DOWNLOAD_";
$file_to_search = "$file_prefix*";
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
	case "recover":
		sleep(2);
		$file = get_file($target,"*.spk");
		if ($file == "") {
			echo "Not Found";
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
	  break;
	case "capture":
		$capture = "sh capture.sh $volume &> /dev/null &";
		exec($capture, $output, $int);
		sleep(3);
		$start = microtime(true);
		$count = 0;
		while (!file_exists($stop) && (microtime(true) - $start) < 180 ) {
		  if ( search_file($target,$file_to_search) ) {
			  $fs = fopen($found, "w");
			  fclose($fs);
			  $fs = fopen($stop, "w");
			  fclose($fs);
		  } else {
			  sleep(1);
			  //$count += 1;			  
			  //$fs = fopen($cancel."_".$count, "w");
		      fclose($fs);
		  }	  
		}
		$fs = fopen($stop, "w");
		fclose($fs);

		if (!file_exists($found)) {
			$exec="echo . > /volume1/@tmp/SynoCapture/1_FileNotFound";
			exec($exec, $output, $int);
			if (!file_exists($cancel)) {
				$exec="echo . > /volume1/@tmp/SynoCapture/2_FileNotCancelled";
				exec($exec, $output, $int);
				echo "Timeout";
			} else {
				$exec="echo . > /volume1/@tmp/SynoCapture/2_FileCancelled";
				exec($exec, $output, $int);
				echo "Cancelled";			
			}
			$exec="echo $file > /volume1/@tmp/SynoCapture/3_FileNotFound";
			exec($exec, $output, $int);
		} else {
			$exec="echo . > /volume1/@tmp/SynoCapture/1_FileFound";
			exec($exec, $output, $int);
			$start = microtime(true);
			while (!search_file($target,$file_to_search.".spk") && (microtime(true) - $start) < 10 ) {
				sleep(1);
			}
			$file = get_file($target,$file_to_search.".spk");
			if ($file == "") {
				$exec="echo . > /volume1/@tmp/SynoCapture/2_FileFoundButNoSPK";
				exec($exec, $output, $int);
				echo "Not Found";
			} else {
				$exec="echo $file > /volume1/@tmp/SynoCapture/2_FileFoundWithSPK";
				exec($exec, $output, $int);
				$spk = str_replace($file_prefix, "", $file);
				rename($file, $spk);
				
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.basename($spk).'"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($spk));
				header('Set-Cookie: fileDownload=true; path=/');
				readfile($spk);
			}
			$exec="echo $file > /volume1/@tmp/SynoCapture/3_FileFound";
			exec($exec, $output, $int);
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
    } else if ($value != "." && $value != "..") {
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