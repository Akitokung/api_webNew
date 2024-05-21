<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
  // header("Content-Type: application/json; charset=UTF-8");
  header("Content-Type: text/html; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);
      if ($data) {
        $json = file_get_contents('php://input');     //  อ่านไฟล์ JSON ที่ทางแอพจะส่งเข้ามา
        $arr = json_decode($json, true);              //  แปลงข้อมูลที่อ่านไฟล์ได้จาก JSON เข้า array ของ php

        $id_ordermain = mysqli_fetch_array(mysqli_query($Con_wang,"
          SELECT MAX(`soh_id`) AS id FROM `shopping_orderHead` WHERE 1
        "));
        $id = $id_ordermain['id']+1;
        $lock_id = "ALTER TABLE `shopping_orderHead` AUTO_INCREMENT=".$id;
        if($id == 0){$order_id = 1;}    
        if($id != 0){$order_id = $id;}

        $spo_memcode = $data['mem_code'];
        $spo_runing = '004-'.$order_id;   // หมายเลขคำสั่งจอง
        $spo_Cdatetime = date('Y-m-d H:i:s');
        $spo_site = 'Next';
        $ship = 1;      // ตั้งค่าเริ่มต้น  แบบร้านวังส่งให้
        $pay = 1;       // ตั้งค่าเริ่มต้น  แบบเครดิต


        echo '<p>spo_memcode = '.$spo_memcode.'</p>';
        echo '<p>spo_runing = '.$spo_runing.'</p>';
        echo '<p>spo_Cdatetime = '.$spo_Cdatetime.'</p>';
        echo '<p>spo_site = '.$spo_site.'</p>';

        echo '<hr>';

        // echo count($arr);
        // print_r($arr);
        echo '<p> shippingOption = '.$arr['shippingOption'].'</p>';
        echo '<p> paymentMethod = '.$arr['paymentMethod'].'</p>';
        echo '<p> address = '.$arr['user_info']['address'].'</p>';

        echo '<hr>';

        foreach ($arr['cart'] as $key => $value) {
          echo '<p> productId = '.$arr['cart'][$key]['productId'].'</p>';
          echo '<p> quantity = '.$arr['cart'][$key]['quantity'].'</p>';
          echo '<p> unit = '.$arr['cart'][$key]['unit'].'</p>';
          echo '<p> price = '.$arr['cart'][$key]['price'].'</p>';
          echo '<p> itemTotal = '.$arr['cart'][$key]['itemTotal'].'</p>';
          echo '<hr>';
        }
      }
    }
  }
?>