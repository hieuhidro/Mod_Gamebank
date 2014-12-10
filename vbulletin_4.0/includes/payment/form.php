<link href="/payment/css/custom.style.css" rel="stylesheet">
<form method="post" id="form-gamebank" action="/">
	<h2>Nạp thẻ điện thoại</h2>
	<div class="form-group">
		<label for="lstTelco">Chọn nhà mạng</label>
		<select id="lstTelco" name="lstTelco"  class="form-control">
			<option value="1">Viettel</option>
			<option value="2">MobiFone</option>
			<option value="3">Vinaphone</option>
			<option value="4">Gate</option>			
		</select>
	</div>

	<div class="form-group">
		<label for="txtSeri">Số serial</label>
		<input type="txtSeri" class="form-control" id="txtSeri" name="txtSeri" placeholder="Số serial" required>
	</div>
	<div class="form-group">
		<label for="">Nhập mã số</label>
		<input type="txtCode" class="form-control" id="txtCode" name="txtCode" placeholder="Mã số" required>
	</div>
	<input type="submit" class="btn btn-primary" name="payment" value="Nạp thẻ"/>
	<a href="/checkout.php" class="btn">Lịch sử</a>
</form>