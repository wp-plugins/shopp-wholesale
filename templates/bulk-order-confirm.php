<p>Please review your bulk order.</p>

<?php if (!empty($order_list)): ?>
	<?php $total_price = 0; ?>
	<?php $total_qty = 0; ?>
	<h3>Order Summary</h3>
	<table>
		<tr><th>Quantity</th> <th>Product</th> <th>Variation</th> <th>Unit Price</th> <th>Unit Tax</th> <th>Total</th> </tr>
		<?php foreach($order_list as $item): ?>
			<tr>
				<td><?php echo $item->quantity; ?></td>
				<td><?php echo $item->product_name; ?></td>
				<td><?php echo $item->variation_name; ?></td>
				<td><?php echo $item->unit_price; ?></td>
				<td><?php echo $item->tax; ?></td>
				<td><?php echo $item->total; ?></td>
				<?php $total_qty += $item->quantity; ?>
				<?php $total_price += floatvalue($item->total); ?>		
			</tr>
		<?php endforeach; ?>
		<tr><th><?php echo $total_qty; ?></th><td colspan='4'>&nbsp;</td><th><?php echo money($total_price); ?></th></tr>
	</table>
<?php endif; ?>

<?php if (!empty($backorder_list)): ?>
	<?php $total_price = 0; ?>
	<?php $total_qty = 0; ?>
	<br/>
	<h3>Backorder Items</h3>
	<p>The following items are not currently in stock<!-- and can be placed on backorder-->:</p>
	<table>
		<tr><th>Quantity</th> <th>Product</th> <th>Variation</th> <th>Unit Price</th> <th>Unit Tax</th> <th>Total</th> </tr>
		<?php foreach($backorder_list as $item): ?>
			<tr>
				<td><?php echo $item->quantity; ?></td>
				<td><?php echo $item->product_name; ?></td>
				<td><?php echo $item->variation_name; ?></td>
				<td><?php echo $item->unit_price; ?></td>
				<td><?php echo $item->tax; ?></td>
				<td><?php echo $item->total; ?></td>		
				<?php $total_qty += $item->quantity; ?>
				<?php $total_price += floatvalue($item->total); ?>
			</tr>
		<?php endforeach; ?>
		<tr><th><?php echo $total_qty; ?></th><td colspan='4'>&nbsp;</td><th><?php echo money($total_price); ?></th></tr>
	</table>
	<!-- <p><input type='checkbox' name='backorder' id='backorder'/><label for='backorder'><b>Yes, please place these items on backorder.</b></label></p> -->
<?php endif;?>

<p><div align='right'><input type='button' value='Proceed to Checkout' onclick="document.location='<?php echo shoppurl(false, 'cart'); ?>';"/></div></p>