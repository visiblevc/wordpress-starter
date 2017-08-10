/**
 *
 * @param usInstance Object an instance of underscore
 * @param templates Object a hash containing templates as arrays of html strings
 * @returns {{getTemplate: getTemplate}}
 * @constructor
 */
var WpmlTemplateCompiler = function (usInstance, templates) {
    var compiledTemplates = {};

    return {
        /**
         *
         * @param {string} temp
         * @returns {*|false} compiled underscore template if a template for the given
         * index was found, false if no such template exists
         */
        getTemplate: function (temp) {
            if (!templates.hasOwnProperty(temp)) {
                throw 'No such template: ' + temp;
            }
            if (compiledTemplates[temp] === undefined) {
                var template = templates[temp];
                if (template instanceof Array) {
                    template = template.join("\n");
                }
                compiledTemplates[temp] = usInstance.template(template);
            }
            return compiledTemplates[temp];
        }
    };
};