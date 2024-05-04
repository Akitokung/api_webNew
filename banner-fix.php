<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: * ");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');

  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $token = getBearerToken();
    if (!empty($token)) {
      $key = "Akitokung";
      //สร้าง object ข้อมูลสำหรับทำ json
      $payload = array();

      $img = "SELECT * FROM `z_shopping_modal` WHERE `zsm_status`='0' LIMIT 1";
      $qimg = mysqli_query($Con_wang,$img);      $num_rows = mysqli_num_rows($qimg);       $num = 0;
      $rimg = mysqli_fetch_array($qimg);

      $site = 'https://www.wangpharma.com/Akitokung/';
      $payload['url_img'] = $site.$rimg['zsm_img'];
      $payload['redirect'] = 'https://www.wangpharma.com/Akitokung/shopping';
      $payload['datetime'] = date('Y-m-d H:i:s');


      mysqli_close($Con_wang);
      // return token ที่สร้าง
      $json = json_encode($payload);
      echo $json;
    }
  }
?>
