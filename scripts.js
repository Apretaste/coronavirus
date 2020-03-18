$(document).ready(function () {
	$('.tabs').tabs();
});

function filter(countryNameInserted) {
	// show everything
	$('li.collection-item').show();

	// do not filter empty strings
	if(countryNameInserted == "") return;

	// filter the list by country name
	$('li.collection-item').hide().each(function(i, e){
		var countryName = $(e).attr('data-value').toLowerCase();

		if (countryName.indexOf(countryNameInserted.toLowerCase()) >= 0) {
			$(e).show();
		}
	});
}