$.validator.addMethod("regex", function(value, element, regexp) {
    var regex = new RegExp(regexp);
    return this.optional(element) || regex.test(value);
}, 'Format is invalid');

$.validator.addMethod("unique", function(value, element, data) {
	return laravalidremote(value, element, data);
}, 'Format is invalid');

$.validator.addMethod("exists", function(value, element, data) {
	return laravalidremote(value, element, data);
}, 'Format is invalid');

function laravalidremote(value, element, data)
{
	var route = $(element).attr('data-route');
	var token = $(element).parents('form').find('[name="_token"]').first().val();
	var validStatus;
	$.ajax({
	  type: 'POST',
	  url: route,
	  data: {validationParameters: data, value: value, _token: token},
	  success: function(data){
	  	validStatus = data.valid;
	  },
	  fail: function(data){
	  	validStatus = false;
	  },
	  async:false
	});
	
	return validStatus;
}