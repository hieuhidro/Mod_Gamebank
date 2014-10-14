
var element = $("#popup-napcard");
var check = false;
function GB_showpopup(){
	if(!check){
		element = $("#popup-napcard");
		check = true;
	}else{
		$("body").append(element);
	}
	$("#popup-napcard").show();
}
function GB_closepopup(){
	$("#popup-napcard").remove();
}
