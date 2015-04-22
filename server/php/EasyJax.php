<?
// EasyJax public class
// Written by Stephen Hansen, Copyright of Hansen Computers LLC,  2013
// Used to assist with data exchange between client and server using JSON to transfer data.
// License: MIT

class EasyJax {
	private $return_data;
	protected $json_data;
	public $path = false;
	public $req_method;
	
	public function __construct(){
		if(isset($_SERVER['PATH_INFO'])){
			$this -> path = $_SERVER['PATH_INFO'];
		}
		$this -> req_method = strtoupper($_SERVER['REQUEST_METHOD']);
		$this -> return_data = array();
		$this -> return_data['error'] = "";
		$jtext = file_get_contents("php://input");
		$this -> json_data = json_decode($jtext,1);
	}
	
	public function getData($key=null){
		if($key === null){
			return $this -> json_data;
		} else {
			if(isset($this -> json_data[$key])){
				return $this -> json_data[$key];
			} else {
				return false;
			}
		}
	}
	
	public function setData($key,$val){
		if(!isset($this -> json_data[$key])){
			$this -> json_data[$key] = $val;
			return true;
		}
		return false;
	}
	
	public function set_ret_data($key,$data){
		$this -> return_data[$key] = $data;
	}
	
	public function add_error_msg($msg){
		$this -> return_data['error'] .= $msg."\n";
	}


	/////Returning data to client
	public function send_resp($error = ""){
		if($error != ""){
			$this -> add_error_msg($error);
		}
		header("Content-type: application/json; charset=UTF-8");
		$send = json_encode($this -> return_data);
		header("Pragma: no-cache");
		header("Expires: Thu, 01 Dec 1997 16:00:00 GMT");
		echo $send;
		die;
	}
	
	
	
	public function send_if_error(){
		if($return_data['error'] != ""){
			$this->send_resp();
		}
	}
}
