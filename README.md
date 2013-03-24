EasyJax
=======

Easy-to-use OO implementation of AJAX.  The goal of this tool is to simplify the process of exchanging data between a web browser and a server.

This code can be used by adding the EasyJax.js file in the js folder to a directory accessible via a web server and simply including the autoload.php script in a php script pointed to with the AJAX request.

A simple implementation of an AJAX echo is as easy as this:

##Client Side##

	function save_data(text_to_echo){
		//last argument in constructor is a callback function which is called if the EasyJax server-side script returns a 200 status code
		easyj = new EasyJax('/easyjax.php','POST',function(data) {
			alert(data.returned_text);
		});

		easyj.set_send_data('echo_me',text_to_echo);

		easyj.submit_data();
	}

##Server Side##

	include("/path/to/autoload.php"); 

	$easyj = new EasyJax\JSON();
	$easyj -> set_ret_data("returned_text",$easyj -> getData('echo_me'));
	$easyj -> send_resp();