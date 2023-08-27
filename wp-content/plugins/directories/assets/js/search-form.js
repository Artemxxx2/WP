'use strict';

(function($) {
  DRTS.Search = DRTS.Search || {};
  DRTS.Search.keyword = DRTS.Search.keyword || function(selector) {
    var $field = $(selector);
    if (!$field.length) return;

    var $input = $field.find('.drts-search-keyword-text');
    if (!$input.length) return;

    var $clear = $field.find('.drts-clear').css('visibility', $input.val().length > 0 ? 'visible' : 'hidden'),
      $form = $field.closest('.drts-search-form'),
      $id = $form.find('.drts-search-keyword-id'),
      $taxonomy = $form.find('.drts-search-keyword-taxonomy'),
      datasets = [],
      options = {
        suggest_post: $field.data('suggest-post') === true,
        suggest_post_url: $field.data('suggest-post-url') || '',
        suggest_taxonomy: $field.data('suggest-taxonomy'),
        suggest_min_length: $field.data('suggest-min-length') || 2
      },
      getIcon = function getIcon(item, defaultIcon, allowParent) {
        var i, _icon;
        if (item.icon_src) {
          i = $('<img/>').attr('class', 'drts-icon drts-icon-sm').attr('src', item.icon_src);
        } else {
          _icon = item.icon || (allowParent ? item.parent_icon : false) || defaultIcon;
          if (_icon) {
            i = $('<i/>').attr('class', 'drts-icon drts-icon-sm ' + _icon);
            if (item.color) i.css({
              'background-color': item.color,
              color: '#fff'
            });
          }
        }
        return i;
      };

    if (options.suggest_post && options.suggest_post_url) {
      var post_header = $field.data('suggest-post-header') || '',
        post_icon = $field.data('suggest-post-icon') || null,
        post_num = $field.data('suggest-post-num') || 5,
        post_wildcard = $field.data('suggest-post-wildcard') || 'QUERY',
        post_templates = {},
        posts,
        post_bloodhound = {
          datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
          queryTokenizer: Bloodhound.tokenizers.whitespace,
          remote: {
            wildcard: post_wildcard,
            url: options.suggest_post_url
          },
          sufficient: post_num,
          identify: function identify(item) {
            return item.id;
          }
        };
      if ($field.data('suggest-post-prefetch-url')) {
        post_bloodhound.prefetch = {
          url: $field.data('suggest-post-prefetch-url')
        };
      }
      posts = new Bloodhound(post_bloodhound);
      if (post_header) {
        post_templates.header = $('<h4/>').text(post_header)[0].outerHTML;;
      }
      post_templates.suggestion = function(icon) {
        return function(item) {
          var div = $('<div/>').html($('<span />').text(item.title)),
            i = getIcon(item, icon);
          if (i) div.prepend(i);

          return div[0].outerHTML;
        };
      }(post_icon);
      datasets.push({
        name: 'post',
        display: 'title',
        templates: post_templates,
        source: function source(q, sync, async) {
          if (q.length >= options.suggest_min_length) {
            posts.search(q, sync, async);
          }
        }
      });
    }

    if (options.suggest_taxonomy) {
      var taxonomies = options.suggest_taxonomy.split(',');
      if (taxonomies && taxonomies.length) {
        $.each(taxonomies, function(i, taxonomy) {
          var url = $field.data('suggest-taxonomy-' + taxonomy + '-url');
          if (url) {
            var taxonomy_templates = {},
              taxonomy_terms = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title', 'pt'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                  url: url
                }
              });
            var header = $field.data('suggest-taxonomy-' + taxonomy + '-header');
            if (header) {
              taxonomy_templates.header = $('<h4/>').text(header)[0].outerHTML;
            }
            var icon = $field.data('suggest-taxonomy-' + taxonomy + '-icon');
            var show_count = $field.data('suggest-taxonomy-' + taxonomy + '-count');
            var show_parents = $field.data('suggest-taxonomy-' + taxonomy + '-parents');
            taxonomy_templates.suggestion = function(icon) {
              return function(item) {
                var title = item.title,
                  div,
                  i;
                if (show_count) {
                  title = title + ' (' + (item.count || '0') + ')';
                }
                if (show_parents && item.pt) {
                  title = item.pt.join(' -> ') + ' -> ' + title;
                }
                div = $('<div/>').html($('<span>').text(title));
                if (icon) {
                  i = getIcon(item, icon, true);
                  if (i) div.prepend(i);
                }

                return div[0].outerHTML;
              };
            }(icon);

            datasets.push({
              name: taxonomy,
              source: function source(q, sync, async) {
                if (q.length > 0) {
                  taxonomy_terms.search(q, sync, async);
                }
              },
              display: 'title',
              templates: taxonomy_templates,
              limit: $field.data('suggest-taxonomy-' + taxonomy + '-num') || 100
            });
          }

          // Show top level taxonomy terms?
          var topUrl = $field.data('suggest-taxonomy-top-' + taxonomy + '-url');
          if (topUrl) {
            var top_taxonomy_templates = {},
              top_taxonomy_terms = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: {
                  url: topUrl
                }
              });
            var icon = $field.data('suggest-taxonomy-' + taxonomy + '-icon');
            var show_count = $field.data('suggest-taxonomy-' + taxonomy + '-count');
            top_taxonomy_templates.suggestion = function(icon) {
              return function(item) {
                var title = item.title,
                  div,
                  i = getIcon(item, icon, true);
                if (show_count) {
                  title = title + ' (' + (item.count || '0') + ')';
                }
                div = $('<div/>').html($('<span>').text(title));
                if (i) div.prepend(i);

                return div[0].outerHTML;
              };
            }(icon);

            datasets.push({
              name: 'top-' + taxonomy,
              source: function source(q, sync, async) {
                if (!q.length) {
                  sync(top_taxonomy_terms.all()); // show all if no query
                }
              },
              display: 'title',
              templates: top_taxonomy_templates,
              limit: 100
            });
          }
        });
      }
    }

    if (datasets.length) {
      $input.typeahead({
        highlight: true,
        minLength: options.suggest_taxonomy ? 0 : options.suggest_min_length
      }, datasets);
      $input.on('typeahead:select', function(e, item, name) {
        if (name !== 'post') {
          console.log(name);
          if (name.indexOf('top-') === 0) {
            name = name.substr(4);
            // Need to manually show clear button since keyup does not fire when selecting top taxonomy term 
            $clear.css('visibility', $input.val().length > 0 ? 'visible' : 'hidden');
          }
          $taxonomy.val(name);
        } else {
          $taxonomy.val('');
        }
        if (item.url) {
          $input.closest('form').find('button[type=submit]').prop('disabled', true);
          window.location.href = item.url;
          return;
        }
        $id.val(item.id);
        $input.trigger('change').blur();
      }).on('keyup', function(e) {
        if (e.keyCode !== 13 && e.keyCode !== 27 && e.keyCode !== 32) {
          $taxonomy.val('');
          $id.val('');
          $clear.css('visibility', $input.val().length > 0 ? 'visible' : 'hidden');
        }
      }).on('typeahead:render', function() {
        $input.closest('.twitter-typeahead').addClass('twitter-typeahead-open');
      }).on('typeahead:close', function() {
        $input.closest('.twitter-typeahead').removeClass('twitter-typeahead-open');
      });
      $clear.click(function() {
        $input.typeahead('val', '').focus();
        $taxonomy.val('');
        $id.val('');
        $clear.css('visibility', 'hidden');
      });
    } else {
      $input.on('keyup', function(e) {
        if (e.keyCode !== 13 && e.keyCode !== 27 && e.keyCode !== 32) {
          $clear.css('visibility', $input.val().length > 0 ? 'visible' : 'hidden');
        }
      });
      $clear.click(function() {
        $input.val('').focus();
        $clear.css('visibility', 'hidden');
      });
    }
  };
})(jQuery);