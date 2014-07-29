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

/** EasyJax Javascript class
 *
 * instantiate this class with the URL to post data to (PHP script using the EasyJax PHP class)
 * supply a callback function that will run if the script returns success.  If an error is returned,
 * it is announced using the alert() function
 */

function EasyJax (Url,req_type,runOnSuccess,post_obj){
  this.Url = Url;
  if(post_obj == undefined){
    this.post_obj = {};
  } else {
    this.post_obj = post_obj;
  }
  this.xmlHttp;
  this.req_type = req_type;
  this.aes = false;
  this.enc;
  this.csrf;

  this.success = function (data, post_obj) {
    if(runOnSuccess == undefined)
      return false;
    else
      runOnSuccess (data, post_obj);
  }
  this.error = function(data) { alert(data.error); }

  this.on = function(e,fn){
    switch(e){
      case "error":
        this.error = fn;
        break;
      case "success":
        this.success = fn;
        break;
      default:
        throw new Error("Event '"+e+"' not recognized for EasyJax");
    }
    return this;
  }

  this.r = false; //response - for debugging purposes

  this.send = function (){
    if(window.XMLHttpRequest) {
      this.xmlHttp = new XMLHttpRequest();
    } else {
      this.xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    //data is a JSON data package returned to the client from the server.
    this.xmlHttp.onreadystatechange = this.createCallback();

    this.xmlHttp.open( this.req_type, this.Url, true );
    this.xmlHttp.setRequestHeader("Content-Type","application/json; charset=ISO-8859-1");
    if (this.csrf) {
      this.xmlHttp.setRequestHeader("X-CSRF-Token", this.csrf);
    }
    this.xmlHttp.send(JSON.stringify(this.post_obj));
  }

  this.push = function(id,val){
    this.post_obj[id] = val;
  }

  this.createCallback = function (){
    var aes = this.aes;
    var x = this.xmlHttp;
    var ej = this;
    var pobj = this.post_obj;
    return function (){
      if(x.readyState == 4) {
        switch(x.status) {
        case 200:
          var response = x.response;
          try {
            var data = JSON.parse(response);
          } catch(err){
            ej.r = response;
            if(aes != false){
              alert("There was an error decrypting and/or parsing response.\n\n"+x.response);
            } else {
              alert("There was an error parsing JSON data.  Response Text shown below:\n\n"+x.response);
            }
            return 1;
          }

          ej.r = data;

          if(data.error != undefined && data.error != ""){
            ej.error(data,pobj);
            return 1;
          } else if(ej.success) {
            ej.success(data,pobj);
            return 0;
          } else {
            alert(ej.success);
          }
          break;
        default:
          alert("Status code "+x.status+" - "+x.statusText+".");
        }
      }
    }
  }
}
