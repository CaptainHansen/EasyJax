<?
namespace EasyJax;

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

//* EasyJax\MySQL public class
//* Written by Stephen Hansen, Copyright of Hansen Computers LLC,  2013
//* Simplifies Reads, Writes, and Deletes to MySQL database entries from a simple AJAX request

class MySQL extends \EasyJax {
	public $type;
	private $id;
	private $db;	//this is actually the table name that will be edited
	
	public function __construct(\mysqli $mysqli,$db,$text_ids=array(),$checkboxes=array()){
		parent::__construct($mysqli);
		
		$this -> db = $db;

		if(isset($_SERVER['PATH_INFO'])){
			$this -> set_type_and_id($_SERVER['REQUEST_METHOD'],intval(basename($_SERVER['PATH_INFO'])));
		} else {
			$this -> set_type_and_id($_SERVER['REQUEST_METHOD']);
		}
		
		$this -> escape_text($text_ids);
		$this -> check_checkboxes($checkboxes);
	}
	
	private function set_type_and_id($type,$id=0){
		$mysqli = $this -> mysqli_inst;
		$this -> type = $type;
		
		if($this -> type != 'GET' && !isset($GLOBALS['user_d'])) {
			$this -> send_resp("You can't do that because you are not logged in.");
			die;
		}
		
		switch($this -> type){
			case 'POST':	//creating a new record with this data.
			break;
			case 'PUT': $this -> id = $id;
			break;
			case 'DELETE': $this -> id = $id;
			break;
			case 'GET': $this -> id = $id;
			break;
			default: $this -> send_resp("The type of client to server transaction is invalid.");
		}
		
		if($this -> id > 0){
			$res = $mysqli -> query("select * from {$this->db} where ID = {$this->id}");
			if($res -> num_rows == 0){
				$this -> send_resp("The item you are trying to access does not exist.");
			}
		}
	}
	
	//This method does alter the database in any way, it just reports to the client what its going to do.
	public function test(){
		$this -> send_resp("Method type: {$this->type}\nID (if needed): {$this->id}\nSQL string: ".$this->SQL_get_string());
	}
	
	/***START OF Getters and Setters***/
	//getData already defined in parent class
	
	public function setData($key,$data){
		$this -> json_data[$key] = $data;
		return true;
	}
	
	public function get_id(){
		return $this -> id;
	}
	
	/***END OF Getters and Setters***/
	
	private function escape_text($text_ids){
		foreach($text_ids as $id => $tid){
			if(isset($this->json_data[$tid])){
				$this -> json_data[$tid] = preg_replace('/\'/','\\\'',preg_replace('/&#039;/','\'',($this -> json_data[$tid])));
				if($this -> json_data[$tid] == null) unset($this-> json_data[$tid]);
			}
		}
	}
	
	private function check_checkboxes($checkboxes){
		foreach($checkboxes as $id => $checkbox){
			if(isset($this->json_data[$checkbox])){
				if($this -> json_data[$checkbox] !== "true"){
					$this -> json_data[$checkbox] = 'false';
				}
			}
		}
	}

	public function write_to_SQL(){
		$mysqli = $this -> mysqli_inst;
	
		$sql=$this -> SQL_get_string();

		switch($this -> type){
		
		/////modifying or editing a record
		case 'PUT':
			if($mysqli->query($sql)){
				$this -> set_ret_data('ID',$this -> id);
			} else {
				$this -> add_error_msg("Error editing ".$this->db." ID # ".$this->id);
			}
		break;
		
		//////creating a new record
		case 'POST':
			if($mysqli->query($sql)){
				$id = $mysqli->insert_id;
				$this -> set_ret_data('ID',$id);
				$this -> id = $id;
			} else {
				$this -> add_error_msg("Error adding to ".$this->db."\nSQL string: ".$sql);
			}
		break;
		
		//////and finally, deleting a record.
		case 'DELETE':
			if($mysqli->query($sql)){
				$this -> set_ret_data('ID',$this -> id);
			} else {
				$this -> add_error_msg("Error deleting from ".$this->db." ID # ".$this->id."\nSQL string: ".$sql);
			}
		break;
		
		///////loading a record
		case 'GET':
			if($res = $mysqli->query($sql)){
				$this -> set_ret_data('ID',$this -> id);
				$this -> set_ret_data('data',$res->fetch_assoc());
			} else {
				$this -> add_error_msg("Error loading from ".$this->db." ID # ".$this->id);
			}
		break;
		}
	}
	
	private function SQL_get_string(){
		$db = $this -> db;
		switch($this -> type){
			case 'POST': $sql="INSERT INTO $db SET ";
			break;
			case 'PUT': $sql="UPDATE $db SET ";
			break;
			case 'DELETE': return "DELETE FROM $db WHERE ID = ".($this->id);
			case 'GET': return "SELECT * FROM $db WHERE ID = ".($this->id);
		}
		$first=true;
		foreach($this -> json_data as $field => $value){
			if(!$first){
				$sql.=", ";
			} else {
				$first=false;
			}
			if(is_int($value)){
				$sql.="$field = $value";
			} elseif($value == "true") {
				$sql.="$field = true";
			} elseif($value == "false") {
				$sql.="$field = false";
			} elseif($value == "") {
				$sql.="$field = NULL";
			} else {
				$sql.="$field = '$value'";
			}
		}
		if($this -> type == 'PUT') $sql.=" WHERE ID = '".$this -> id."'";
		return $sql;
	}
}
