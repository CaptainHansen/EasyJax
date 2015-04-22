/** EasyJax Javascript class
 *
 * instantiate this class with the URL to post data to (PHP script using the EasyJax PHP class)
 * supply a callback function that will run if the script returns success.  If an error is returned,
 * it is announced using the alert() function
 * 
 * License: MIT
 */

function EasyJax (Url, req_type) {
  this.Url = Url;
  this.tx = {};
  this.rx = {};
  this._xmlHttp;
  this.req_type = req_type;
  this.aes = false;
  this.enc;
  this.csrf;
  this.ignoreRequestTrigger = false;
  this._longLoadTimeout = false;

  this.on = function (e, fn) {
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

  this.send = function () {
    if(window.XMLHttpRequest) {
      this._xmlHttp = new XMLHttpRequest();
    } else {
      this._xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    this._xmlHttp.onreadystatechange = this._createCallback();

    this._xmlHttp.open( this.req_type, this.Url, true );
    this._xmlHttp.setRequestHeader("X-Requested-With","XMLHttpRequest");
    this._xmlHttp.setRequestHeader("Content-Type","application/json; charset=utf-8");
    if (this.csrf) {
      this._xmlHttp.setRequestHeader("X-CSRF-Token", this.csrf);
    }
    this._xmlHttp.send(JSON.stringify(this.tx));

    var self = this;
    this._longLoadTimeout = setTimeout(function () {
      self.longLoad();
    }, 1000);
  }

  this.push = function(id,val){
    this.tx[id] = val;
    return this;
  }

  this._createCallback = function () {
    var self = this;
    return function (){
      if(this.readyState == 4) {
        if(this.response != "") {
          try {
            self.rx = JSON.parse(this.response);
          } catch (err) {
            self.rx = this.response;
          }
        }

        if (this.status == 0) {
          self.rx = "Connection error";
        } else {
          if(typeof EasyJax.requestTrigger == 'function' && !self.ignoreRequestTrigger)
            EasyJax.requestTrigger();
        }

        clearTimeout(self._longLoadTimeout);
        self.longLoadEnd();

        if (this.status >= 100 && this.status < 400) {
          return self.success();
        }

        if (this.status >= 400 || this.status < 100) {
          if(typeof self.rx == "string") {
            self.rx = {error: self.rx};
          }
          return self.error();
        }
      }
    }
  }
}

EasyJax.prototype.success = function () { alert("Success!"); }
EasyJax.prototype.error = function () { alert(this.rx.error); }
EasyJax.prototype.longLoad = function () {}
EasyJax.prototype.longLoadEnd = function () {}