$.validator.addMethod("regex", function(value, element, regexp) {
    var regex = new RegExp(regexp);
    return this.optional(element) || regex.test(value);
}, 'Format is invalid');