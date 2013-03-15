{extends file="index.tpl"} 

{block name="content"}
   <style type="text/css">
      body {
        background-color: #f5f5f5;
        padding-top: 40px;
        padding-bottom: 40px;
      }
      .form-signin {
        max-width: 600px;
        padding: 19px 29px 29px;
        margin: 0 auto 20px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        -webkit-border-radius: 5px;
           -moz-border-radius: 5px;
                border-radius: 5px;
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
           -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
                box-shadow: 0 1px 2px rgba(0,0,0,.05);
      }
      .form-signin .form-signin-heading,
      .form-signin .checkbox {
        margin-bottom: 10px;
      }
      .form-signin input[type="text"],
      .form-signin input[type="password"] {
        font-size: 16px;
        height: auto;
        margin-bottom: 15px;
        padding: 7px 9px;
      }

    </style>
    
    {literal}
        <script type="text/javascript">
        
            $.fn.spin = function(opts) {
              this.each(function() {
                var $this = $(this),
                    data = $this.data();

                if (data.spinner) {
                  data.spinner.stop();
                  delete data.spinner;
                }
                if (opts !== false) {
                  data.spinner = new Spinner($.extend({color: $this.css('color')}, opts)).spin(this);
                }
              });
              return this;
            };
            var opts = {
                lines: 11, // The number of lines to draw
                length: 7, // The length of each line
                width: 2, // The line thickness
                radius: 4, // The radius of the inner circle
                corners: 1, // Corner roundness (0..1)
                rotate: 0, // The rotation offset
                color: '#000', // #rgb or #rrggbb
                speed: 1, // Rounds per second
                trail: 60, // Afterglow percentage
                shadow: false, // Whether to render a shadow
                hwaccel: false, // Whether to use hardware acceleration
                className: 'spinner', // The CSS class to assign to the spinner
                zIndex: 2e9, // The z-index (defaults to 2000000000)
                top: '0px', // Top position relative to parent in px
                left: '0px' // Left position relative to parent in px
            };

            $(document).ready(function() { 

                $("#addSummoners button").click(function () {
                    $(this).button('loading');
                    
                    var txt = $(this).parents("form").find("textarea").val();
                    var lines = $.grep(txt.split(/\n|[\r\n]/), function(n, i) { return(n); });
                    var region = $("select[name=region]").val();
                    var todo = 0;
                    var done = 0;
                    
                    var errBox = $("#mainError #error");
                    errBox.html("Processing " + lines.length + " summoners from " + region + "...");
                    $("#mainError").show();
                    
                    for(var i = 0; i < lines.length; i++)
                    {
                        todo++;
                        // Create a new element into the box indicating this summoner.
                        $("#mainError").append(
                            $("<div>")
                                .css("height", "30px")
                                .data("name", lines[i])
                                .addClass("summoner")
                                .append($("<div>").addClass("theSpinner").spin(opts))
                                .append(
                                    $("<div>")
                                        .css("padding-left", "30px")
                                        .css("line-height", "20px")
                                        .text("Waiting for summoner " + lines[i])
                                        .addClass("theName")
                                )
                        );
                    }
                    
                    $(".summoner").each(
                        function () {
                            var elem = $(this);
                            var name = $(this).data("name");
                            $.getJSON("API.php?action=AddSummoner&region=" + region + "&name=" + name, 
                                function (data) {
                                    if (data.result)
                                    {
                                        elem.find(".theName").text("Added summoner " + name).addClass("text-success");
                                    }
                                    else
                                    {
                                        var msg = "Problem with summoner " + name;
                                        if(data.error)
                                        {
                                            msg = msg + ": " + data.error;
                                        }
                                        elem.find(".theName").text(msg).addClass("text-error");
                                    }
                                    elem.find(".theSpinner").remove();
                                    done++;
                                    on_finish(todo, done);
                                }
                            )
                            .error(
                                function()
                                {
                                    elem.find(".theName").text("Could not update summoner " + name).addClass("text-error");
                                    elem.find(".theSpinner").remove();
                                    done++;
                                    on_finish(todo, done);
                                }
                            );
                    });
                });

            });
            
            function on_finish(todo, done)
            {
                if (done >= todo)
                {
                    $("#addSummoners button").button('reset');
                }
            }
        </script>
    {/literal}


    <form class="form-signin" method="POST" action="API.php?action=AddSummoners" id="addSummoners">

        <h2 class="form-signin-heading">Enter Names</h2>
        
        <div class="well hide" id="mainError">
            <h5 class="alert-heading"><span id="error"></span></h5>
        </div>
        
        <label></label>
        <select name="region" class="input-block-level">
            <option value="euw">EUW</option>
            <option value="eune">EUNE</option>
        </select>
        
        <textarea name="summoner_names" type="text" class="input-block-level" placeholder="Summoner Names" rows="8"></textarea>
        
        <button type="button" class="btn btn-large btn-primary pull-right" data-loading-text="Loading...">Add Them</button>
        
        <div class="clearfix"></div>
    </form>
{/block}