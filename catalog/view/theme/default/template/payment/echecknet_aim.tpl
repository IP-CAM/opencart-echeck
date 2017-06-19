<h2><?php echo $text_bank_account; ?></h2>
<div class="content" id="payment">
	<table class="form">
		<tr>
			<td><?php echo $entry_account_type; ?></td>
			<td>
				<select name="account_type">
					<option value="CHECKING"><?php echo $option_checking; ?></option>
					<option value="BUSINESSCHECKING"><?php echo $option_business; ?></option>
					<option value="SAVINGS"><?php echo $option_savings; ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td><?php echo $entry_bank_name; ?></td>
			<td><input type="text" name="bank_name" value="" /></td>
		</tr>
		<tr>
			<td><?php echo $entry_account_name; ?></td>
			<td><input type="text" name="account_name" value="" /></td>
		</tr>
		<tr>
			<td><?php echo $entry_account_number; ?></td>
			<td><input type="text" name="account_number" value="" /></td>
		</tr>
		<tr>
			<td><?php echo $entry_routing_number; ?></td>
			<td><input type="text" name="routing_number" value="" /></td>
		</tr>
	</table>
</div>
<div class="buttons">
	<div class="right"><input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="button" /></div>
</div>
<script type="text/javascript"><!--
$('#button-confirm').bind('click', function() {
	$.ajax({
		url: 'index.php?route=payment/echecknet_aim/send',
		type: 'post',
		data: $('#payment :input'),
		dataType: 'json',		
		beforeSend: function() {
			$('#button-confirm').attr('disabled', true);
			$('#payment').before('<div class="attention"><img src="catalog/view/theme/default/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
		},
		complete: function() {
			$('#button-confirm').attr('disabled', false);
			$('.attention').remove();
		},				
		success: function(json) {
			if (json['error']) {
				alert(json['error']);
			}
			
			if (json['success']) {
				location = json['success'];
			}
		}
	});
});
//--></script>