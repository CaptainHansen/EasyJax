<?
// EasyJaxFiles public class
// Written by Stephen Hansen, Copyright of Hansen Computers LLC,  2013
// Used to Upload data from a browser to the server asynchronously.
// License: MIT

class EasyJaxFiles {
	private $return_data;
	public $path = false;
	public $req_method;

	public $exts = array();
	
	private $read;
	private $write;
	private $overw;
	
	public function __construct(){
		$this -> path = $_SERVER['PATH_INFO'];
		$this -> req_method = strtoupper($_SERVER['REQUEST_METHOD']);
		$this -> return_data = array();
		$this -> return_data['error'] = "";
	}
	
	public function downloadTo($folder = "/tmp"){
		$dloc = $folder.$this -> path;
		$dest = dirname($dloc);

		$this -> read = fopen('php://input', "r");
		$this -> set_ret_data('name',basename($this -> path));
		
		if(!is_dir($dest)){
			$this -> add_error_msg("Destination folder does not exist.");
			return false;
		}
		
		if(file_exists($dloc)){
			$this -> set_ret_data('overw',true);
		} else {
			$this -> set_ret_data('overw',false);
		}
		if(isset($_SERVER['HTTP_EJF_SEGMENT'])){
			if($_SERVER['HTTP_EJF_SEGMENT'] == 1) {
				unlink($dloc);
			}
			$this -> write = fopen($dloc,'a');
		} else {
			$this -> write = fopen($dloc,'w');
		}
		if(!$this -> write) {
			$this -> add_error_msg("Cannot open a write handle.");
			return false;
		}
		
		while(true) {
			$buffer = fgets($this -> read, 4096);
			if (strlen($buffer) == 0) {
				fclose($this -> read);
				fclose($this -> write);
				break;
			}
			fwrite($this -> write, $buffer);
		}
		if($_SERVER['HTTP_EJF_FINAL'] == 'YES'){
			return $dloc;
		} else {
			$this -> send_resp();	//stop execution here if file is segmented and this is not the last one!!!
		}
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
		header("Pragma: no-cache");
		header("Expires: Thu, 01 Dec 1997 16:00:00 GMT");
		echo json_encode($this->return_data);
		die;
	}
	
	public function send_if_error(){
		if($return_data['error'] != ""){
			$this->send_resp();
		}
	}
}