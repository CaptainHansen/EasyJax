// EasyJaxFiles public class
// Written by Stephen Hansen, Copyright of Hansen Computers LLC,  2013
// Used to Upload data from a browser to the server asynchronously.
// License: MIT

var fs = require("fs");

function EasyJaxFiles(req,res) {
  this.req = req;
  this.res = res;

  this.return_data = {"error":""};
  this.path = "/"+req.headers["ejf-file"];  //$this -> path = $_SERVER['PATH_INFO'];
  this.req_method = req.method.toUpperCase();

  this.exts = [];
  
  this.read = null;
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

//    this.read = new SlowBuffer(); //$this -> read = fopen('php://input', "r");
    ms = dloc.match(/\/([^\/]+)$/);
    this.set_ret_data('name',ms[1]);

    try {
      stat = fs.statSync(dest);
      if(!stat.isDirectory()){
        this.send_resp("Destination folder does not exist.");
        return false;
      }
    } catch(e){
      this.send_resp("Destination folder does not exist. Backend failure.");
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

    if(req.headers["ejf-segment"] != null){
      if(req.headers["ejf-segment"] == 1 && stat){
        fs.unlinkSync(dloc);
      }
      opts.flags = 'a';
    } else {
      opts.flags = 'w';
    }

    this.write = fs.createWriteStream(dloc,opts);

    if(!this.write){
      this.send_resp("Cannot open a write handle.");
      return false;
    }

    this.read = fs.createReadStream(this.req.files.file.path);
    var p = this.read.pipe(this.write);
    //var p = req.pipe(this.write);

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
    //this.log('fjkdlajfkldsjaflkajklfdsjalkfjdsaklfjdklsajfklsdajfkldsjaf');
    if(this.req.headers["ejf-final"] == 'YES'){
      p.on('finish',(function(file,ejf){
        return function(){
          require('fs').unlink(ejf.req.files.file.path);
          ejf.finishFile.call(ejf,file);
        };
      })(dloc,this));
      return dloc;
    } else {
      p.on('finish',(function(file,ejf){
        return function(){
          require('fs').unlink(ejf.req.files.file.path);
          ejf.finishSeg.call(ejf,file);
        };
      })(dloc,this));
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
    this.res.json(this.return_data);
    //die......
  }
}

module.exports = EasyJaxFiles;