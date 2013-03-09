{extends file="index.tpl"} 

{block name="content"}
   <style type="text/css">
      body {
		background-color: #f5f5f5;
        padding-top: 40px;
        padding-bottom: 40px;
      }
      .form-signin {
        max-width: 300px;
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

	<form class="form-signin maskpost" method="GET" action="API.php">
		<input type="hidden" name="action" value="Login2"/>
		<h2 class="form-signin-heading">Please sign in</h2>
		<div class="alert alert-block alert-error hide in">
			<button type="button" class="close">&times;</button>
			<h4 class="alert-heading"><span id="error"></span></h4>
		</div>
		<input name="login_args_email" type="text" class="input-block-level" placeholder="Email address">
		<input name="login_args_password" type="password" class="input-block-level" placeholder="Password">
		<button class="btn btn-large btn-primary" type="submit">Sign in</button>
	</form>
{/block}