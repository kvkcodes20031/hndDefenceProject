<?php

session_start()

$role = isset($_SESSION['role']) ? trim($_SESSION['role']) : '';
if (!isset($_SESSION['user_id']) || strtolower($role) !== 'farmer') {
    echo json_encode(['success'=>false,'errors'=>['Unauthorized']]);
    exit;
}

if(!isset($_POST['product_name']))
{
    echo json_encode(['success'=>false,'errors'=>['Product name is required']]);
    exit;
}
if(!isset($_POST['product_price']) )
{
    echo json_encode(['success'=>false,'errors'=>['Product price is required']]);
    exit;
}
else if( !is_numeric($_POST['product_price'])){
  echo json_encode(['success'=>, 'errors'=>['Product price must be a number']]);
  exit;
}

if(!isset($_POST['product_quantity']) || !is_numeric($_POST['product_quantity']))
{
    echo json_encode(['success'=>false,'errors'=>['Product quantity is required and must be a number']]);
    exit;
}
if()

?>