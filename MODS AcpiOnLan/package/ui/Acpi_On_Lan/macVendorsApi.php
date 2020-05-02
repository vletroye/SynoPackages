<?php 
	/**
	 * MacVendorsApi Class
	 * @author http://macvendors.co
	 * @version 1.0 this version tag is parsed
	 */
 
 class MacvendorsApi{
	 
	 private $api_link = "http://macvendors.co/api/";
	 private $response_type = 'json';
	 
	 
	/**
	 * get_vendor
	 *
	 * Get vendor data by mac address,name or address,if no results it will return false
	 *
	 * @param (string) ($query) mac address,name or address
	 * @return (mixed) (array or bool)
	 */
	public function get_vendor($query,$resp_type='json'){
		 $this->response_type = $resp_type;
		 $link = $this->api_link.$query.'/'.$this->response_type;
		 $response_data = $this->get_curl($link);
		 return $this->extract_data($response_data);
	 }
	 
	 /**
	 * get_vendor_xml
	 *
	 * Get vendor data into XML by mac address,name or address,if no results it will return false
	 *
	 * @param (string) ($query) mac address,name or address
	 * @return (string)
	 */
	 public function get_vendor_xml($query){
		 return $this->get_raw_data($query,'xml');
	 }
	 
	 /**
	 * get_vendor_csv
	 *
	 * Get vendor data into CSV by mac address,name or address,if no results it will return false
	 *
	 * @param (string) ($query) mac address,name or address
	 * @return (string)
	 */
	 public function get_vendor_csv($query){
		 return $this->get_raw_data($query,'csv');
	 }
	 
	 /**
	 * get_vendor_json
	 *
	 * Get vendor data into JSON by mac address,name or address,if no results it will return false
	 *
	 * @param (string) ($query) mac address,name or address
	 * @return (string)
	 */
	 public function get_vendor_json($query){
		 return $this->get_raw_data($query,'json');
	 }
	 
	 /**
	 * get_vendor_pipe
	 *
	 * Get vendor data into PIPE by mac address,name or address,if no results it will return false
	 *
	 * @param (string) ($query) mac address,name or address
	 * @return (string)
	 */
	 public function get_vendor_pipe($query){
		 return $this->get_raw_data($query,'pipe');
	 }
	 
	 
	 /**
	 * get_raw_data
	 *
	 * Helper method to get raw data of vendoe
	 *
	 * @param (string) ($query) mac address,name or address
	 * @param (string) ($resp_type) response type
	 * @return (string)
	 */
	 private function get_raw_data($query,$resp_type){
		  $this->response_type = $resp_type;
		  $link = $this->api_link.$query.'/'.$this->response_type;
		  return  $this->get_curl($link);
	 }
	 
	 /**
	 * extract_data
	 *
	 * Helper method to extract data into array
	 *
	 * @param (array) ($data) vendor data
	 * @return (string)
	 */
	 private function extract_data($data){
		 if(!$data){
			 return false;
		 }
		 
		 $resp_type = $this->response_type;
		 if($resp_type == 'csv' || $resp_type == 'pipe'){
			 if($resp_type == 'pipe'){
				$data_arr = explode ('|',$data);
			 }else{
				$data_arr = str_getcsv($data,',');
			 }
			 
			 if($data_arr[0] == 'no result'){
				 return false;
			 }
			 
			 $result = array ();
			 $result['company'] = $data_arr[0];
			 $result['mac_prefix'] = $data_arr[1];
			 $result['address'] = $data_arr[2];
			 
			 return $result;
		 }elseif($resp_type == 'xml'){
			 $resp_data = $this->parse_xml($data);
			 if(isset($resp_data['error'])){
				 return false;
			 }
			 
			 return $resp_data;
		 }else{
			 $resp_data = json_decode($data,true);
			 return $resp_data['result'];
		 }
	 }
 
	/**
	 * parse_xml
	 *
	 * Helper method to extract XML into array
	 *
	 * @param (array) ($data) vendor data
	 * @return (string)
	 */
	 private function parse_xml($data){
			$xml = new \SimpleXMLElement($data);
			return (array)$xml;
	 }
	 
	 /**
	 * get_curl
	 *
	 * Helper method to get link using CURL
	 *
	 * @param (array) ($data) vendor data
	 * @return (string)
	 */
	 private function get_curl($url){
		 $ch = curl_init();
		 curl_setopt($ch, CURLOPT_URL, $url);
		 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		 return curl_exec($ch);
	 }
 }