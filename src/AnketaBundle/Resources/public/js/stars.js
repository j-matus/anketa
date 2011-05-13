
/**
 * @copyright Copyright (c) 2011 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @author     Tomi Belan <tomi.belan@gmail.com>
 */

(function ($) {
  var starWidth = 24;
  var starHeight = 24;

  var cancelText = "Zruš odpoveď";

  // aktivujeme CSS pravidla pre tento skript (este nez sa stranka donacitava)
  $('html').addClass('with-stars-js');

  $(document).ready(function () {
    // pridame hviezdicky do hviezdickovych otazok
    $('.stars.question').each(function () {
      var $question = $(this);
      var $options = $question.find('.option:not(.none) :radio');
      var $noneOption = $question.find('.option.none :radio');
      var $labels = $question.find('.option:not(.none)');

      function trim(str) {
        // remove all whitespace from str's beginning and end
        return str.replace(/^\s*|\s*$/g, "");
      }

      var $container = $('<div />', { 'class': 'stars-container', 'height': starHeight }).appendTo($question);
      $container.append($('<span />', {
        'class': 'cancel',
        'title': cancelText,
        'width': starWidth,
        'height': starHeight,
        'css': { 'left': ($options.length * starWidth) + 'px' }
      }));
      for(var i = 0; i < $options.length; i++) {
        $container.append($('<span />', {
          'class': 'star',
          'title': trim($labels.eq(i).text()),
          'width': starWidth,
          'height': starHeight,
          'css': { 'left': (i * starWidth) + 'px' },
          'data': { 'id': i }
        }));
      }
      var $cancel = $container.find('.cancel');
      var $stars = $container.find('.star');

      var redraw = function () {
        var active = -1;
        for(var i = 0; i < $options.length; i++) {
          if($options[i].checked) active = i;
        }
        for(var i = 0; i < $stars.length; i++) {
          $stars.eq(i).toggleClass('active', i <= active);
        }
        $container.removeClass('hover');
        $cancel.removeClass('hover');
        $cancel.toggle(active != -1);
      };
      var redrawHover = function () {
        var hover = $(this).data('id');
        $container.addClass('hover');
        for(var i = 0; i < $stars.length; i++) {
          $stars.eq(i).toggleClass('active', i <= hover);
        }
      }

      $options.bind({
        change: redraw
      });
      $noneOption.bind({
        change: redraw
      });
      $stars.bind({
        mouseover: redrawHover,
        mouseout: redraw,
        click: function () {
          $options[$(this).data('id')].checked = true;
          redraw();
        }
      });
      $cancel.bind({
        mouseover: function () {
          $cancel.addClass('hover');
          $stars.removeClass('active');
        },
        mouseout: redraw,
        click: function () {
          $noneOption[0].checked = true;
          redraw();
        }
      });

      redraw();
    });

    // na normalne otazky pridame linku "cancel"
    $('.question:has(:radio):not(.stars)').each(function () {
      var $question = $(this);
      var $cancel = $('<a />', {
        'href': '#',
        'text': cancelText,
        'click': function () {
          $question.find('.option.none :radio')[0].checked = true;
          $cancel.hide(100);
          return false;
        }
      });
      $cancel.hide();
      $cancel.insertAfter($question.find('.option:last'));

      function refresh() {
        if($question.find('.option:not(.none):has(:radio:checked)').length) {
          $cancel.show(100);
        }
        else {
          $cancel.hide(100);
        }
      }

      $question.find(':radio').bind('change', refresh);

      refresh();
    });
  });
})(jQuery);

