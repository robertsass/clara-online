$(document).ready(function(){

	setTimeout( "try_refresh()", 10000 );

});


function try_refresh() {
	$.ajaxq ("queue", {
		url: "index.php?getstatus",
		cache: false,
		success: function(html)
		{
			if( html != 'underconstruction' )
				document.location.href='index.php';
			else
				setTimeout( "try_refresh()", 10000 );
		}
	});
}