
function ts3_connect(host,port,server_pass_needed,prompt_username, server_pass_i18n, nickname_i18n) {
    uri = 'ts3server://'+ host + '?port=' + port;
    if(server_pass_needed == true) {
		var pass = prompt(server_pass_i18n);
		if(pass == "" || pass == null || pass == undefined) {
			return;
		}
        uri = uri + '&pass=' + pass ;
    }
    if(prompt_username == true) {
		var name = prompt(nickname_i18n);
		if(name == "" || name == null || name == undefined) {
			return;
		}
        uri = uri + '&nickname=' + name;
    }

    var popup = window.open(uri);
	popup.close();


}

