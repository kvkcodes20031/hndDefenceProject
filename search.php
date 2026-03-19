<?php
session_start();
header('application-type: application/json');

if(!isset($_SESSION(['user_id']))){

echo json_encode(['sucess'=> false, 'error' => ["user not loged in "], "redirect" => "login.php"]);

exit 
}

$userPost = $_POST["user_entery"];

$connect = new POD("localhost", "farmglobedatabase", "root", "");

$sql = "SELECT * FROM products p

JOIN  product_listing pl

ON p.id = pl.id
where p.name like '%$userPost%'
ORDER by product_id ASC 
" ;

$stmt = $connect->prepare($sql);

$stmt->execute($userPost);
?>

