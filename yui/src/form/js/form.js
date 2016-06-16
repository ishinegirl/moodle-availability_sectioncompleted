/**
 * JavaScript for form editing course completed condition.
 *
 * @module moodle-availability_sectioncompleted-form
 */

M.availability_sectioncompleted = M.availability_sectioncompleted || {};
M.availability_sectioncompleted.form = Y.Object(M.core_availability.plugin);
M.availability_sectioncompleted.form.completed = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} groups Array of objects
 */
M.availability_sectioncompleted.form.initInner =  function(dropdowns) {
    this.dropdowns = dropdowns;
};

M.availability_sectioncompleted.form.getNode = function(json) {
  // Create HTML structure.
    var html = M.util.get_string('title', 'availability_sectioncompleted') + ' <span class="availability-group"><label>' +
            '<span class="accesshide">' + M.util.get_string('label_section', 'availability_sectioncompleted') + ' </span>' +
            '<select name="sectionnumber" title="' + M.util.get_string('label_section', 'availability_sectioncompleted') + '">' +
            '<option value="0">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.dropdowns.length; i++) {
        var dropdown = this.dropdowns[i];
        // String has already been escaped using format_string.
        html += '<option value="' + dropdown.id + '">' + dropdown.name + '</option>';
    }
    html += '</select></label>';
    var node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values.
    if (json.sectionnumber !== undefined &&
            node.one('select[name=sectionnumber] > option[value=' + json.sectionnumber + ']')) {
        node.one('select[name=sectionnumber]').set('value', '' + json.sectionnumber);
    }

    // Add event handlers (first time only).
    if (!M.availability_sectioncompleted.form.addedEvents) {
        M.availability_sectioncompleted.form.addedEvents = true;
        var root = Y.one('#fitem_id_availabilityconditionsjson');
        root.delegate('change', function() {
            // Whichever dropdown changed, just update the form.
            M.core_availability.form.update();
        }, '.availability_sectioncompleted select');
    }

    return node;
};

M.availability_sectioncompleted.form.fillValue = function(value, node) {
    value.sectionnumber = parseInt(node.one('select[name=sectionnumber]').get('value'), 10);
};

M.availability_sectioncompleted.form.fillErrors = function(errors, node) {
    var sectionnumber = parseInt(node.one('select[name=sectionnumber]').get('value'), 10);
    if (sectionnumber === 0) {
        errors.push('availability_sectioncompleted:error_selectsectionnumber');
    }
};