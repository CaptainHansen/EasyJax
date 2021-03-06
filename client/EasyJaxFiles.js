/** EasyJaxFiles Javascript class
 *
 * instantiate this class with the URL to post files to (PHP script using the EasyJaxFiles PHP class)
 * supply a callback function that will run after each successful upload.  If an error is returned,
 * it is announced using the alert() function
 * 
 * License: MIT
 */
 
 // make sure we have the sendAsBinary method on all browsers
XMLHttpRequest.prototype.mySendAsBinary = function(text){
  var data = new ArrayBuffer(text.length);
  var ui8a = new Uint8Array(data, 0);
  for (var i = 0; i < text.length; i++) ui8a[i] = (text.charCodeAt(i) & 0xff);

  if(typeof window.Blob != undefined) {
    var blob = new Blob([data]);
  } else {
    var bb = new (window.MozBlobBuilder || window.WebKitBlobBuilder || window.BlobBuilder)();
    bb.append(data);
    var blob = bb.getBlob();
  }

  this.send(blob);
}

function EasyJaxFiles (Url,req_type){
  //if(Url.slice(-1) != '/') Url += '/';
  this.Url = Url;

  this.start = function() { return true; };

  this.nextfile = function() {};
  
  this.progress = function(e) {};
  /*** like this: 
  function(e) {
        // get percentage of how much of the current file has been sent
        var position = e.position || e.loaded;
        var total = e.totalSize || e.total;
        var percentage = Math.round((position/total)*100);
    
        $('.file_progress').last().html("Uploaded "+percentage+"% of file "+file.name);

        // here you should write your own code how you wish to process this
      });
  */
  this.badjson = function(txt) { alert("Unknown JSON response:<br>"+txt); return false; }
  this.success = function(data) {};
  this.error = function(data) { alert(data.error); return false; };
  
  this.finish = function() { return true; };
  this.finishError = function() { alert("Errors occurred while uploading.  Some files were not successfully uploaded."); };

  this.addFiles = function(files) {
    for(i in files){
      if(typeof files[i] != 'object') continue;
      this.files.push(files[i]);
    }
    if(!this.uploading) {
      this.upload();
    }
  }
  
  this.files = [];
  this.files_index = 0;
  
  this.allpass;
  this.uploading = false;
  
  this.xmlHttp;
  this.req_type = req_type;
  
  this.fileReader;
  
  this.segment = 0;
  
  
  this.on = function(type,f){
    switch(type){
    case "start":
      this.start = f;
      break;
    
    case "nextfile":
      this.nextfile = f;
      break;
    
    case "progress":
      this.progress = f;
      break;
    
    case "badjson":
      this.badjson = f;
      break;
    case "success":
      this.success = f;
      break;
    case "error":
      this.error = f;
      break;
    
    case "finish":
      this.finish = f;
      break;
    case "finishError":
      this.finishError = f;
      break;

    default:
      throw "No handler for type \""+type+"\"";
      break;
    }
    return this;
  }
  
  this.upload = function (){
    if(this.files.length == 0) {
      alert("You must first select files to upload!");
      return false;
    }

    if(this.files_index == 0) {
      this.allpass = true;
      if(this.start() == false) return false;
    }
    this.uploading = true;
    this.fileReader = new FileReader();
    if(this.files_index >= this.files.length) {
      this.uploading = false;
      this.files = [];
      this.files_index = 0;
      if(this.allpass){
        return this.finish();
      } else {
        return this.finishError();
      }
    }
    this.nextfile();
    this.runUpload();
  }
  
  this.runUpload = function(lstart,more){
    // take the file from the input
    var file = this.files[this.files_index];
    if(file.size > 5000000) {
      if(lstart == undefined){
        var start = 0;
        this.segment = 1;
        var more = 5000000;
      } else {
        var start = lstart+more;
        this.segment ++;
      }
      if(file.size <= start+more){
//        more = file.size - start;
        var lastone = true;
      } else var lastone = false;
      this.fileReader.onloadend = this.createUploader(start,more,lastone);
      //piece together
      var slice = file.slice || file.webkitSlice || file.mozSlice;
      var blob = slice.call(file,start,start+more);
      this.fileReader.readAsBinaryString(blob);
    } else {
      this.fileReader.onloadend = this.createUploader();
      this.fileReader.readAsBinaryString(this.files[this.files_index]);
    }
  }
  
  this.createUploader = function(start,more,lastone){
    var file = this.files[this.files_index];
    var ejf = this;
    this.xmlHttp = new XMLHttpRequest();
    
    var xhrCallback = this.createXHRCallback(start,more,lastone);

    var x = this.xmlHttp;
    return function(evt) {
      x.open(ejf.req_type, ejf.Url, true);
      
      var last = 'YES';
      x.setRequestHeader("Content-type","application/octet-stream");
      x.setRequestHeader("EJF-File",file.name);
      if(start != undefined){
        x.setRequestHeader("EJF-Segment",ejf.segment);
//        x.setRequestHeader("Content-Length",more);
        if(!lastone) last = 'NO';
      } else {
        start = 0;
//        x.setRequestHeader("Content-Length",file.size);
      }
      x.setRequestHeader("EJF-Final",last);

      // let's track upload progress
      var eventSource = x.upload || x;
      eventSource.addEventListener("progress", (function(ejf) {
        return function(e){
          var s = {
            'position' : e.loaded || e.position,
            'total' : file.size,
            'num_files' : ejf.files.length,
            'current_file' : ejf.files_index+1,
          };
          s.position += start;
          s.percent = (s.position/s.total)*100;
          s.overallPercent = ((s.position/s.total)/ejf.files.length + (ejf.files_index)/ejf.files.length)*100;
          ejf.progress(s,file,e);
        };})(ejf));
//      });

      // state change observer - we need to know when and if the file was successfully uploaded
      x.onreadystatechange = xhrCallback;

      // start sending
      x.mySendAsBinary(evt.target.result);
    };
  }
  
  this.createXHRCallback = function(start,more,lastone){
    var x = this.xmlHttp;
    var ejf = this;
    return function() {
      if(x.readyState == 4) {
        if(x.status == 200) {
          try {
            var data = JSON.parse(x.responseText);
          } catch(e) {
            if(!ejf.badjson(x.responseText)) return false;
          }
          
          if(start == undefined || lastone){
            if(data.error == ''){
              ejf.success(data);
            } else {
              ejf.allpass = false;
              if(!ejf.error(data)) return false;
            }
            ejf.files_index += 1;
            ejf.upload();
          } else {
            if(data.error == ''){
              ejf.runUpload(start,more);
            } else {
              ejf.allpass = false;
              if(!ejf.error(data)) return false;
            }
          }
        } else {
          alert("Status code "+x.status+" not continuing...");
        }
      }
    };
  }
}