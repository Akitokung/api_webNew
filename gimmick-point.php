<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: * ");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');
  
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // token สำหรับ decode jwt
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);
      if ($data) {
        //echo $data['mem_code'];

        $sql = "SELECT `mem_RCoin` FROM `member` WHERE `mem_code`='".$data['mem_code']."'";
        $query = mysqli_query($Con_wang,$sql); 
        if (!$query) {http_response_code(404);}
        else {
          $result = mysqli_fetch_array($query);

          $json = array(
            'save_point' => number_format($result['mem_RCoin'],0,'.',''),
            'new_point' => array(),
          );

          $all_cart = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
              SUM(`spc_total`) AS Price 
            FROM 
              `shopping_cart` 
            WHERE 
              `spc_memcode`='".$data['mem_code']."' AND 
              `spc_amount`!='0'
          "));
          $check_cart = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
              SUM(`spc_total`) AS Price 
            FROM 
              `shopping_cart` 
            WHERE 
              `spc_memcode`='".$data['mem_code']."' AND 
              `spc_amount`!='0' AND 
              `spc_check`='1'
          "));

          $new_point = array(
            'all_cart' => number_format(($all_cart['Price']*0.01),0,'.',''),
            'check_cart' => number_format(($check_cart['Price']*0.01),0,'.',''),
          );
          $json['new_point'] = $new_point;

          mysqli_close($Con_wang);
          echo json_encode($json);
        }
      }
      else {
        http_response_code(404);
        echo 'error';
      }
    }
    else {
      // 404 = Not Found
      http_response_code(404);
    }
  }
?>
