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

        $sql = "
          SELECT 
            COUNT(`spc_procode`) AS List,
            SUM(`spc_total`) AS Price 
          FROM 
            `shopping_cart` 
          WHERE 
            `spc_memcode`='".$data['mem_code']."' AND 
            `spc_amount`!='0'
        ";
        $query = mysqli_query($Con_wang,$sql); 
        if (!$query) {http_response_code(404);}
        else {
          $num_rows = mysqli_num_rows($query);    $result = mysqli_fetch_array($query);
          $mem = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT `mem_RCoin` FROM `member` WHERE `mem_code`='".$data['mem_code']."'
          "));

          $point_cart = number_format(($result['Price']*0.01)+$mem['mem_RCoin'],0,'.','');

          $sql = "
            SELECT 
              COUNT(`spc_procode`) AS List,
              SUM(`spc_total`) AS Price 
            FROM 
              `shopping_cart` 
            WHERE 
              `spc_memcode`='".$data['mem_code']."' AND 
              `spc_amount`!='0' AND 
              `spc_check`
          ";


          $json = array(
            'pointA' => $point_cart,      // สะสม
            'pointB' => $point_cart,      // สะสม + เลือกในรถเข็น
            'pointC' => $point_cart,      // สะสม + เลือกในรถเข็น + ไม่เลือกในรถเข็น
          );

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
