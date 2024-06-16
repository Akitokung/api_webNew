<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");
  // header("Content-Type: text/html; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');
  require_once('../../shopping/calculator-cart.php');

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);
      if ($data) {
        $mem_code = $data['mem_code'];
        $json = file_get_contents('php://input');     //  อ่านไฟล์ JSON ที่ทางแอพจะส่งเข้ามา
        $arr = json_decode($json, true);              //  แปลงข้อมูลที่อ่านไฟล์ได้จาก JSON เข้า array ของ php
        // echo count($arr);
        // print_r($arr);

          $sql = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
              * 
            FROM 
              `product` 
            WHERE 
              `pro_code`='".$arr['productCode']."'
          "));

          if ($arr['variant']['unit']==$sql['pro_unit1']) {$spo_unit = 1;}
          else if ($arr['variant']['unit']==$sql['pro_unit2']) {$spo_unit = 2;}
          else if ($arr['variant']['unit']==$sql['pro_unit3']) {$spo_unit = 3;}

          $mem = $data['mem_code'];
          $pro = $sql['pro_code'];
          $num = (int)$arr['quantityUpdate'];
          $unit = $spo_unit;
          $sitee = 'New-Web';    
          $lat = '';    $long = '';
          $site = 'https://www.wangpharma.com/';

        if ($arr['statusUpdate']=='add') {     

          calculator_cart($mem,$pro,$num,$unit,$sitee,$lat,$long);
          $statusUpdate = 'add success';
        }
        else if ($arr['statusUpdate']=='del') {
          $cart = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
              * 
            FROM 
              `shopping_cart` 
            WHERE 
              `spc_memcode`='".$mem."' AND 
              `spc_procode`='".$pro."' AND 
              `spc_unit`='".$spo_unit."'
          "));

          $amount = $cart['spc_amount']-(int)$arr['quantityUpdate'];
          $total = number_format($amount*$cart['spc_ppu'],2,'.','');

            $up = mysqli_query($Con_wang,"
              UPDATE 
                `shopping_cart` 
              SET 
                `spc_amount`='".$amount."', 
                `spc_total`='".$total."' 
              WHERE 
                `spc_id`='".$cart['spc_id']."'
            ");

          $statusUpdate = 'del success';
        }
        else {
          $sql = mysqli_query($Con_wang,"
            DELETE FROM 
              `shopping_cart` 
            WHERE 
              `spc_memcode`='".$mem."' AND 
              `spc_procode`='".$pro."'
          ");
          $statusUpdate = 'delAll success';
        }

        $sql = "
          SELECT 
            *,
            `b`.`pro_priceTag` AS `p_tag` ,
            `b`.`pro_priceA` AS `p_a` , 
            `b`.`pro_priceB` AS `p_b` , 
            `b`.`pro_priceC` AS `p_c` 
          FROM 
            `shopping_cart` AS `a` 
            LEFT JOIN `product` AS `b` ON `a`.`spc_procode`=`b`.`pro_code`
            LEFT JOIN `product_drugmode` AS `c` ON `b`.`pro_mode`=`c`.`pd_code`
          WHERE 
            `a`.`spc_memcode`='".$mem."' AND 
            `a`.`spc_procode`='".$pro."' AND 
            `a`.`spc_unit`='".$spo_unit."'
        ";
        $query = mysqli_query($Con_wang,$sql);      $num_rows = mysqli_num_rows($query);
        $result = mysqli_fetch_array($query);

        if ($memd['mem_price']=='A') {$price = number_format($result['p_a'],2,'.','');}
        else if ($memd['mem_price']=='B') {$price = number_format($result['p_b'],2,'.','');}
        else if ($memd['mem_price']=='C') {$price = number_format($result['p_c'],2,'.','');}
        $originalPrice = ($result['p_tag']!=0)? number_format($result['p_tag'],2,'.',''):number_format($result['p_c'],2,'.','');
        $discount = $originalPrice-$price;

            $pro_imgm = str_replace('../',$site,$result['pro_img']);
            $sku = null;      $unit = null;      $barcode = null;
            if ($result['spc_unit']==1) {
              $sku = $result['pro_unit1'];
              $unit = $result['pro_unit1'];
              $barcode = $result['pro_barcode1'];

              $pro_img = str_replace('../',$site,$result['pro_imgU1']);
            }
            else if ($result['spc_unit']==2) {
              $sku = $result['pro_unit2'];
              $unit = $result['pro_unit1'];
              $barcode = $result['pro_barcode2'];

              $pro_img = str_replace('../',$site,$result['pro_imgU2']);
            }
            else if ($result['spc_unit']==3) {
              $sku = $result['pro_unit3'];
              $unit = $result['pro_unit1'];
              $barcode = $result['pro_barcode3'];

              $pro_img = str_replace('../',$site,$result['pro_imgU3']);
            }

            $pro_img = ($pro_img=='')? $pro_imgm:$pro_img;

            $pro_img = ($pro_img=='')? $pro_imgm:$pro_img;

            $tag_x = array();
            if ($result['pro_gs6']!=0) {$tag_x[] = 'แนะนำขาย';}
            if ($result['pro_gs7']!=0) {$tag_x[] = 'สินค้าขายดี';}
            if ($result['pro_gs8']!=0) {$tag_x[] = 'สินค้าใหม่';}
            if ($result['pro_gs3']!=0) {$tag_x[] = 'โปรโมชั่น ฯ';}

            $tag_ms = '';
            foreach ($tag_x as $key => $value) {
              $tag_ms .= '"'.$value;
              if ($key!=COUNT($tag_x)-1) {$tag_ms .= '",';}
              if ($key==COUNT($tag_x)-1) {$tag_ms .= '"';}
            }
            $tag_ms .= '';

            $status = ($result['pro_instock']>=$result['pro_limitA'])? 'show':'hide';
            $status = ($result['pro_show']==0)? 'show':'hide';
            $description_th = str_replace($bad,'',strip_tags($result['pro_details']));
            $title_th = ($result['pro_nameMain']!='')? $result['pro_nameMain']:$result['pro_nameTH'];
            $title_th = ($title_th!='')? $title_th:$result['pro_name'];
            $slug = ($result['pro_gs3']!=0)? 'โปรโมชั่น ฯ':'';
            $stock = (int)$result['pro_instock'];
            $stock = ($stock>1)? 999:0;

        $memd = mysqli_fetch_array(mysqli_query($Con_wang,"
          SELECT 
            * 
          FROM 
            `member` AS `a` 
            LEFT JOIN `member_phone` AS `b` ON `a`.`mem_code`=`b`.`mn_memcode`
          WHERE 
            `a`.`mem_code`='".$mem."'
          LIMIT 
            1
        "));

        $json = array(
          'prices' => array(
          'price' => (float)$price,
          'originalPrice' => (float)$originalPrice,
          'discount' => (float)$discount,
          ),
          'image' => $pro_imgm,
          'tag' => array(),
          'status' => $status , 
          '_id' => $result['pro_id'],
          'productId' => $result['pro_id'],
          'productCode' => $result['pro_code'],
          'sku' => $sku,
          'barcode' => $barcode,
          'title' => $title_th,
          'slug' => $slug,
          'category' => array(
            '_id' => $result['pro_mode'], 
            'name' => array(
              'th' => $result['pd_name_TH'], 
              'en' => $result['pd_name_Eng'], 
            ),
          ),
          'stock' => $stock,
          'isCombination' => true,
          'createdAt' => $result['spc_datetime'],
          'updatedAt' => $result['spc_datetime'],
          'sales' => 0 , 
          '__v' => 0 ,
          'id' => $result['spc_id'],
          'variant' => array(
            'register' => $result['pro_drugregister'],
            'view' => (int)$result['pro_view'],
            'rating' => (float)number_format($result['pro_rating'],2,'.',''),

            'originalPrice' => (float)$originalPrice,
            'price' => (float)$price,
            'quantity' => $stock,
            'discount' => (float)$discount,
            'productId' => $result['pro_id'],
            'barcode' => $barcode,
            'sku' => $sku,
            'unit' => $unit,
            'image' => $pro_img,
          ),
          'price' => (int)number_format($result['spc_ppu'],0,'.',''),
          'originalPrice' => (float)$originalPrice,
          'quantityUpdate' => (int)number_format($result['spc_amount'],0,'.',''),
          'statusUpdate' => $statusUpdate,
        );
        array_push($json['tag'],$tag_ms);

        echo json_encode($json);
      }
    }
  }
?>