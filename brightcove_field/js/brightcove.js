/**
 * @file browse.js
 * Creates actions - browse and remove for Video form fields.
 */
(function ($) {

  Drupal.brightcove_field = {};
  Drupal.brightcove_field.actions = {};
  var brightcove_field_settings;
  Drupal.brightcove_field.dialog = null;
  Drupal.brightcove_field.dialog_field_rel = null;

  Drupal.behaviors.brightcove_field_buttons = {
    attach: function(context, settings) {
      brightcove_field_settings = settings;
      $('.brightcove-field-browse-button', context).click(Drupal.brightcove_field.actions.browse);
      $('.brightcove-field-upload-button', context).click(Drupal.brightcove_field.actions.upload);
      $('.brightcove-field-remove-button', context).click(Drupal.brightcove_field.actions.remove);
      $('.form-text.brightcove-video-field').change(Drupal.brightcove_field.actions.change);
    }
  };

  Drupal.brightcove_field.actions.change = function() {
    var filt = $(this).attr('rel');
    var button = $('.brightcove-field-remove-button[rel*="' + filt + '"]');
    button.attr('disabled', '');
    button.removeClass('form-button-disabled');
  };

  Drupal.brightcove_field.actions.remove = function(event) {
    event.preventDefault();
    $('.' + $(this).attr('rel')).val('');
    $(this).attr('disabled', '');
    $(this).addClass('form-button-disabled');
  };

  Drupal.brightcove_field.actions.browse = function() {
  };

  Drupal.brightcove_field.actions.upload = function() {
    //event.preventDefault();
    //var id = $(this).attr('rel');
    //var field_name = $('.' + id).attr('data-field-name');
    //var frame = $('iframe[rel=' + id + ']');
    /*frame.attr('src', src);*/

    /*var path = Drupal.settings.basePath + 'brightcove_field/upload/' +
     brightcove_field_settings.brightcove_field[field_name].entity_type + '/' +
     brightcove_field_settings.brightcove_field[field_name].field_name + '/' +
     brightcove_field_settings.brightcove_field[field_name].entity_id + ' #brightcove-field-upload-form';*/

    /*if( !$('fieldset[rel="' + id + '"]').length ) {
     $('.brightcove-field-remove-button[rel="' + id + '"]').after('<fieldset rel="' + id + '" id="' + id + '"><legend>Upload form</legend><div class="content"></div></fieldset>');
     console.log($('fieldset#' + id));
     $('fieldset#' + id + ' .content').load(path, function() {

     Drupal.attachBehaviors($(this));
     });
     }*/

    /*var dialog = $('<div>').dialog({
     autoOpen: false,
     modal: true,
     open: function() {
     $(this).load(Drupal.settings.basePath + 'brightcove_field/upload/' +
     brightcove_field_settings.brightcove_field[field_name].entity_type + '/' +
     brightcove_field_settings.brightcove_field[field_name].field_name + '/' +
     brightcove_field_settings.brightcove_field[field_name].entity_id + ' #brightcove-field-upload-form',
     function() {
     Drupal.attachBehaviors($(this));
     });
     },
     height: 600,
     width: 950,
     close: function() {
     Drupal.brightcove_field.submit(id);
     $(this).remove();
     }
     });

     dialog.dialog('open');*/


    //Drupal.modalFrame.open({onSubmit: Drupal.brightcove_field.submit(id), url: Drupal.settings.basePath + 'brightcove_field/upload/' + Drupal.settings.brightcove_field[field_name].node_type + '/' + Drupal.settings.brightcove_field[field_name].field_name + '/' + Drupal.settings.brightcove_field[field_name].nid, width: 950, height: 600, autoFit: false});
    return false;
  };

  Drupal.brightcove_field.submit_browse = function(field_rel, data) {
    parent.jQuery("." + field_rel).val(data);
    parent.jQuery('.brightcove-field-remove-button[rel="' + field_rel + '"]').attr('disabled', '').removeClass('form-button-disabled');
  };

  Drupal.ajax.prototype.commands = {
    ui_dialog: function (ajax, response, status) {
      var wrapper = response.selector ? $(response.selector) : $(ajax.wrapper);
      var method = response.method || ajax.method;
      var effect = ajax.getEffect(response);
      var new_content;

      switch (method) {
        case 'dialog':
          var settings = response.settings || ajax.settings || Drupal.settings;
          Drupal.brightcove_field.dialog_field_rel = response.field_rel;
          Drupal.detachBehaviors(wrapper, settings);
      }

      if (Drupal.brightcove_field.dialog == null) {
        if (response.iframe) {
          new_content = $('<iframe id="' + response.id + '-iframe"/>').attr('src', response.data);
          new_content.attr('width', '100%');
          new_content.load(function() {
            try {
              Drupal.brightcove_field.dialog.dialog('option', 'title', 'Brightcove Video Browser');
            } catch(e) {}
          });
        }
        else {
          var new_content_wrapped = $('<div></div>').html(response.data);
          new_content = new_content_wrapped.contents();

          if (new_content.length != 1 || new_content.get(0).nodeType != 1) {
            new_content = new_content_wrapped;
          }
        }

        // Add the new content to the page.
        //wrapper[method](new_content);
        Drupal.brightcove_field.dialog = wrapper[method]({
          autoOpen: true,
          height: 600,
          width: 950,
          modal: true,
          show: 'fade',
          hide: 'fade',
          title: 'Loading...',
          open: function() {
            $(this).html(new_content);
            $(this).attr('rel', Drupal.brightcove_field.dialog_field_rel);
            new_content.attr('height', $(this).height() + 'px');
          },
          close: function() {
            Drupal.brightcove_field.dialog_field_rel = null;
            Drupal.brightcove_field.dialog = null;
            $(this).remove();
          }
        });

        // Immediately hide the new content if we're using any effects.
        if (effect.showEffect != 'show') {
          new_content.hide();
        }

        // Determine which effect to use and what content will receive the
        // effect, then show the new content.
        if ($('.ajax-new-content', new_content).length > 0) {
          $('.ajax-new-content', new_content).hide();
          new_content.show();
          $('.ajax-new-content', new_content)[effect.showEffect](effect.showSpeed);
        }
        else if (effect.showEffect != 'show') {
          new_content[effect.showEffect](effect.showSpeed);
        }

        // Attach all JavaScript behaviors to the new content, if it was successfully
        // added to the page, this if statement allows #ajax['wrapper'] to be
        // optional.
        if (new_content.parents('html').length > 0) {
          // Apply any settings from the returned JSON if available.
          var settings = response.settings || ajax.settings || Drupal.settings;
          Drupal.attachBehaviors(new_content.contents(), settings);
        }
      }

    },

    ui_dialog_close: function (ajax, response, status) {
      var dialog = parent.jQuery(response.selector);
      var dialog_type = response.dialog_type;

      switch (dialog_type) {
        case 'browse':
          Drupal.brightcove_field.submit_browse(dialog.attr('rel'), response.data);
        case 'upload':
      }

      dialog.dialog('close');
    }
  };

})(jQuery);