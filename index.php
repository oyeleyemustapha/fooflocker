<!DOCTYPE html>
<html>
<head>
  <title>FoodLocker</title>
   <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" media="screen">
   
       
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>


<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#">FoodLocker Inventory System</a>
    </div>

    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
       
      </ul>

      
     
    </div>
  </div>
</nav>
<div class="content">
<div class="dashboard">
	<div class="container-fluid">
		<div class="btn-group">
			<a href='index.php?action=add' class="btn btn-success">Add Product</a>
			<a href='index.php?action=add-sales' class="btn btn-info">Add Sales</a>
			<a href='index.php?action=fetch' class="btn btn-warning">Inventory</a>

		</div>
		
<div class="clearfix"></div>
<br>



		
	

<?php


require_once('function.php');

$db=dbconnect();

//ADD SALES
if(isset($_GET['action']) and $_GET['action']=='add-sales'){
	
	//FETCH THE LIST OF PRODUCTS TO LOG SALES FOR
	$result=$db->query('SELECT * from products');

	if($result->num_rows>0){
		echo'<form method="post" action="">
		<table class="table table-bordered">
			<thead>
				<tr>
					<th>ID</th>
					<th>PRODUCT</th>
					<th>CARTONS SOLD</th>
					<th>UNITS SOLD</th>
					
					
				</tr>
			</thead>
			<tbody>
		';

		$counter=1;
		while($row=$result->fetch_assoc()){
			$total_qty=($row['CARTONS']*$row['UNITS_CARTON'])+$row['UNITS'];
			$total_unit_cost=$total_qty*$row['UNIT_COST'];
			$total_unit_price=$total_qty*$row['UNIT_PRICE'];
			echo'
				<input type="hidden" name="carton_cost[]" value="'.$row['CARTON_COST'].'"> 
				<input type="hidden" name="carton_price[]" value="'.$row['CARTON_PRICE'].'"> 
				<input type="hidden" name="unit_cost[]" value="'.$row['UNIT_COST'].'"> 
				<input type="hidden" name="unit_price[]" value="'.$row['UNIT_PRICE'].'"> 
				<input type="hidden" name="product[]" value="'.$row['PRODUCT_ID'].'">

			';

			echo"


			<tr>
				<td>$counter</td>
				<td>".$row['PRODUCT']."</td>
				<td><input type='number' class='form-control' name='carton_sold[]'></td>
				<td><input type='number' class='form-control' name='unit_sold[]'></td>
			</tr>
			";
			$counter++;
		}



		echo'</tbody></table>
		<button class="btn btn-primary" name="addSales">Add Sales</button>
		</form>';
	}
}


