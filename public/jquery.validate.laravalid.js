$.extend($.validator.methods, {
	maxlength: function (value, element, param) {
		if (this.optional(element))
			return true;

		var length = (element.files && element.files.length) ? element.files[0].size / 1024
			: ($.isArray(value) ? value.length : this.getLength(value, element));
		return length <= param;
	}
});

// Return true if the field value matches the given format RegExp
$.validator.addMethod("pattern", function (value, element, param) {
	if (this.optional(element))
		return true;

	if (typeof param === "string")
		param = new RegExp(param.charAt(0) == "^" ? param : "^(?:" + param + ")$");
	return param.test(value);
}, "Invalid format.");

$.validator.addMethod("notEqualTo", function (value, element, param) {
	return this.optional(element) || !$.validator.methods.equalTo.call(this, value, element, param);
}, "Please enter a different value, values must not be the same.");

$.validator.addMethod("integer", function (value, element) {
	return this.optional(element) || /^-?\d+$/.test(value);
}, "A positive or negative non-decimal number please");

$.validator.addMethod("ipv4", function (value, element) {
	return this.optional(element)
		|| /^(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)\.(25[0-5]|2[0-4]\d|[01]?\d\d?)$/i.test(value);
}, "Please enter a valid IP v4 address.");

// Accept a value from a file input based on a required mime-type
$.validator.addMethod("accept", function (value, element, param) {
	// Browser does not support element.files and the FileList feature
	if (this.optional(element) || $(element).attr("type") !== "file" || !element.files || !element.files.length)
		return true;

	// Split mime on commas in case we have multiple types we can accept
	var typeParam = typeof param === "string" ? param.replace(/\s+/g, "") : "image/*",
		// Escape string to be used in the regex
		regex = new RegExp(".?(" + typeParam.replace(/[\-\[\]\/\{}\(\)\+\?\.\\\^\$\|]/g, "\\$&").replace(/,/g, "|").replace(/\/\*/g, "/.*") + ")$", "i");

	// Grab the mime-type from the loaded file, verify it matches
	for (var i = 0; i < element.files.length; i++) {
		if (!regex.test(element.files[i].type))
			return false;
	}

	// We've validated each file
	return true;
}, "Please enter a value with a valid mime-type.");
