{extends file="index.tpl"} 

{block name="content"}
   <style type="text/css">
      body {
        padding-top: 40px;
        padding-bottom: 40px;
      }
      .form-signin {
        max-width: {block name="width"}300px{/block};
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

<div class="form-signin">
	<h2 class="form-signin-heading">{block name="exception_label"}{/block}</h2>
	<p>{block name="exception_text"}{/block}</p>
	<p>{block name="exception_raw"}{/block}</p>
</form>
{/block}