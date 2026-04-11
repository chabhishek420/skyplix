function id(v) { return( document.getElementById(v) ); }
function $(v) { return(id(v)); }

function hide(id,hidden){
	if(hidden>0){
		$(id).style.display='none';
	}else{
		$(id).style.display='block';
	}
}

function toggle(id){
	if($(id).style.display=='none'){
		$(id).style.display='block'
	}else{
		$(id).style.display='none'
	}
}

function addref(id,name){
	var a = $(id);
	var c = document.createElement('span');
	c.innerHTML = '\n<p><input type="text" name="' + name + '[]" /></p>';
	a.appendChild(c);
}

function appendfrom(source,dest){
	var a = $(source);
	var b = $(dest);
	var c = document.createElement('span');
	c.innerHTML = a.innerHTML;
	b.appendChild(c);
}

function makecode(url){
	if($('campaign').value!=-1&&$('image').value!=-1){
		$('htmlbox').value='<img src="' + url + $('campaign').value + '/' + $('image').value + '" />';
		$('htmlbox').size=$('htmlbox').value.length;
		$('bbcodebox').value='[IMG]' + url + $('campaign').value + '/' + $('image').value + '[/IMG]';
		$('bbcodebox').size=$('bbcodebox').value.length;
	}else{
		$('htmlbox').value='';
		$('bbcodebox').value='';
	}
}