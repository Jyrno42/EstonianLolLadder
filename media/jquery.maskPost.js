(function($)
{
	
	$.fn.maskPost = function(options, blanket)
	{
		var settings = $.extend({
			"selector": ".error",
			"errorLabel": "Error: ",
			"confirm": false,
			"success": function (a,b,c,url)
			{
				if(url)
				{
					location.assign(url);
				}
				else
				{
					location.reload();
				}
			},
			"beforeSubmit": function ()
			{
				if(settings.confirm !== false)
				{
					return confirm(settings.confirm);
				}
			},
			"setResult": function (form, selector, value)
			{
				if($(form).children(selector).length)
				{
					if($(form).children(selector).get(0).tagName == "INPUT" || $(form).children(selector).get(0).tagName == "SELECT")
					{
						$(form).children(selector).val(value);	
					}
					else
					{
						$(form).children(selector).text(value);
						$(form).children(selector).parents(".alert").show();
					}
				}
				else
				{
					if($(selector).get(0).tagName == "INPUT" || $(selector).get(0).tagName == "SELECT")
					{
						$(selector).val(value);	
					}
					else
					{
						$(selector).text(value);
						$(selector).parents(".alert").show();
					}
				}
			}
		}, options);

		return this.each(
			function() 
			{
				var form = this;
				
			    $(form).ajaxForm({ dataType: "json", beforeSubmit: settings.beforeSubmit, success:
			    	function(responseText) 
			    	{
						console.log(responseText);
			    		if(responseText.error)
			    		{
			    			settings.setResult(form, settings.selector, settings.errorLabel + responseText.error);
			    		}
			    		else if(responseText.result || responseText.redirect)
			    		{
			    			settings.success(responseText.result || "", settings, form, responseText.redirect);
			    		}
			    		else
			    		{
			    			settings.setResult(form, settings.selector, "Bad: " + responseText);
			    		}
			    	},
			    	error: function(e)
			    	{
						alert(JSON.stringify(e));
						console.log(e);
			    		settings.setResult(form, settings.selector, "Something went terribly wrong!");
			    	}
			    });
			}
		);
	};
	
})(jQuery);