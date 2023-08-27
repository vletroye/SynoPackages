<?php
$name = $_GET["service"];
$param = $_GET["parameters"];
HandleRequest($name, $param);

function HandleRequest($name, $param) {
	try {
	
		if (isJson($param)) {
			$param = json_decode($param);
		}
				
		switch ($name) {
			case "Implemented":
				$response = array('html' => "service '$name($param)' is successful.");
				break;

			case "Do nothing":
				break;

			case "Throw":
				throw new Exception('Error thrown for illustration purpose.', 100);
				break;			

			default:
				if (function_exists($name)) {
					$response = $name($param);
				} else {		
					$response = array('html' => "service '$name($param)' is not implemented.");
				}
				break;
		}	
	} catch (Exception $e) {
		$response = array(
			'error' => array(
				'msg' => $e->getMessage(),
				'code' => $e->getCode(),
				'stack' => $e->getTraceAsString(),
			),
		);	
	} finally {
		header('Content-Type: application/json');
		
		if ($response && !is_array($response)) {
			$response = array('html' => $response);
		} 
		$return = json_encode($response);

		echo $return;
	}
}

function isJson($string) {
	json_decode($string);
	
	//json_last_error is supported in PHP >= 5.3.0 only.
	return (json_last_error() == JSON_ERROR_NONE);
}

//---------------------------------------------------------
// IMPLEMENT YOUR SERVICES HERE AFTER
//---------------------------------------------------------
//
// Each function must return 
// - a string or
// - an array with at least one 'html' element
//
//---------------------------------------------------------

function DynamicString($param) {
	//$return = array('html' => $param);
	$return = $param;
	
	return $return;
}

function DynamicJson($param) {
	//$return = array('html' => $param['text']);
	$return = $param->text;
	
	return $return;
}

?>