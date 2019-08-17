<?php

function add_sales($sales_data){
	$db=dbconnect();
	$product=$sales_data['PRODUCT'];
	$carton_sold=$sales_data['CARTON_SOLD'];
	$units_sold=$sales_data['UNIT_SOLD'];
	$carton_cost=$sales_data['CARTON_COST'];
	$carton_price=$sales_data['CARTON_PRICE'];
	$unit_price=$sales_data['UNIT_PRICE'];
	$unit_cost=$sales_data['UNIT_COST'];
	$query="INSERT INTO sales VALUES(NULL, $product, $carton_sold, $units_sold, $carton_cost, $carton_price, $unit_cost, $unit_price );";
	$result=$db->query($query);
	if($result){
		echo'<div class="alert alert-success"><h1 class="text-center">Sales has been added successfully</h1></div>';
	}
	else{
		echo mysqli_error($db);
	}
}

function deduce_carton($data){
	$db=dbconnect();
	$excess_unit=$data['EXCESS'];
	$product=$data['PRODUCT'];
	$query="UPDATE products set CARTONS=CARTONS-1, UNITS=UNITS_CARTON-$excess_unit WHERE PRODUCT_ID=$product";
	$result=$db->query($query);
	echo mysqli_error($db);
}

function update_unit_stock($data){
	$db=dbconnect();
	$unit_sold=$data['UNITS_SOLD'];
	$product=$data['PRODUCT'];
	$query="UPDATE products set UNITS=UNITS-$unit_sold WHERE PRODUCT_ID=$product";
	$result=$db->query($query);
	echo mysqli_error($db);
}

function update_carton_stock($data){
	$db=dbconnect();
	$carton_sold=$data['CARTON_SOLD'];
	$product=$data['PRODUCT'];
	$query="UPDATE products set CARTONS=CARTONS-$carton_sold WHERE PRODUCT_ID=$product";
	$result=$db->query($query);
	echo mysqli_error($db);
}




function dbconnect(){
	$db=new mysqli('localhost', 'root', '', 'foodlocker');
	if(mysqli_errno($db)>0){
		echo 'There is a problem connecting to the database :'.$db->mysqli_error();
	}
	return $db;
}






?>