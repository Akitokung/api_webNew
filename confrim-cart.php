<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");
  // header("Content-Type: text/html; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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
          SELECT 
            * 
          FROM 
            `member` AS `a` 
            LEFT JOIN `member_phone` AS `b` ON `a`.`mem_code`=`b`.`mn_memcode`
          WHERE 
            `a`.`mem_code`='".$spo_memcode."'
          LIMIT 
            1
        "));

        $spo_runing = '004-'.$order_id;   // หมายเลขคำสั่งจอง
        $spo_Cdatetime = date('Y-m-d H:i:s');
        $spo_site = 'Next';
        $ship = 1;      // ตั้งค่าเริ่มต้น  แบบร้านวังส่งให้
        $pay = 1;       // ตั้งค่าเริ่มต้น  แบบเครดิต

        $lg = mysqli_fetch_array(mysqli_query($Con_wang,"
          SELECT * FROM `logintsc_shipping_type` WHERE `lst_id`='".$ship."'
        "));

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
        }

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
        $last_id = mysqli_insert_id($Con_wang);

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

        $jsonx = array(
          'cart' => array(),
          'discount' => 0,
          '_id' => $spo_runing,
          'user_info' => array(),
          'shippingOption' => $lg['lst_type'],    // บริษัท ขนส่งที่เลือก Flash | POST | BEST | DHL | Wang
          'paymentMethod' => $soh_payment[($pay!='')? $pay:1],
          'status' => 'Delivered',        // สถานะขนส่ง
          'subTotal' => (int)number_format($soh_listsale+$soh_listfree,2,'.',''),            // มูลค่าสินค้า
          'shippingCost' => (float)number_format($lg['lst_price'],2,'.',''),
          'total' => (float)number_format($soh_sumprice,2,'.',''),        // มูลค่าสินค้า
          'user' => $mem_RCoin['mem_code'],
          'createdAt' => $spo_Cdatetime,
          'updatedAt' => $spo_Cdatetime,
          'invoice' => (int)$last_id,
          '__v' => 0,
        );

            $ic = "
              SELECT 
                * 
              FROM 
                `shopping_order` AS `a` 
                LEFT JOIN `product` AS `b` ON `a`.`spo_procode`=`b`.`pro_code`
              WHERE 
                `a`.`spo_runing`='".$spo_runing."'
            ";

            $qic = mysqli_query($Con_wang,$ic);  $num_rows = mysqli_num_rows($qic);
            while ($ric = mysqli_fetch_array($qic)) {
              $status = ($ric['pro_instock']>=$ric['pro_limitA'])? 'show':'hide';
              $status = ($ric['pro_show']==0)? 'show':'hide';

              $image = ($ric['pro_img']!='')? str_replace('../',$site,$ric['pro_img']):null;

              $sku = '';    $barcode = '';
              if ($ric['spo_unit']==1) {$sku = $ric['pro_unit1']; $barcode = $ric['pro_barcode1'];}
              else if ($ric['spo_unit']==2) {$sku = $ric['pro_unit2']; $barcode = $ric['pro_barcode2'];}
              else if ($ric['spo_unit']==3) {$sku = $ric['pro_unit3']; $barcode = $ric['pro_barcode3'];}

              $description_th = str_replace($bad,'',strip_tags($ric['pro_details']));
              $title_th = ($ric['pro_nameMain']!='')? $ric['pro_nameMain']:$ric['pro_nameTH'];
              $title_th = ($title_th!='')? $title_th:$ric['pro_name'];
              $slug = ($ric['pro_gs3']!=0)? 'โปรโมชั่น ฯ':'';

              $quantity = (int)$ric['pro_instock'];
              $quantity = ($quantity>1)? 999:0;

              $cart = array(
                'prices' => array(),
                'image' => $image , 
                'tag' => array(),
                'status' => $status , 
                '_id' => $ric['pro_id'],
                'productId' => $ric['pro_id'],
                'productCode' => $ric['pro_code'],
                'sku' => $sku,
                'unit' => $sku,
                'barcode' => $barcode,
                'title' => array(
                  'th' => $title_th,
                  'en' => $ric['pro_nameEng']
                ),
                'description' => array(
                  'th' => $description_th,
                  'en' => ''
                ),
                'slug' => $slug, 
                'category' => array(
                  '_id' => $ric['pro_mode'], 
                  'name' => array(
                    'th' => $ric['pd_name_TH'], 
                    'en' => $ric['pd_name_Eng'], 
                  ),
                ),
                'stock' => $quantity,
                'isCombination' => false,
                'createdAt' => $ric['spo_Cdatetime'], 
                'updatedAt' => $ric['spo_Cdatetime'], 
                '__v' => 0,
                'id' => $ric['spo_id'],
                'price' => (float)number_format($ric['spo_ppu'],2,'.',''),
                'originalPrice' => (float)number_format($ric['spo_discount'],2,'.',''),
                'quantity' => (int)number_format($ric['spo_amount'],0,'.',''),
                'itemTotal' => (int)number_format($ric['spo_amount'],0,'.',''),
              );
              $prices = array(
                'price' => (float)number_format($ric['spo_ppu'],2,'.',''),
                'originalPrice' => (float)number_format($ric['spo_discount'],2,'.',''),
                'discount' => (float)number_format($ric['spo_total'],2,'.',''),
              );
              array_push($cart['prices'],$prices);

              $tag_x = array();
              if ($ric['pro_gs6']!=0) {$tag_x[] = 'แนะนำขาย';}
              if ($ric['pro_gs7']!=0) {$tag_x[] = 'สินค้าขายดี';}
              if ($ric['pro_gs8']!=0) {$tag_x[] = 'สินค้าใหม่';}
              if ($ric['pro_gs3']!=0) {$tag_x[] = 'โปรโมชั่น ฯ';}
              $tag_ms = '[';
              foreach ($tag_x as $key => $value) {
                $tag_ms .= '"'.$value;
                if ($key!=COUNT($tag_x)-1) {$tag_ms .= '",';}
                if ($key==COUNT($tag_x)-1) {$tag_ms .= '"';}
              }
              $tag_ms .= ']';
              $cart['tag'][] = (COUNT($tag_x)==0)? '':$tag_ms;          
              array_push($jsonx['cart'],$cart);
            }

        $mem_address = ($mem_RCoin['mem_address']!='')? 'เลขที่ '.trim($mem_RCoin['mem_address']).' ':null;
        $mem_village = ($mem_RCoin['mem_village']!='')? 'หมู่ที่ '.trim($mem_RCoin['mem_village']).' ':null;
        $mem_alley = ($mem_RCoin['mem_alley']!='')? 'ซอย'.trim($mem_RCoin['mem_alley']).' ':null;
        $mem_road = ($mem_RCoin['mem_road']!='')? 'ถนน'.trim($mem_RCoin['mem_road']).' ':null;
        $mem_tumbon = ($mem_RCoin['mem_tumbon']!='')? 'ตำบล'.trim($mem_RCoin['mem_tumbon']).' ':null;
        $mem_amphur = ($mem_RCoin['mem_amphur']!='')? 'อำเภอ'.trim($mem_RCoin['mem_amphur']).' ':null;
        $mem_province = ($mem_RCoin['mem_province']!='')? 'จังหวัด'.trim($mem_RCoin['mem_province']).' ':null;
        $mem_post = ($mem_RCoin['mem_post']!='')? 'รหัสไปรษณีย์ '.trim($mem_RCoin['mem_post']).' ':null;
        $country = 'ประเทศไทย';

        $address = $mem_address.$mem_village.$mem_alley.$mem_road.$mem_tumbon.$mem_amphur.$mem_province.$mem_post.$country;

        $user_info = array(
          'code' => $mem_RCoin['mem_code'],
          'name' => $mem_RCoin['mem_name'],
          'contact' => $mem_RCoin['mem_name'],
          'email' => $mem_RCoin['mn_emailshop'],
          'address' => $address,
          'country' => $country,
          'city' => ($mem_RCoin['mem_province']!='')? trim($mem_RCoin['mem_province']):null,
          'zipCode' => ($mem_RCoin['mem_post']!='')? trim($mem_RCoin['mem_post']):null
        );
        mysqli_close($Con_wang);
        array_push($jsonx['user_info'],$user_info);
        echo json_encode($jsonx);
      }
    }
  }
?>