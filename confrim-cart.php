<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");
  // header("Content-Type: text/html; charset=UTF-8");
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
        // echo count($arr);
        // print_r($arr);

        $id_ordermain = mysqli_fetch_array(mysqli_query($Con_wang,"
          SELECT MAX(`soh_id`) AS id FROM `shopping_orderHead` WHERE 1
        "));
        $id = $id_ordermain['id']+1;
        $lock_id = "ALTER TABLE `shopping_orderHead` AUTO_INCREMENT=".$id;
        if($id == 0){$order_id = 1;}    
        if($id != 0){$order_id = $id;}

        $spo_memcode = $data['mem_code'];

        $mem_RCoin = mysqli_fetch_array(mysqli_query($Con_wang,"
          SELECT * FROM `member` WHERE `mem_code`='".$spo_memcode."'
        "));

        $spo_runing = '004-'.$order_id;   // หมายเลขคำสั่งจอง
        $spo_Cdatetime = date('Y-m-d H:i:s');
        $spo_site = 'Next';
        $ship = 1;      // ตั้งค่าเริ่มต้น  แบบร้านวังส่งให้
        $pay = 1;       // ตั้งค่าเริ่มต้น  แบบเครดิต

        // echo '<p>spo_memcode = '.$spo_memcode.'</p>';
        // echo '<p>spo_runing = '.$spo_runing.'</p>';
        // echo '<p>spo_Cdatetime = '.$spo_Cdatetime.'</p>';
        // echo '<p>spo_site = '.$spo_site.'</p>';
        // echo '<hr>';
        // echo '<p> shippingOption = '.$arr['shippingOption'].'</p>';
        // echo '<p> paymentMethod = '.$arr['paymentMethod'].'</p>';
        // echo '<p> address = '.$arr['user_info']['address'].'</p>';
        // echo '<hr>';

        $soh_listsale = 0;        $soh_listfree = 0;       
        $soh_sumprice = 0;        $point_cart = 0;
        $point_free = 0;


        foreach ($arr['cart'] as $key => $value) {
          $pro_id = $arr['cart'][$key]['productId'];

          $pro = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
              * 
            FROM 
              `product` 
            WHERE 
              `pro_id`='".$pro_id."'
          "));

          $spo_procode = $pro['pro_code'];
          $spo_amount = $arr['cart'][$key]['quantity'];
          $spo_unit = 0;
          if ($arr['cart'][$key]['unit']==$pro['pro_unit1']) {$spo_unit = 1;}
          else if ($arr['cart'][$key]['unit']==$pro['pro_unit2']) {$spo_unit = 2;}
          else if ($arr['cart'][$key]['unit']==$pro['pro_unit3']) {$spo_unit = 3;}
          $spo_ppu = $arr['cart'][$key]['price'];
          $spo_discount = 0;
          $spo_total = $arr['cart'][$key]['itemTotal'];

            $in = "
              INSERT INTO 
                `shopping_order`(
                  `spo_runing`,
                  `spo_memcode`,
                  `spo_procode`,
                  `spo_amount`,
                  `spo_unit`,
                  `spo_ppu`,
                  `spo_discount`,
                  `spo_total`,
                  `spo_Cdatetime`,
                  `spo_site`
                ) VALUES (
                  '".$spo_runing."',
                  '".$spo_memcode."',
                  '".$spo_procode."',
                  '".number_format($spo_amount,2,'.','')."',
                  '".$spo_unit."',
                  '".number_format($spo_ppu,2,'.','')."',
                  '".number_format($spo_discount,2,'.','')."',
                  '".number_format($spo_total,2,'.','')."',
                  '".$spo_Cdatetime."',
                  '".$spo_site."'
                )
            ";
          mysqli_query($Con_wang,$in);

          if ($spo_discount==0) {$soh_listsale++;}
          else {$soh_listfree++;}

          $soh_sumprice += $spo_total;
          $point_cart += ($spo_total*0.01);

          // echo $in;

          // echo '<p> productId = '.$arr['cart'][$key]['productId'].'</p>';
          // echo '<p> quantity = '.$arr['cart'][$key]['quantity'].'</p>';
          // echo '<p> unit = '.$arr['cart'][$key]['unit'].'</p>';
          // echo '<p> price = '.$arr['cart'][$key]['price'].'</p>';
          // echo '<p> itemTotal = '.$arr['cart'][$key]['itemTotal'].'</p>';
          // echo '<hr>';
        }

        // echo '<hr>';

        $inh = "
          INSERT INTO 
            `shopping_orderHead`(
              `soh_runing`,
              `soh_memcode`,
              `soh_datetime`,
              `soh_site`,
              `soh_listsale`,
              `soh_listfree`,
              `soh_sumprice`,
              `soh_shiptype`,
              `soh_payment`
            ) VALUES (
              '".$spo_runing."',
              '".$spo_memcode."',
              '".$spo_Cdatetime."',
              '".$spo_site."',
              '".number_format($soh_listsale,2,'.','')."',
              '".number_format($soh_listfree,2,'.','')."',
              '".number_format($soh_sumprice,2,'.','')."',
              '".$ship."',
              '".$pay."'
            )
        ";
        mysqli_query($Con_wang,$inh);

        // echo $inh;
        // echo '<hr>';

        $point_cart = ($point_cart>=20)? $point_cart:'0';
        $RCoin = ($point_cart-$point_free)+$mem_RCoin['mem_RCoin'];
        $mem = "
          UPDATE 
            `member` 
          SET 
            `mem_RCoin`='".number_format($RCoin,0,'.','')."' 
          WHERE 
            `mem_code`='".$spo_memcode."'
        ";

        // echo $mem;

        mysqli_close($Con_wang);
        $sent = array(
          'invoice' => $spo_runing,
          'status' => 'Pending'
        );
        echo json_encode($sent);
      }
    }
  }
?>