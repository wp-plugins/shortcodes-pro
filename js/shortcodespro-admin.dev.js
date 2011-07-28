/**
* Shortcodes Pro Admin JS
*
* @package Shortcodes Pro
* @author Matt Varone
*/

jQuery(function() {
    var scp_class = ".shortcodespro-meta-box ";
    var elements = [];
    var behavior_id = scp_class + "#type";
    var attributes_id = scp_class + "#attributes";
    var attributes_box = "#attributes-values";
    var attributes_row = scp_class + ".sscattributes";
    var language_id = scp_class + "#language";
    var button_id = scp_class + "#button";
    var image_box = "#postimagediv";
    jQuery("#post_title").focus();
    jQuery(behavior_id + " option").each(function () {
        elements.push(jQuery(this).prop("value"))
    });
    jQuery(behavior_id).change(function () {
        var behaviour = jQuery(this).prop("value");
        filterElements(behaviour)
    });
    filterElements(jQuery(behavior_id).prop("value"));
    jQuery(attributes_id).change(function () {
        filterAttributes()
    });
    filterAttributes();
    jQuery(language_id).change(function () {
        var status = jQuery(attributes_id).prop("checked");
        if (status === true) {
            filterDesc(true)
        } else {
            filterDesc()
        }
    });
    filterAttributesTitle();
    filterRichEditorFields();
    jQuery("#button").change(function () {
        filterRichEditorFields()
    });
    jQuery(".add_attribute").live("click", function () {
        var slug = jQuery("#att_name").val();
        var label = jQuery("#att_label").val();
        var desc = jQuery("#att_desc").val();
        var type = jQuery("#att_type").val();
        var value = jQuery("#att_value").val();
        var options = jQuery("#att_options").val();
        if (slug.length < 1 || label.length < 1 || type.length < 1 || value.length < 1) {
            callForError();
            return false
        }
        if (type == "select" && options.length < 1) {
            callForError();
            return false
        }
        slug = stringToSlug(slug);
        var attribute = new Attribute(slug, label, desc, type, value, options);
        if (jQuery("#att_slug_" + slug).length > 0) {
            tb_remove();
            return false
        } else {
            addAttribute(attribute);
            filterAttributesTitle();
            tb_remove()
        }
    });
    jQuery(".update_attribute").live("click", function () {
        var old_slug = jQuery(this).prop("id").replace("att_", "");
        var slug = old_slug;
        var label = jQuery("#att_label").val();
        var desc = jQuery("#att_desc").val();
        var type = jQuery("#att_type").val();
        var value = jQuery("#att_value").val();
        var order = jQuery("#att_order_" + old_slug).val();
        var options = jQuery("#att_options").val();
        if (label.length < 1 || type.length < 1 || value.length < 1) {
            callForError();
            return false
        }
        if (type == "select" && options.length < 1) {
            callForError();
            return false
        }
        var attribute = new Attribute(slug, label, desc, type, value, options);
        updateAttribute(attribute, "#row_" + old_slug, order);
        tb_remove()
    });
    jQuery(".button.delete").live("click", function () {
        var answer = confirm("Delete this attribute?");
        if (answer) {
            var row = jQuery(this).parent().parent();
            var slug = row.prop("id").replace("row_", "");
            var attributes = jQuery("#attrvals").val();
            var attributes_a = attributes.replace("|%" + slug + "%", "");
            jQuery("#attrvals").val(attributes_a);
            var total = jQuery("#totalattr").val();
            var c = parseFloat(total) - 1;
            jQuery("#totalattr").val(c);
            row.remove();
            filterAttributesTitle()
        } else {
            return false
        }
    });
    jQuery("#att_type").live("change", function () {
        var selected = jQuery(this).val();
        filterOverlay(selected)
    });

    function filterElements(behaviour) {
        if (behaviour !== "insert-custom-code") {
            jQuery(attributes_box).hide();
            jQuery(".sscquicktag").show();
            jQuery(attributes_row).stop().fadeTo(50, 0.5, function () {
                jQuery(attributes_id).removeAttr("checked").prop("disabled", "disabled");
                jQuery(".sscdesclong,.sscwidth,.sscheight").hide()
            })
        } else {
            jQuery(".sscquicktag").hide();
            jQuery(attributes_row).stop().fadeTo(50, 1, function () {
                jQuery(attributes_id).removeAttr("disabled")
            })
        }
        jQuery.each(elements, function (index, value) {
            if (value === behaviour) {
                jQuery("#" + value).show()
            } else {
                jQuery("#" + value).hide()
            }
        })
    }
    function FormatNumberLength(num, length) {
        var r = "" + num;
        while (r.length < length) {
            r = "0" + r
        }
        return r
    }
    function filterAttributes() {
        var status = jQuery(attributes_id).prop("checked");
        if (status === true) {
            jQuery(attributes_box).show();
            if (jQuery("#button").is(":checked")) {
                jQuery(".sscdesclong,.sscwidth,.sscheight").show()
            }
            filterDesc(true)
        } else {
            jQuery(attributes_box).hide();
            jQuery(".sscdesclong,.sscwidth,.sscheight").hide();
            filterDesc()
        }
    }
    function filterDesc(showAttributes) {
        var language = jQuery(language_id).prop("value");
        switch (language) {
        case "php":
            jQuery(".sscinsert-php").show();
            jQuery(".sscinsert-css,.sscinsert-html").hide();
            if (jQuery("#button").is(":checked")) {
                jQuery(".sscexecute").show()
            }
            break;
        case "html":
            jQuery(".sscinsert-html").show();
            jQuery(".sscinsert-css,.sscinsert-php").hide();
            if (jQuery("#button").is(":checked")) {
                jQuery(".sscexecute").show()
            }
            break;
        case "css":
            jQuery(".sscinsert-css").show();
            jQuery(".sscinsert-html,.sscinsert-php").hide();
            jQuery(".sscexecute").prop("checked", "").hide();
            break
        }
        if (showAttributes === true) {
            jQuery(scp_class + ".attributes-desc").show()
        } else {
            jQuery(scp_class + ".attributes-desc").hide()
        }
    }
    function filterAttributesTitle() {
        var titles = jQuery(".scp-attributes-header");
        var total = jQuery("#totalattr").val();
        if (total > 0) {
            titles.show()
        } else {
            titles.hide()
        }
    }
    function filterRichEditorFields() {
        var fields = ".sscexecute,.sscprevent,.sscrowbutton,.sscdesc";
        var fields_atr = fields + ",.sscdesc,.sscdesclong,.sscwidth,.sscheight";
        if (jQuery("#button").is(":checked")) {
            if (jQuery("#attributes").is(":checked")) {
                jQuery(fields_atr).show()
            } else {
                jQuery(fields).show()
            }
        } else {
            jQuery(fields_atr).hide()
        }
    }
    function filterOverlay(selection) {
        if (selection == "select") {
            jQuery(".sscatt_options").show()
        } else {
            jQuery(".sscatt_options").hide()
        }
    }
    function Attribute(slug, label, desc, type, value, options) {
        this.slug = slug;
        this.label = label;
        this.desc = desc;
        this.type = type;
        this.value = value;
        this.options = options
    }
    function addAttribute(attribute) {
        var attributes = jQuery("#attrvals").val();
        var total = jQuery("#totalattr").val();
        var last = jQuery("#lastattr").val();
        var c = parseFloat(total) + 1;
        var l = parseFloat(last) + 1;
        var hidden = '<input type="hidden" name="att_desc_' + attribute.slug + '" value="' + attribute.desc + '" id="att_desc_' + attribute.slug + '"/><input type="hidden" name="att_type_' + attribute.slug + '" value="' + attribute.type + '" id="att_type_' + attribute.slug + '"/><input type="hidden" name="att_value_' + attribute.slug + '" value="' + attribute.value + '" id="att_value_' + attribute.slug + '"/><input type="hidden" name="att_options_' + attribute.slug + '" value="' + attribute.options + '" id="att_options_' + attribute.slug + '"/>';
        var prepend = '<tr class="sscrow" id="row_' + attribute.slug + '"><td width="25%"><input type="text" name="att_order_' + attribute.slug + '" id="att_order_' + attribute.slug + '" value="' + l + '" size="4" ></td><td width="25%"><input type="text" name="att_slug_' + attribute.slug + '" id="att_slug_' + attribute.slug + '" value="' + attribute.slug + '"readonly="readonly"></td><td width="25%"><input type="text" name="att_label_' + attribute.slug + '" id="att_label_' + attribute.slug + '" value="' + attribute.label + '"readonly="readonly"></td><td width="25%"><a title="Edit Attribute" class="edit button thickbox" href="admin-ajax.php?action=scpeditattribute&width=640&id=' + attribute.slug + '">Edit</a> <a title="Delete Attribute" class="delete button" href="#">Delete</a>' + hidden + "</td></tr>";
        jQuery(".sscadd-new-attributed").before(prepend);
        jQuery("#totalattr").val(c);
        jQuery("#lastattr").val(l);
        jQuery("#attrvals").val(attributes + "|%" + attribute.slug + "%")
    }
    function updateAttribute(attribute, row, order) {
        var hidden = '<input type="hidden" name="att_desc_' + attribute.slug + '" value="' + attribute.desc + '" id="att_desc_' + attribute.slug + '"/><input type="hidden" name="att_type_' + attribute.slug + '" value="' + attribute.type + '" id="att_type_' + attribute.slug + '"/><input type="hidden" name="att_value_' + attribute.slug + '" value="' + attribute.value + '" id="att_value_' + attribute.slug + '"/><input type="hidden" name="att_options_' + attribute.slug + '" value="' + attribute.options + '" id="att_options_' + attribute.slug + '"/>';
        var prepend = '<tr class="sscrow" id="row_' + attribute.slug + '"><td width="25%"><input type="text" name="att_order_' + attribute.slug + '" id="att_order_' + attribute.slug + '" value="' + order + '" size="3"></td><td width="25%"><input type="text" name="att_slug_' + attribute.slug + '" id="att_slug_' + attribute.slug + '" value="' + attribute.slug + '"readonly="readonly"></td><td width="25%"><input type="text" name="att_label_' + attribute.slug + '" id="att_label_' + attribute.slug + '" value="' + attribute.label + '"readonly="readonly"></td><td width="25%"><a title="Edit Attribute" class="edit button thickbox" href="admin-ajax.php?action=scpeditattribute&width=640&id=' + attribute.slug + '">Edit</a> <a title="Delete Attribute" class="delete button" href="#">Delete</a>' + hidden + "</td></tr>";
        jQuery(row).replaceWith(prepend)
    }
    function callForError() {
        jQuery("#errors").html("Please complete all required fields (*).")
    }
    function stringToSlug(str) {
        str = str.replace(/^\s+|\s+$/g, "");
        str = str.toLowerCase();
        var from = "àáäâèéëêìíïîòóöôùúüûñç·/_,:;";
        var to = "aaaaeeeeiiiioooouuuunc------";
        for (var i = 0, l = from.length; i < l; i++) {
            str = str.replace(new RegExp(from.charAt(i), "g"), to.charAt(i))
        }
        str = str.replace(/[^a-z0-9]/g, "").replace(/\s+/g, "").replace(/-+/g, "");
        return str
    }
});