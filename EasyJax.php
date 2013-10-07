<?

/*
 * Copyright (c) 2013 Stephen Hansen (www.hansencomputers.com)
 * 
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:

 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE. 
 */

//* EasyJax public class
//* Written by Stephen Hansen, Copyright of Hansen Computers LLC,  2013
//* Used to assist with data exchange between client and server using JSON to transfer data.

class EasyJax {
	private $return_data;
	protected $mysqli_inst;
	protected $json_data;
	public $path;
	public $req_method;
	
	public function __construct(\mysqli $mysqli_inst=NULL){
		if(isset($_SERVER['PATH_INFO'])){
			$this -> path = $_SERVER['PATH_INFO'];
		}
		$this -> req_method = strtoupper($_SERVER['REQUEST_METHOD']);
		$this -> return_data = array();
		$this -> return_data['error'] = "";
		$this -> mysqli_inst = $mysqli_inst;
		$this -> json_data = json_decode(file_get_contents("php://input"),1);
	}
	
	public function getData($key=null){
		if($key === null){
			return $this -> json_data;
		} else {
			return $this -> json_data[$key];
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
		echo json_encode($this->return_data);
		die;
	}
	
	public function send_if_error(){
		if($return_data['error'] != ""){
			$this->send_resp();
		}
	}

	public function SQL_sub($sql){
		$mysqli = $this -> mysqli_inst;
		$mysqli->query($sql);
		if($mysqli->error != ""){
			$this -> add_error_msg($mysqli -> error);
		}
	}
}
