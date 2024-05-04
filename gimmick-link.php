<?php
  require_once('../../Akitokung/00-connection.class.sqli.php');
  
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    header("Access-Control-Allow-Origin: * ");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // token สำหรับ decode jwt
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);
      if ($data) {
        $spc_memcode = $data['mem_code'];

        $sql = "
          SELECT 
            SUM(`spc_total`) AS `Price`
          FROM 
            `shopping_cart`
          WHERE 
            `spc_check`='1' AND 
            `spc_memcode`='".$spc_memcode."'
        ";

        // echo $sql.'<br/>';

        $query = mysqli_query($Con_wang,$sql);      $num_rows = mysqli_num_rows($query);
        if (!$query) {http_response_code(404);}
        $json = array();
        // Akitokung
        $site = 'https://www.wangpharma.com/';

        if ($num_rows<=0) {
          http_response_code(401);
          echo 'ไม่ม่รายการสินค้า ในรถเข็น';
        }
        else {
          $result = mysqli_fetch_array($query,MYSQLI_ASSOC);

          $status = ($result['Price']>=2000)? true:false;

          if ($status) {
            $payload = array(
              'status' => $status,
              'total' => number_format($result['Price'],2,'.',','),
              'redirect' => $site,
            );
            array_push($json,$payload);

            mysqli_close($Con_wang);
            echo json_encode($json);
          }
          else {
            http_response_code(401);
            echo 'ยืนยันออเดอร์ หรือ แจ้งกลับไปซื้อเพิ่ม';
          }

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