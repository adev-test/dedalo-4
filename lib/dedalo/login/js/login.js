// JavaScript Document
$(document).ready(function() {
	
	// ENTER KEY : Submit
	$(".css_wrap_login").keydown(function(event) {
		var keycode = (event.keyCode ? event.keyCode : event.which);	//console.log(keycode)
		// ENTER
	    if (keycode == 13) {
			event.preventDefault();
			// LOGIN
			try{
				login.Login();    		
			} catch (e) {
				if (DEBUG) { console.log(e)};
			}
		}
	});

	// QUIT BUTTON EVENT	
	$('#quit').bind("click", function(event) {			
		login.Quit();
	});
	
	// FOCUS
	$(".css_wrap_login").fadeIn(500, function(){
		$('input:text').delay(600).focus();
	});
	
});



var login = new function() {

	this.url_trigger = DEDALO_LIB_BASE_URL + '/login/trigger.login.php' ;

	// QUIT DEDALO
	this.Quit = function() {
		
		var page_url 	= window.location.pathname;	
		
		var mode 		= 'Quit';
		var mydata		= { 'mode': mode };

		// AJAX REQUEST
		$.ajax({
			url		: this.url_trigger,
			data	: mydata,
			type	: "POST",
		})
		// DONE
		.done(function(received_data) {

			// Elimina las cookies que empiezan por ..  (component_autocomplete_ts)			
			clear_some_local_storage('component_autocomplete_ts');
			
			if(received_data=='ok') {
				if (DEBUG) console.log("Starting Dédalo Quit");							
				window.location.href = window.location ;
			}else{							
				alert( received_data );
			}			
		})
		// FAIL ERROR 
		.fail(function(error_data) {
			// Notify to log messages in top of page
			if (DEBUG) console.log("Error quit: " +error_data);	//if (DEBUG) console.log(data['responseText'].toSource());
			alert("Error on quit")
		})
		// ALLWAYS
		.always(function() {
			
		})	
	}
	
	/**
	* Login
	*/
	this.Login = function (obj) {
		
		var username			= $('.css_input_text').val();
		var password			= $('.css_password').val();			
		var tipo_login			= $('#tipo_login').val();
		var tipo_username		= $('#tipo_username').val();
		var tipo_password		= $('#tipo_password').val();
		var tipo_active_account	= $('#tipo_active_account').val();
		
		if(username == null || username.length <1) {
			$('#username').focus(); return false;
		}
		if(password == null || password.length <1)	 {
			$('#password').focus(); return false;
		}
			
		var target_div	= $('.login_ajax_response');

		if ( $(target_div).length != 1 ) { return alert("Error en login secuence.."); };	
		
		var mode 		= 'Login';
		var mydata		= { 'mode': mode, 'username': username, 'password': password, 'tipo_login': tipo_login, 'tipo_username': tipo_username, 'tipo_password': tipo_password, 'tipo_active_account': tipo_active_account };				
		
		//if (DEBUG) console.log("Login data vars: " + 'mode:'+ mode+ ' username:'+ username+ ' password:'+ password);		//return false;	
		target_div.html(' ').addClass('css_spinner');

		
		if (DEBUG) console.log("Starting Dédalo Login");

		if(DEDALO_BACKUP_ON_LOGIN==1) {
			//target_div.append("<span class='building_backup'>Building system backup</span>");
		}

		// AJAX REQUEST
		$.ajax({
			url		: this.url_trigger,
			data	: mydata,
			type	: "POST",
		})
		// DONE
		.done(function(received_data) {
			
			// Search 'error' string in response
			var error_response = /error/i.test(received_data);	//alert(error_response)

			// If received_data contains 'error' show alert error with (received_data), else reload the page
			if(error_response != false) {
				// ERROR : Show error
				target_div.fadeOut(500, function(){
					$(this).html("<span class='error'>" + received_data + "</span>");					
				})
			}else{
				// OK		
				target_div.empty().append("<span class='ok'> "+received_data+" </span>" );
				setTimeout(function() {
					window.location.href = window.location.href ;
				},1)				
			}			
		})
		// FAIL ERROR 
		.fail(function(error_data) {
			// Notify to log messages in top of page
			if (DEBUG) console.log("Error: "+error_data);
			alert("Error on Login " + error_data)

			target_div.append(" <span class='error'>Error on Login \n"+ error_data +"</span>");
		})
		// ALLWAYS
		.always(function() {
			target_div.removeClass('css_spinner');
		})

		return false;

	}//end Login method




}//end login class




