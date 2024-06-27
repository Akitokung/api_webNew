<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');

  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $token = getBearerToken();
    if (!empty($token)) {
      $payload = array(
        'shippingOption' => array()
      );
      $mem = mysqli_fetch_array(mysqli_query($Con_wang,"SELECT * FROM `member` WHERE `mem_code`='".$data['mem_code']."'"));

      if ($mem['mem_route']=='L16') {
        $sql = "
          SELECT * FROM `logintsc_shipping_type` WHERE `lst_L16`='1'
        ";
      }
      else {
        $sql = "
          SELECT * FROM `logintsc_shipping_type` WHERE `lst_show`='1'
        ";
      }

      $query = mysqli_query($Con_wang,$sql);
      if (!$query) {http_response_code(404);}
      $site = 'https://www.wangpharma.com/';
      while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {
        $payload2 = array(
          '_id' => $result['lst_id'],
          'code' => $result['lst_id'],
          'name' => $result['lst_type'],
          'price' => $result['lst_price'],
        );
        array_push($payload['shippingOption'],$payload2);
      }

      mysqli_close($Con_wang);
      // return token ที่สร้าง
      $json = json_encode($payload);
      echo $json;
    }
  }
?>
