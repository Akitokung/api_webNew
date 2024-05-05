<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: * ");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');
  require_once('../../shopping/calculator-cart.php');

  $json = file_get_contents('php://input');
  $input = json_decode($json, true); 

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);

      $mem = $data['mem_code'];   
      $pro = $input['pro_code'];

      if ($data) {

        $cart = mysqli_fetch_array(mysqli_query($Con_wang,"
          SELECT 
            * 
          FROM 
            `shopping_cart` 
          WHERE 
            `spc_memcode`='".$mem."' AND 
            `spc_procode`='".$pro."' AND 
            `spc_unit`='1'
        "));

        $amount = $cart['spc_amount']-1;
        $total = number_format($amount*$cart['spc_ppu'],2,'.','');

        if ($amount>0) {
          $up = mysqli_query($Con_wang,"
            UPDATE 
              `shopping_cart` 
            SET 
              `spc_amount`='".$amount."', 
              `spc_total`='".$total."' 
            WHERE 
              `spc_id`='".$cart['spc_id']."'
          ");
        }
        else {
          $sql = mysqli_query($Con_wang,"
            DELETE FROM 
              `shopping_cart` 
            WHERE 
              `spc_memcode`='".$mem."' AND 
              `spc_procode`='".$pro."'
          ");
        }

        $amount = mysqli_fetch_array(mysqli_query($Con_wang,"
          SELECT 
            COUNT(`spc_procode`) AS List,
            SUM(`spc_total`) AS Price 
          FROM 
            `shopping_cart` 
          WHERE 
            `spc_memcode`='".$mem."' AND 
            `spc_amount`!='0'
        "));
        $list = number_format($amount['List'],0,'.','');
        $price = number_format($amount['Price'],2,'.',',');
        mysqli_query("OPTIMIZE TABLE `shopping_cart`");
        $json = array(
          'List' => $list,
          'Price' => $price
        );
        mysqli_close($Con_wang);
        echo json_encode($json);
      }
    }
  }
?>
