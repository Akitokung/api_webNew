<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');
  require_once('../../shopping/calculator-cart.php');

  $json = file_get_contents('php://input');
  $input = json_decode($json, true); 

  $pro_code = $input['pro_code'];

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);
      if ($data) {

        $mem = $data['mem_code'];
        $pro = $pro_code;
        $num = 1;
        $unit = 1;
        $site = 'New-Web';    
        $lat = '';    
        $long = '';

        calculator_cart($mem,$pro,$num,$unit,$site,$lat,$long);

        $amount = mysqli_fetch_array(mysqli_query($Con_wang,"
          SELECT 
            COUNT(`spc_procode`) AS `List`,
            SUM(`spc_total`) AS `Price` 
          FROM 
            `shopping_cart` 
          WHERE 
            `spc_memcode`='".$mem."' AND 
            `spc_amount`!='0'
        "));

        $json = array(
          'List' => number_format($amount['List'],0,'.',''),
          'Price' => number_format($amount['Price'],2,'.',',')
        );
        mysqli_close($Con_wang);
        echo json_encode($json);
      }
    }
  }
?>