//SUBMIT SALES ENTRY
if(isset($_POST['addSales'])){
	


	for ($i=0; $i <count($_POST['product']) ; $i++) { 
		$product=$_POST['product'][$i];
		$carton_sold=$_POST['carton_sold'][$i];
		$units_sold=$_POST['unit_sold'][$i];
		$carton_cost=$_POST['carton_cost'][$i];
		$carton_price=$_POST['carton_price'][$i];
		$unit_price=$_POST['unit_price'][$i];
		$unit_cost=$_POST['unit_cost'][$i];


		$sales_data=array(
							'PRODUCT'=>$product,
							'CARTON_SOLD'=>$carton_sold,
							'UNIT_SOLD'=>$units_sold,
							'CARTON_PRICE'=>$carton_price,
							'CARTON_COST'=>$carton_cost,
							'UNIT_PRICE'=>$unit_price,
							'UNIT_COST'=>$unit_cost
						);

		//FETCH THE AMOUNT OF UNITS AND CARTONS FOR THIS PRODUCTS SO AS TO SEE IF IT CAN CATER FOR THE SALES
		$result=$db->query("SELECT CARTONS, UNITS, PRODUCT from products WHERE PRODUCT_ID=$product");

		if($result->num_rows>0){
			if($row=$result->fetch_assoc()){
				$CARTON_QTY=$row['CARTONS'];
				$UNIT_QTY=$row['UNITS'];
				$PRODUCT=$row['PRODUCT'];


				if($carton_sold=='' and $units_sold==''){
					continue;
				}
				else{
					
					//THIS APPLY TO CARTON SALES ONLY
					if(isset($carton_sold) and $units_sold==''){
						if($sales_data['CARTON_SOLD']>$CARTON_QTY){
							echo'<div class="alert alert-danger">
							<p class="text-center">Reasons: <br>
								Carton Sold is more than Cartion Quantity in Stock for '.$PRODUCT.'
							</p>
							</div>';
							continue;		
						}
						else{
							$sales_data['UNIT_SOLD']=0;
							add_sales($sales_data);

							$data=array(
								'PRODUCT'=>$sales_data['PRODUCT'],
								'CARTON_SOLD'=>$sales_data['CARTON_SOLD']
							);
							update_carton_stock($data);
						}
					}
					//THIS APPLY TO UNITS SALE ONLY
					elseif (isset($units_sold) and $carton_sold=='') {
						$sales_data['CARTON_SOLD']=0;

						if($units_sold>$UNIT_QTY and $CARTON_QTY>0){
							add_sales($sales_data);
							$excess=$units_sold-$UNIT_QTY;
							$unit=$units_sold-$excess;
							//WE ARE GOING TO SACRIFICE ONE CARTOON
							$data=array(
								'EXCESS'=>$excess,
								'UNIT'=>$unit,
								'PRODUCT'=> $sales_data['PRODUCT']
							);
							deduce_carton($data);
						}
						elseif ($units_sold<=$UNIT_QTY) {
							$sales_data['CARTON_SOLD']=0;
							add_sales($sales_data);
							$data=array(
								'PRODUCT'=>$sales_data['PRODUCT'],
								'UNITS_SOLD'=>$sales_data['UNIT_SOLD']
							);
							update_unit_stock($data);
						}
						else{
							echo'<div class="alert alert-danger"><h1 class="text-center">The Available stock for '.$PRODUCT.' can not cater for the sales</h1></div>';
							continue;
						}
					}
					elseif(isset($units_sold) and isset($carton_sold)){
						if($carton_sold>$CARTON_QTY and $units_sold>$UNIT_QTY){
							echo'<div class="alert alert-danger"><p class="text-center">The Available stock for '.$PRODUCT.' can not cater for the sales</p>
							<p class="text-center">
							Reasons : <br>
							Carton Sold is more than Cartons in Stock <br>
							Units sold is more than Units in Stock
							</p></div>';
							continue;
						}
						elseif($carton_sold>$CARTON_QTY and $units_sold<=$UNIT_QTY){
							echo'<div class="alert alert-danger"><p class="text-center">The Available stock for '.$PRODUCT.' can not cater for the sales</p>
							<p class="text-center">
							Reasons : <br>
							Carton Sold is more than Cartons in Stock <br>
	
							</p>
							</div>';
							continue;
						}
						elseif($carton_sold<=$CARTON_QTY and $units_sold>$UNIT_QTY){
							echo'<div class="alert alert-danger"><p class="text-center">The Available stock for '.$PRODUCT.' can not cater for the sales</p>
							<p class="text-center">
							Reasons : <br>
							Units Sold is more than Units in Stock <br>
	
							</p></div>';
							continue;
						}
						else{
							add_sales($sales_data);
							$data=array(
								'PRODUCT'=>$sales_data['PRODUCT'],
								'UNITS_SOLD'=>$sales_data['UNIT_SOLD']
							);
							update_unit_stock($data);

							$data2=array(
								'PRODUCT'=>$sales_data['PRODUCT'],
								'CARTON_SOLD'=>$sales_data['CARTON_SOLD']
							);
							update_carton_stock($data2);
						}
				    }	
			    }
		    }
		}
    }
}


