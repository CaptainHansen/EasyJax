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
//* Simplifies Re-Ordering of objects in a MySQL table.  This class can move objects from one parent ID to another.  Based on sequence numbers.  Callback function in javascript can be easily written with the use of jQuery

class MySQL_SequenceChanger extends \EasyJax {
	private $to_id = 0;
	private $to_parent = 0;
	private $from_id = 0;
	private $db;	//again, this is the table name, NOT the database name in MySQL
	private $pID_field;
	private $pID_mismatch_ok;
	
	public function __construct($mysqli,$db,$parent_ID_field='pID',$pID_mismatch_ok = false){	//if true, HCI_SEQ will move the item to the new pID, then do the move (bottom up)
		parent::__construct($mysqli);
		$all_sent = $this -> json_data;
		$this -> from_id = intval($all_sent['from_id']);
		if(isset($all_sent['to_id'])) $this -> to_id = intval($all_sent['to_id']);
		if(isset($all_sent['to_parent'])) $this -> to_parent = intval($all_sent['to_parent']);
		$this -> db = $db;
		$this -> pID_field = $parent_ID_field;
		$this -> pID_mismatch_ok = ($pID_mismatch_ok == true);	//true or false.
		
		$this -> set_ret_data('from_id',$this->from_id);
		$this -> set_ret_data('to_id',$this->to_id);
		$this -> set_ret_data('to_parent',$this -> to_parent);
		
		if(($this -> from_id == 0 || $this -> to_id == 0) && ($this -> from_id == 0 || $this -> to_parent == 0)){
			$this->send_resp("Javascript error: ID to move and/or ID of destination were not submitted.");
		}
	}
	
	public function write_seq_ch(){
		if($this -> to_parent > 0 && $this -> from_id > 0) {
			$this -> obj_to_parent();
		} else {
			$this -> obj_to_obj();
		}
	}
	
	private function obj_to_parent(){
		if(!$this -> pID_mismatch_ok) {
			$this -> send_resp("Parent ID mismatch is not enabled.  You cannot move the selected item to a different parent.");
		}
		
		$this -> set_ret_data('move','down');
	
		$mysqli = $this -> mysqli_inst;
		$res = $mysqli -> query("select seq,{$this->pID_field} from {$this->db} where ID = {$this->from_id}");
		list($from,$from_pID) = $res -> fetch_row();

		$to_pID = $this -> to_parent;

		if($from == 0){
			$this->send_resp("Sequence data could not be pulled from the database.");
		}
		
		//move to the bottom to remove from this sequence grouping
		$res = $mysqli -> query("select count(*) from {$this->db} where {$this->pID_field} = $from_pID");
		list($to_bottom) = $res -> fetch_row();
		if($from != $to_bottom){	//if the item is already at the bottom, take no action.
			$this -> internal_sequence_change($from,$to_bottom,$from_pID);
		}

		//it is now at the bottom.  change it's parent ID to where the destination object is, set seq to count of new grouping + 1
		$res = $mysqli -> query("select count(*) from {$this->db} where {$this->pID_field} = $to_pID");
		list($from) = $res -> fetch_row();
		$from++; //increment
		$mysqli -> query("update {$this->db} set seq = $from, {$this->pID_field} = $to_pID where ID = {$this->from_id}");
	}
	
	private function obj_to_obj(){
		$mysqli = $this -> mysqli_inst;
		$res = $mysqli -> query("select seq,{$this->pID_field} from {$this->db} where ID = {$this->from_id}");
		list($from,$from_pID) = $res -> fetch_row();
		
		$res = $mysqli -> query("select seq,{$this->pID_field} from {$this->db} where ID = {$this->to_id}");
		list($to,$to_pID) = $res -> fetch_row();
				
		if($to == 0 || $from == 0){
			$this->send_resp("Sequence data could not be pulled from the database.");
		}
		
		if($from_pID != $to_pID){	//implement sequence change on a pID mismatch (while preserving sequencing consistency)
			if($this -> pID_mismatch_ok){
				//move to the bottom to remove from this sequence grouping
				$res = $mysqli -> query("select count(*) from {$this->db} where {$this->pID_field} = $from_pID");
				list($to_bottom) = $res -> fetch_row();
				if($from != $to_bottom){	//if the item is already at the bottom, take no action.
					$this -> internal_sequence_change($from,$to_bottom,$from_pID);
				}

				//it is now at the bottom.  change it's parent ID to where the destination object is, set seq to count of new grouping + 1
				$res = $mysqli -> query("select count(*) from {$this->db} where {$this->pID_field} = $to_pID");
				list($from) = $res -> fetch_row();
				$from++; //increment
				$mysqli -> query("update {$this->db} set seq = $from, {$this->pID_field} = $to_pID where ID = {$this->from_id}");
				//now free to proceed as normal!
				
			} else {
				$this -> send_resp("The items you selected cannot be altered in sequence - parent ID mismatch.\n\nThis should NEVER happen...");
			}
		}
		$this -> internal_sequence_change($from,$to,$to_pID);
	}
	
	public function internal_sequence_change($from,$to,$pID){
		$mysqli = $this -> mysqli_inst;
		$sql = "update {$this->db} set seq = $to where id = {$this->from_id}";
		$mysqli -> query($sql);
		
		if($from == $to){
			$this -> send_resp("Selected item and relative item are identical.  No action required.");
		} elseif($from > $to){
			$this -> set_ret_data('move','up'); //use .insertBefore in jQuery
		} else {
			$this -> set_ret_data('move','down'); //use .insertAfter in jQuery
		}
		
		while($from > $to){  //moving UP, all others must move DOWN
			$sql = "update {$this->db} set seq = $from where id != {$this->from_id} and {$this->pID_field} = {$pID} and seq = ".($from-1);
			$mysqli -> query($sql);
			$from--;
		}
		while($from < $to){  //moving DOWN, all others must move UP
			$sql="update {$this->db} set seq = $from where id != {$this->from_id} and {$this->pID_field} = {$pID} and seq = ".($from+1);
			$mysqli -> query($sql);
			$from++;
		}
	}
}