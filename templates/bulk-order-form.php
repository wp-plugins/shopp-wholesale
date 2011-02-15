<!-- SHOPP WS BULK ORDER FORM -->
<form method='post'>

	<table class='shopp-bulk-order-form-table'>

		<?php foreach ($bulk_order_data as $category_name => $product_list): ?>

			<tr>
				<th colspan='99'>
				<h2><?php echo $category_name; ?></h2>
				</th>
			</tr>
			
			<tr>
				<th>SKU</th>
				<th>Product Name</th>
				<th>Variation</th>
				<th>In Stock</th>
				<th>Unit Price</th>
				<th>Tax</th>
				<th>Price</th>
				<th>Order Qty</th>
			</tr>
		
			<?php foreach ($product_list as $product): ?>
			
				<tr>
					<td><?php echo $product->sku; ?></td>
					<th><?php echo $product->name; ?></th>
					<td><?php echo $product->label; ?></td>
					<td align='right'><?php echo $product->stock; ?></td>
					<td align='right'><?php echo $product->unit_price; ?></td>
					<td align='right'><?php echo $product->tax; ?></td>
					<td align='right'><?php echo $product->price; ?></td>
					<td>
						<input type='text' size='2' name='<?php echo $product->field_name; ?>'	value='<?php echo $product->field_value; ?>' />
					</td>
				</tr>
		
			<?php endforeach; ?>
		
			<tr>
				<td colspan='99'>&nbsp;</td>
			</tr>
		
		<?php endforeach; ?>
	
	</table>
	
	<div align='right'>
		<input type='submit'	name='". <?php echo ShoppWholesale::SUBMIT_KEY; ?> ."' value='Place Order' />
	</div>

</form>