//ADD PRODUCTS
if(isset($_POST['add'])){

	$product=trim($_POST['product']);
	$cartons=trim($_POST['cartons']);
	$units=trim($_POST['units']);
	$units_per_carton=trim($_POST['unit_carton']);
	$carton_cost=trim($_POST['carton_cost']);
	$carton_price=trim($_POST['carton_price']);
	$unit_cost=trim($_POST['unit_cost']);
	$unit_price=trim($_POST['unit_price']);

	if($unit_price!=$carton_price/$units_per_carton){

		$query="INSERT INTO products VALUES(NULL,'$product', $cartons, $units, $units_per_carton, $carton_cost, $carton_price, $unit_cost, $unit_price);";

		$result=$db->query($query);
		if($result){
			echo'
			<div class="alert alert-success"><h1 class="text-center">Product has been added successfully</h1></div>
			';
		}
		else{
			echo mysqli_error($db);
		}
	}
	else{
		echo "Please Check Unit Price";
	}
}




if(isset($_GET['action']) and $_GET['action']=='add'){
	echo'
		<div class="col-lg-8 col-lg-offset-2">
			
			<form method="post" action="">
			
			<div class="form-group">
				<label>Product</label>
				<input type="text" name="product" required="" class="form-control form-control-lg">
			</div>
			<div class="form-group">
				<label>Cartons</label>
				<input type="number" name="cartons" required="" class="form-control form-control-lg">
			</div>
			<div class="form-group">
				<label>Units</label>
				<input type="number" name="units" required="" class="form-control form-control-lg">
			</div>
			<div class="form-group">
				<label>Units Per Carton</label>
				<input type="number" name="unit_carton" required="" class="form-control form-control-lg">
			</div>
			<div class="form-group">
				<label>Carton Cost</label>
				<input type="text" name="carton_cost" required="" class="form-control form-control-lg">
			</div>
			<div class="form-group">
				<label>Carton Price</label>
				<input type="text" name="carton_price" required="" class="form-control form-control-lg">
			</div>
			<div class="form-group">
				<label>Unit Cost</label>
				<input type="text" name="unit_cost" required="" class="form-control form-control-lg">
			</div>
			<div class="form-group">
				<label>Unit Price</label>
				<input type="text" name="unit_price" required="" class="form-control form-control-lg">
			</div>
			<button class="btn btn-primary" name="add">Add</button>
		</form>
		</div>




	';
}


//FETCH INVENTORY
if(isset($_GET['action']) and $_GET['action']=='fetch'){
	$result=$db->query('SELECT * from products');

	if($result->num_rows>0){
		echo'
		<table class="table table-bordered">
		<thead>
			<tr>
				<th>ID</th>
				<th>PRODUCT</th>
				<th>CARTONS</th>
				<th>UNITS</th>
				<th>UNITS PER CARTON</th>
				<th>CARTON COST</th>
				<th>CARTON PRICE</th>
				<th>UNIT COST</th>
				<th>UNIT PRICE</th>
				<th>TOTAL QUANTITY</th>
				<th>TOTAL INVENTORY COST</th>
				<th>TOTAL INVENTORY PRICE</th>
			</tr>
		</thead>
		<tbody>
		';

		$counter=1;
		while($row=$result->fetch_assoc()){
			$total_qty=($row['CARTONS']*$row['UNITS_CARTON'])+$row['UNITS'];
			$total_unit_cost=$total_qty*$row['UNIT_COST'];
			$total_unit_price=$total_qty*$row['UNIT_PRICE'];
			echo"
			<tr>
				<td>$counter</td>
				<td>".$row['PRODUCT']."</td>
				<td>".$row['CARTONS']."</td>
				<td>".$row['UNITS']."</td>
				<td>".$row['UNITS_CARTON']."</td>
				<td>".$row['CARTON_COST']."</td>
				<td>".$row['CARTON_PRICE']."</td>
				<td>".number_format($row['UNIT_COST'],2)."</td>
				<td>".number_format($row['UNIT_PRICE'],2)."</td>
				<td>$total_qty</td>
				<td>".number_format($total_unit_cost,2)."</td>
				<td>".number_format($total_unit_price,2)."</td>
			</tr>
			";
			$counter++;
		}
		echo'</tbody></table>';
	}
	else{
		echo'div class="alert alert-danger"><h2 class="text-center">NO PRODUCT FOUND</h2></div>';
	}
}




?>
		

		
		
		
	</div>
</div>
</div>

<br>
<footer><p class="text-center"></p></footer>

</body>
</html>