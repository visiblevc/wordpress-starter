/**
 * Used on the language settings page, validates the text content of one row
 * of the language domains settings form and potentially sanitizes the contents
 * of the row's text field.
 * Un-checks the text fields validation checkbox in case the field contains an
 * empty string after sanitization.
 *
 * @param domainInput Object
 * @param domainCheckBox Object
 * @returns {{run: run}}
 * @constructor
 */
var WpmlDomainValidation = function (domainInput, domainCheckBox) {

    return {
        run: function () {
            var textInput = domainInput.val().match(/^(?:.+\/\/)?([\w\.-]*)/)[1];
            if (!textInput) {
                domainCheckBox.prop('checked', false)
            }
            domainInput.val(textInput ? textInput : '');
        }
    }
};