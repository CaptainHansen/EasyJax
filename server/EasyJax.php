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
