/*!
 * Tri-State checkbox
 *
 * Copyright 2011, http://webworld-develop.blogspot.com/
 * Artistic License 2.0
 * http://www.opensource.org/licenses/artistic-license-2.0
 *
 * Date: Fr Apr 22 08:00:00 2011 -0200
**/

(function($)
{
    var settings = { 
		classes: { 
			checkbox: "customcheck", 
			checked: "customcheckfull", 
			partial: "customcheckpartial", 
			unchecked: "customchecknone" 
		}, 
		children: null 
    };

    var methods = 
    {
        init: function(options) 
        {
            return this.each(function()
            {
                if (options)
                	$.extend(settings, options);

                var main = $(this).hide();
                var ch = settings.classes.checked;
                var part = settings.classes.partial;
                var unch = settings.classes.unchecked;
                var start = unch;
                var disabled = settings.children.filter(":disabled");
                var enabled = settings.children.filter(":not(:disabled)");

                if (main.is(":checked"))
                {
                    start = (disabled.length > 0) ? part : ch;
                    enabled.attr("checked", true);
                }                
                else if (enabled.filter(":checked").length > 0)
                    start = (enabled.filter(":checked").length == enabled.length && disabled.length == 0) ? ch : part;

                var $this = $("<span class='" + settings.classes.checkbox + " " + start + "'></span>").insertBefore(main);                

                main.click(function() {
                	$this.trigger("click");
                });
                
                $this.click(function()
                {
                    if ($this.hasClass(ch) || ($this.hasClass(part) && disabled.length > 0))
                    {
                        $this.removeClass(ch).removeClass(part).addClass(unch);
                        enabled.attr("checked", false);
                        main.attr("checked", false);
                    }
                    else
                    {
                        if (disabled.length > 0)
                            $this.removeClass(unch).removeClass(ch).addClass(part);
                        else
                            $this.removeClass(unch).removeClass(part).addClass(ch);
                        
                        enabled.attr("checked", true);
                        main.attr("checked", true);
                    }
                });

                enabled.click(function()
                {
                    if ($(this).is(":checked"))
                    {
                        if (enabled.filter(":checked").length == enabled.length && disabled.length == 0)
                        {
                            $this.removeClass(part).removeClass(unch).addClass(ch);
                            main.attr("checked", true);
                        }
                        else
                        {
                            $this.removeClass(unch).addClass(part);
                            main.attr("checked", true);
                        }
                    } 
                    else
                    {
                        if (enabled.filter(":not(:checked)").length == enabled.length)
                        {
                            $this.removeClass(ch).removeClass(part).addClass(unch);
                            main.attr("checked", false);
                        }
                        else
                            $this.removeClass(ch).removeClass(unch).addClass(part);
                    }
                });
            });
        }
    };

    $.fn.tristate = function(method)
    {
        if (methods[method])
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        else if ( typeof method === 'object' || ! method ) 
          return methods.init.apply( this, arguments );
    }
})(jQuery);

function tristateClick(object)
{
}