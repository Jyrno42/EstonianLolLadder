{extends file="dashboard.tpl"} 

{block name="innerContent"}
	<style type="text/css">
		.box-middle {
			max-width: 600px;
			margin: 0 auto 20px;
			margin-top: 20px;
			background-color: #fff;
			border: 1px solid #e5e5e5;
			-webkit-border-radius: 5px;
			-moz-border-radius: 5px;
			border-radius: 5px;
			-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
			-moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
			box-shadow: 0 1px 2px rgba(0,0,0,.05);
		}
	</style>
	<div class="row">
		<div class="span12" style="margin-top: 40px">
			  <form action="" method="get">
				<input type="hidden" name="page" value="show_offers" />
				
				<div class="box-middle">
				  <div class="modal-header">
					<h2 class="box-middle-heading">Start here</h2>
				  </div>
				  <div class="modal-body">
					<label>Site</label>
					<select name="Site">
						{foreach from=$Sites item=Site}
							<option value="{$Site[0]}">{$Site[1]}</option>
						{/foreach}
					</select>
					
					<label>Category</label>
					<select name="Category">
						{foreach from=$Categories item=Cat}
							<option value="{$Cat->Id}">{$Cat->Name} ({$Cat->Amount})</option>
						{/foreach}
					</select>
				  </div>
				  <div class="modal-footer">
					<input type='submit' class="btn btn-primary" value="Next">
				  </div>
				</div>
			  </form>
		</div>
	</div>
{/block}