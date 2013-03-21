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

/** EasyJax public class
 * Written by Stephen Hansen,  2013
 * Used to assist with data exchange between client and server using JSON to transfer data.
 * This class has been fully tested using the mysqli object.  Feel free to use it with the SQL object of your choice.
 * This class can easily be used without any SQL object to return the JSON data that was submitted.
 *
 * When finished processing the request, call the send_resp method (with an optional error message) to send data back to the client
 * error messages can be added using add_error_msg.  Data to be returned to the client can be set using set_ret_data
 */

class EasyJax {
  private $return_data;
	protected $mysqli_inst;
	protected $json_data;
	
	public function __construct($mysqli_inst="not_here"){
		$this -> return_data = array();
		$this -> return_data['error'] = "";
		$this -> mysqli_inst = $mysqli_inst;
		
		$data_in = file_get_contents("php://input");
		$this -> json_data = json_decode($data_in,1);
	}
	
	public function get_send_data($key=null){
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
		header("Content-type: application/json");
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
