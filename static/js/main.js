$(document).ready(function(){
	
	show_notifications();
	
	setupZoom();

	$.jcorners(".panel", {radius:10});
	$.jcorners("input[type='button']", {radius:6});
	$.jcorners("input[type='submit']", {radius:6});
	$.jcorners(".notice", {radius:10});
	$.jcorners(".error", {radius:10});

	$('img.edit').click(function(){
		$(this).hide();
		$(this).parent().children('span').hide();
		$(this).parent().children('textarea.editor').ckeditor({path: 'static/js/ckeditor/', customConfig : 'static/js/ckeditor/config.js', toolbar: 'Standard', height: 300, width: 520});
	});

	$('input.datum').mask('99.99.9999');
	setup_multiplefileupload(1);
	setup_tablesorter();
	$('table.list').searchable();
	setTimeout( "build_flexbox('#getUser');", 500 );
	
});


function show_board( i ) {
	$('.view .list ul li.selected').removeClass('selected');
	$('.view .list ul li#l'+i).addClass('selected');
	$('.view .board ul li.selected').removeClass('selected');
	$('.view .board ul li#b'+i).addClass('selected');
}


function setup_tablesorter() {
	$('table.list').tablesorter({
		cssAsc: "sorted-asc",
		cssDesc: "sorted-desc"
	});
}


function setup_multiplefileupload( i ) {
	$('input[name="filecount"]').val( i );
	$('input.multiplefiles').change(function(){
		$(this).parent().children('ul.files-to-upload').append('<li>'+ $(this).val() +' <img src="static/images/delete.png" onClick="remove_uploadfile($(this),'+(i-1)+')"></li>');
		$(this).hide();
		$(this).after('<input type="file" name="file'+i+'" class="multiplefiles">');
		setup_multiplefileupload( i+1 );
	});
}


function remove_uploadfile( e, i ) {
	e.parent('li').fadeOut(200, function(){ $(this).remove(); });
	$('input[name="file'+ i +'"]').remove();
}


function show_notifications() {
	var displaytime = 4;
	$('.notification').fadeIn(500, function(){ $(this).show('pulsate',{times:1},1000); });
	setTimeout( "$('.notification').fadeOut(500);", 1500+displaytime*1000 );
}


function build_tabs( element ) {
	var tabs = element+' ul:first li';
	var tabcontainer = element+' div.tabcontainer';
	var isselected = false;
	$( tabs ).each(function(i){
		$( tabs + ':eq(' + i + ')' ).click(function(){
			choose_tab( i, tabs, tabcontainer );
		});
		if( $( tabs + ':eq(' + i + ')' ).hasClass('selected') )
			isselected = i;
	});
	if( !isselected )
		choose_tab( 0, tabs, tabcontainer );
	else
		choose_tab( isselected, tabs, tabcontainer );
}


function choose_tab( i, tabs, tabcontainer ) {
	$( tabcontainer ).removeClass('selected');
	$( tabcontainer + ':eq(' + i + ')' ).addClass('selected');
	$( tabs ).removeClass('selected');
	$( tabs + ':eq(' + i + ')' ).addClass('selected');
	$('#getUser_ctr').css({
		'width': $('#getUser input').css('width'),
		'top': $('#getUser input').css('height')
		});
}


function build_flexbox( element ) {
	$(element).flexbox('ajax.php?x=search-user', {
		showArrow: false,
		autoCompleteFirstMatch: false,
		watermark: 'Benutzer-Suche',
		onSelect: function() {  
			$('#inputfounduserid').val( this.getAttribute('hiddenValue') );  
			$('#foundusername').text( this.value );  
			$.ajaxq("ajax", {
				url: "ajax.php?x=get-userprofile&userid="+this.getAttribute('hiddenValue'),
				cache: false,
				success: function(html)
				{
					$("#userprofile").html(html).show();
				}
			});
		}
    });
	$.jcorners(element+" input", {radius:10});
}