$(document).ready(function () {
	$('.tabs').tabs();
});

function filter(countryNameInserted) {
	// show everything
	$('.card').show();

	// do not filter empty strings
	if(countryNameInserted == "") return;

	// filter the list by country name
	$('.card').hide().each(function(i, e){
		var countryName = $(e).attr('data-value').toLowerCase();

		if (countryName.indexOf(countryNameInserted.toLowerCase()) >= 0) {
			$(e).show();
		}
	});
}

// add commas to an integer number
function format(number) {
	var decimalSeparator = ".";
	var thousandSeparator = ",";
	var result = String(number);
	var parts = result.split(decimalSeparator);
	result = parts[0].split("").reverse().join("");
	result = result.replace(/(\d{3}(?!$))/g, "$1" + thousandSeparator);
	parts[0] = result.split("").reverse().join("");
	return parts.join(decimalSeparator);
}