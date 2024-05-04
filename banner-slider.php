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
      $payload = array();

      $img = "SELECT * FROM `z_shopping_slide` WHERE `zss_start`>='".date('Y-m-d')."' AND `zss_end`<='".date('Y-m-d')."' ORDER BY `zss_id` DESC";
      $qimg = mysqli_query($Con_wang,$img);      $num_rows = mysqli_num_rows($qimg);       $num = 0;
      // echo $img;
      if ($num_rows==0) {
        $img = "SELECT * FROM `z_shopping_slide` WHERE 1 ORDER BY `z_shopping_slide`.`zss_id` DESC LIMIT 3";
        $qimg = mysqli_query($Con_wang,$img);      $num_rows = mysqli_num_rows($qimg);       $num = 0;

        $site = 'https://www.wangpharma.com/Akitokung/';
        while ($rimg = mysqli_fetch_array($qimg)) {
          $payload[$num]['procode'] = $rimg['zss_topic'];
          $payload[$num]['url_img'] = $site.$rimg['zss_img'];
          $payload[$num]['redirect'] = 'https://www.wangpharma.com/Akitokung/shopping';
          $payload[$num]['datetime'] = date('Y-m-d H:i:s');
          $num++;
        }
      }
      else {
        // Akitokung
        $site = 'https://www.wangpharma.com/Akitokung/';
        while ($rimg = mysqli_fetch_array($qimg)) {
          $payload[$num]['procode'] = $rimg['zss_topic'];
          $payload[$num]['url_img'] = $site.$rimg['zss_img'];
          $payload[$num]['redirect'] = 'https://www.wangpharma.com/Akitokung/shopping';
          $payload[$num]['datetime'] = date('Y-m-d H:i:s');
          $num++;
        }
      }
      mysqli_close($Con_wang);
      // return token ที่สร้าง
      $json = json_encode($payload);
      echo $json;
    }
  }
?>
