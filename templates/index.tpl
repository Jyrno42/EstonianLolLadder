<!DOCTYPE HTML>
<html>
    <head>
        <script type="text/javascript">
          var _kmq = _kmq || [];
          var _kmk = _kmk || 'c13911f9369abddc62387f3a8e89010b464ed9a5';
          function _kms(u){
            setTimeout(function(){
              var d = document, f = d.getElementsByTagName('script')[0],
              s = d.createElement('script');
              s.type = 'text/javascript'; s.async = true; s.src = u;
              f.parentNode.insertBefore(s, f);
            }, 1);
          }
          _kms('//i.kissmetrics.com/i.js');
          _kms('//doug1izaerwt3.cloudfront.net/' + _kmk + '.1.js');
        </script>
        
        
        <meta http-equiv="Content-Type" content="application/xhtml+xml;" />
        <meta charset="utf-8">
        
        <title>{$SITE_NAME}</title>
        
        <link href="{$MEDIA_URL}/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
        <link href="{$MEDIA_URL}/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet" media="screen">
        <link rel="stylesheet" href="{$MEDIA_URL}/font-awesome.min.css">
        <link rel="stylesheet" href="{$MEDIA_URL}/style.css">

        <!--[if IE 7]>
        <link rel="stylesheet" href="assets/css/font-awesome-ie7.min.css">
        <![endif]-->

        
        <style type="text/css">
            body {
                padding: 0;
                margin: 0;
            }
            .title {
                font-size: 16px;
                font-weight: bold;
                background: #B9C9FE;
                color: #039;
                padding-bottom: 8px;
                padding-top: 8px;
                text-align: center !important;
            }
            .title .pull-left {
                margin-left: 5px;
            }
            .title .pull-right {
                margin-right: 5px;
            }
            tr th {
                font-size: 14px;
                font-weight: normal;
                background: #B9C9FE;
                border-top: 4px solid #AABCFE;
                color: #039;
                padding: 8px;
            }
            tr td {
                background: #E8EDFF;
                border-top: 1px solid white;
                color: #669;
                padding: 8px;
            }
            tr:nth-child(odd) td {
                background: #f3f6ff;
            }
        </style>
        <script src="{$MEDIA_URL}/jquery-latest.js"></script>
        <script src="{$MEDIA_URL}/bootstrap/js/bootstrap.js"></script>
        <script src="{$MEDIA_URL}/spin.min.js"></script>
        <script type="text/javascript" src="{$MEDIA_URL}/jquery.tablesorter.js"></script> 
        
        <!--[if lt IE 9]><script language="javascript" type="text/javascript" src="{$MEDIA_URL}/plot/excanvas.js"></script><![endif]-->
        <script language="javascript" type="text/javascript" src="{$MEDIA_URL}/plot/jquery.jqplot.min.js"></script>
        <script type="text/javascript" src="{$MEDIA_URL}/plot/plugins/jqplot.donutRenderer.min.js"></script> 
        <link rel="stylesheet" type="text/css" href="{$MEDIA_URL}/plot/jquery.jqplot.css" />
    
        <script type="text/javascript">
            {literal}
            $(document).ready(function() {
                if ($("#myTable tr").length > 1)
                {
                    $("#myTable").tablesorter({
                        sortList: [[0,0]],
                        textExtraction: function(node) { 
                            if ($(node).data("rval")) {
                                return $(node).data("rval");
                            }
                            return $(node).text();
                        },
                        headers: {
                            1: { 
                                sorter: false 
                            },
                            2: { 
                                sorter: false 
                            }
                        }
                    }).bind("sortEnd", function () {
                        var idx = 1;
                        $("#myTable tbody tr td:first-child").each(function () {
                            $(this).text(idx);
                            idx = idx + 1;
                        });
                    }); 
                }
                $(".maskpost").maskPost({"errorLabel": "", "selector": "#error"});
                $(".my-tooltip").tooltip();
            });
            {/literal}
        </script>
    </head>
    <body>{strip}
        <script src="{$MEDIA_URL}/jquery.form.js"></script>
        <script src="{$MEDIA_URL}/jquery.maskPost.js"></script>
        {block name="content"}
            <h1>Hello, world!</h1>
        {/block}
    {/strip}</body>
</html>