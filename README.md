EasyJax
=======

Easy-to-use OO implementation of AJAX.  The goal of this tool is to simplify the process of exchanging data between a web browser and a server.

This code can be used by adding the EasyJax.js file in the js folder to a directory accessible via a web server and simply including the autoload.php script in a php script pointed to with the AJAX request.

A simple implementation of an AJAX echo is as easy as this:

##Client Side##

```javascript
function save_data(text_to_echo){
	//last argument in constructor is a callback function which is called if the EasyJax server-side script returns a 200 status code
	easyj = new EasyJax('/easyjax.php','POST',function(data) {
		alert(data.returned_text);
	},{'echo_me':text_to_echo});

	easyj.submit_data();
}
```

##Server Side##

```php
include("/path/to/EasyJax.php"); 

$easyj = new EasyJax();
$easyj -> set_ret_data("returned_text",$easyj -> getData('echo_me'));
$easyj -> send_resp();
```

EasyJaxFiles
============

Extension off of my EasyJax idea which simplifies file uploads by using AJAX and allows large files to be uploaded regardless of PHP's memory limit or value of upload_max_filesize.  I used this script to allow a client in Hawaii to upload a 400+ MB video file to my server in California.  I also use it to upload photos to my family's picture site in combination with my own progress bar code.

This code is very easy to setup.  The following code will allow you to upload a file of any size to a server's /tmp/ directory:

##Web Page##

```html
<div style="width: 0; height: 0; position: absolute;"><input type="file" id="files" multiple="multiple"></div>
<button id="btn">Upload Files</button>
<div id="allfiles"></div>
<div id="results"></div>
```

##Client Side JS (using jQuery)##

```javascript
function buttonClick(){
	$('#files').trigger('click');
}

$(document).ready(function(){
	$('#btn').click(buttonClick);
	$('#files').change(function(){
		ejf = new EasyJaxFiles('upload.php','POST',$('#files')[0].files);
		ejf.on('start',function(){
			$('#btn').off('click');
			$('#results').html('');
			$('#results').append('<div style="text-align: center;"><div id="allprogress">Process Initiated</div></div>');
		}).on('nextfile',function(){
			$('#results').append('<div class="file">File Upload Starting</div>');
		}).on('progress',function(s,file){
			$('.file:last').html('File "'+file.name+'" - '+s.percent.toFixed(0)+'% Complete');
			$('#allprogress').html(s.overallPercent.toFixed(0)+'% complete - '+s.current_file+' of '+s.num_files+' Uploaded');
		}).on('success',function(data){
			$('.file:last').html('File "'+data.name+'" Uploaded Successfully!');
			$('#allfiles').append("<div class='fname'>"+data.name+"</div>");
		}).on('error',function(data){
			$('.file:last').html('File "'+data.name+'" Not Uploaded! - '+data.error);
		}).on('finish',function(){
			$('#allprogress').html('Process Complete!');
			$('#btn').click(buttonClick);
		});
		ejf.upload();
	});
});
```

##Server Side##

```php
include("/path/to/EasyJaxFiles.php");

$ejf = new EasyJaxFiles();
$ejf -> downloadTo("/tmp/");
$ejf -> send_resp();
```