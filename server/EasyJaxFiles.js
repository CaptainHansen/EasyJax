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

//* EasyJaxFiles public class
//* Written by Stephen Hansen, Copyright of Hansen Computers LLC,  2013
//* Used to Upload data from a browser to the server asynchronously.

var fs = require("fs");

function EasyJaxFiles(req,res) {
	this.req = req;
	this.res = res;

	this.return_data = {"error":""};
	this.path = req.url;	//$this -> path = $_SERVER['PATH_INFO'];
	this.req_method = req.method.toUpperCase();

	this.exts = [];
	
	//this.read = 0;
	this.write = null;
	this.overw = null;

	this.finishSeg = function(floc){ this.send_resp(); }
	this.finishFile = function(floc){ this.send_resp(); }

	this.on = function(evt,fn){
		switch(evt){
		case "finishFile":
			this.finishFile = fn;
			break;

		case "finishSeg":
			this.finishSeg = fn;
			break;
		default:
			throw new Error(evt+" is not a recognized event for EasyJaxFiles.");
		}
	}
	
	this.downloadTo = function(folder){
		if(folder === undefined) folder = "/tmp";
		var dloc = folder+this.path;
		var ms = dloc.match(/^(.+)\/[^\/]+$/);
		var dest = ms[1];

		var stat;

//		this.read = new SlowBuffer(); //$this -> read = fopen('php://input', "r");
		ms = dloc.match(/\/([^\/]+)$/);
		this.set_ret_data('name',ms[1]);

		try {
			stat = fs.statSync(dest);
			if(!stat.isDirectory()){
				this.add_error_msg("Destination folder does not exist.");
				return false;
			}
		} catch(e){
			this.add_error_msg("Destination folder does not exist. Backend failure.");
			return false;
		}

		try {
			stat = fs.statSync(dloc);
			this.set_ret_data('overw',true);
		} catch(e){
			this.set_ret_data('overw',false);
			stat = false;
		}

		var opts = {};

		if(req.get("EJF-Segment") != null){
			if(req.get("EJF-Segment") == 1 && stat){
				fs.unlinkSync(dloc);
			}
			opts.flags = 'a';
		} else {
			opts.flags = 'w';
		}

		this.write = fs.createWriteStream(dloc,opts);

		if(!this.write){
			this.add_error_msg("Cannot open a write handle.");
			return false;
		}

		var p = req.pipe(this.write);

/*		
		while(true) {
			$buffer = fgets($this -> read, 4096);
			if (strlen($buffer) == 0) {
				fclose($this -> read);
				fclose($this -> write);
				break;
			}
			fwrite($this -> write, $buffer);
		}
		*/
		if(this.req.get("EJF-Final") == 'YES'){
			p.on('finish',(function(file,ejf){
				return function(){ ejf.finishFile.call(ejf,file); };
			})(dloc,this));
			return dloc;
		} else {
			p.on('finish',(function(file,ejf){
				return function(){ ejf.finishSeg.call(ejf,file); };
			})(dloc,this));
			this.send_resp();
			return false;
		}
	}
	
	this.set_ret_data = function(key,data){
		this.return_data[key] = data;
	}
	
	this.add_error_msg = function(msg){
		this.return_data['error'] += msg+'\n';
	}

	/////Returning data to client
	this.send_resp = function(error){
		if(error != undefined) this.add_error_msg(error);
		this.res.header({
			"Pragma": "no-chache",
			"Expires": "Thu, 01 Dec 1997 16:00:00 GMT"
		});
		this.res.send(this.return_data);
		//die......
	}
}

module.exports = EasyJaxFiles;