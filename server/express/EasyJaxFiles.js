/** EasyJaxFiles public class
 * 
 * Written by Stephen Hansen, Copyright of Hansen Computers LLC,  2013
 * Used to Upload data from a browser to the server asynchronously.
 * 
 * License: MIT
 */

var fs = require("fs");

function EasyJaxFiles (req, res) {
	this.req = req;
	this.res = res;

	this.tx = {"error": ""};
	this.req_method = req.method.toUpperCase();
	
	//this.read = 0;
	this.write = null;
	this.overw = null;

	this.finishSeg = function (floc) { this.send(); }
	this.finishFile = function (floc) { this.send(); }

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
		return this;
	}
	
	this.downloadTo = function (dest) {
		if(dest === undefined) dest = "/tmp";
		var file = req.headers["ejf-file"];
		var dloc = dest+"/"+file;

		ms = dloc.match(/\/([^\/]+)$/);
		this.push('name',ms[1]);

		var stat;
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
			this.push('overw',true);
		} catch(e){
			this.push('overw',false);
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

		var self = this;
		if(this.req.get("EJF-Final") == 'YES'){
			p.on('finish',function () {
				self.finishFile(dloc);
			});
			return dloc;
		} else {
			p.on('finish',function () {
				self.finishSeg(dloc);
			});
			return false;
		}
	}
	
	this.push = function (key, data) {
		this.tx[key] = data;
		return this;
	}
	
	this.add_error_msg = function (msg) {
		this.tx.error += msg+'\n';
	}

	/////Returning data to client
	this.send = function (error) {
		if(error != undefined) this.add_error_msg(error);
		this.res.header({
			"Pragma": "no-chache",
			"Expires": "Thu, 01 Dec 1997 16:00:00 GMT"
		});
		this.res.json(this.tx);
	}
}

module.exports = EasyJaxFiles;