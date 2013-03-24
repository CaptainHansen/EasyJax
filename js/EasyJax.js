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

function EasyJax (Url,req_type,runOnSuccess){
	this.Url = Url;
	this.runOnSuccess = runOnSuccess;
	this.post_obj = {};
	this.xmlHttp;
	this.req_type = req_type;
	
  /** submit_data
   * generates the xmlHttp request, creates a callback function, formats the data to be submitted as a JSON string,
   * and posts the data to the server
   */
	this.submit_data = function(){
		if(window.XMLHttpRequest) {
			this.xmlHttp = new XMLHttpRequest(); 
		} else {
			this.xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
	
		//data is a JSON data package returned to the client from the server.
		this.xmlHttp.onreadystatechange = this.createCallback();

		this.xmlHttp.open( this.req_type, this.Url, true );
		this.xmlHttp.setRequestHeader("Content-Type","application/json; charset=ISO-8859-1");
		this.xmlHttp.send( JSON.stringify(this.post_obj) );
	}
	
	this.createCallback = function (){
		var x = this.xmlHttp;
		var ros = this.runOnSuccess;
		return function (){
			if(x.readyState == 4) {
				switch(x.status) {
				case 200:
					try {
						var data = JSON.parse(x.responseText);
					} catch(err){
						alert("There was an error parsing JSON data.  Response Text shown below:\n\n"+x.response);
						return 1;
					}
					if(data.error != "" && data.error != undefined){
						alert(data.error);
						return 1;
					} else if(ros) {
						eval(ros)(data);
						return 0;
					} else {
						alert(ros);
					}
					break;
				case 500:
					alert("Status code 500 - Internal Server Error.");
					break;
				case 404:
					alert("Status code 404 - Destination script not found");
					break;
				}
			}
		}
	}
		

	this.set_send_data = function(name,value){
		this.post_obj[name] = value;
	}
}