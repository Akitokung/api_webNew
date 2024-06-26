<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
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

        $s = ($_GET['s']!='')? $_GET['s']:date('Y-m-d');         $e = ($_GET['e']!='')? $_GET['e']:date('Y-m-d');  
        $start = ($_GET['start']!='')? $_GET['start']:0;        $end = ($_GET['end']!='')? $_GET['end']:12;
        
        $mem = mysqli_fetch_array(mysqli_query($Con_wang,"SELECT * FROM `member` WHERE `mem_code`='".$data['mem_code']."'"));

        $sql = "
          SELECT 
            `b`.*,
            SUM(`b`.`WH_receiveBox_TC_qtyBox`) AS `boxs`,
            SUM(`b`.`WH_receiveBox_TC_qtySub`) AS `list`,
            `c`.*,
            `c`.`id` AS `pro_id`
          FROM 
            `WH_receiveBox_TC` AS `b`
            LEFT JOIN `product` AS `c` ON `b`.`WH_receiveBox_TC_idPro`=`c`.`id`
          WHERE 
            `b`.`WH_receiveBox_TC_dateAdd` BETWEEN '".$s." 00:00:00' AND '".$e." 23:59:59'
          GROUP BY 
            `b`.`WH_receiveBox_TC_idPro`
          ORDER BY 
            `b`.`WH_receiveBox_TC_dateAdd`
          DESC
            LIMIT 
          ".$start." , ".$end."
        ";

        $site = 'https://www.wangpharma.com/';          $query = mysqli_query($Con_pharSYS,$sql);
        if (!$query) {http_response_code(404);}         $json = array();        $num_rows = mysqli_num_rows($query);

        if ($num_rows<=0) {
          $s = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d'))));    
          $e = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d'))));
          $sql = "
            SELECT 
              `b`.*,
              SUM(`b`.`WH_receiveBox_TC_qtyBox`) AS `boxs`,
              SUM(`b`.`WH_receiveBox_TC_qtySub`) AS `list`,
              `c`.*,
              `c`.`id` AS `pro_id`
            FROM 
              `WH_receiveBox_TC` AS `b`
              LEFT JOIN `product` AS `c` ON `b`.`WH_receiveBox_TC_idPro`=`c`.`id`
            WHERE 
              `b`.`WH_receiveBox_TC_dateAdd` BETWEEN '".$s." 00:00:00' AND '".$e." 23:59:59'
            GROUP BY 
              `b`.`WH_receiveBox_TC_idPro`
            ORDER BY 
              `b`.`WH_receiveBox_TC_dateAdd`
            DESC
              LIMIT 
            ".$start." , ".$end."
          ";
        }
        $query = mysqli_query($Con_pharSYS,$sql);
        if (!$query) {http_response_code(404);}
        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {
          $pro = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
              *,
              `b`.`pro_priceTag` AS `p_tag` ,
              `b`.`pro_priceA` AS `p_a` , 
              `b`.`pro_priceB` AS `p_b` , 
              `b`.`pro_priceC` AS `p_c` 
            FROM 
              `product` AS `b`
              LEFT JOIN `product_drugmode` AS `c` ON `b`.`pro_mode`=`c`.`pd_code`
            WHERE 
              `b`.`pro_code`='".$result['pcode']."'
          "));

          if ($mem['mem_price']=='A') {$price = number_format($pro['p_a'],2,'.','');}
          else if ($mem['mem_price']=='B') {$price = number_format($pro['p_b'],2,'.','');}
          else if ($mem['mem_price']=='C') {$price = number_format($pro['p_c'],2,'.','');}
          $originalPrice = ($pro['p_tag']!=0)? number_format($pro['p_tag'],2,'.',''):number_format($pro['p_c'],2,'.','');
          $discount = $originalPrice-$price;

          $status = ($pro['pro_instock']>=$pro['pro_limitA'])? 'show':'hide';
          $status = ($pro['pro_show']==0)? 'show':'hide';

          $description_th = str_replace($bad,'',strip_tags($pro['pro_details']));
          $title_th = ($pro['pro_nameMain']!='')? $pro['pro_nameMain']:$pro['pro_nameTH'];
          $title_th = ($title_th!='')? $title_th:$pro['pro_name'];
          $slug = ($pro['pro_gs3']!=0)? 'โปรโมชั่น ฯ':'';

          $quantity = (int)$pro['pro_instock'];
          $quantity = ($quantity>1)? 999:0;
          
          $payload = array(
            "prices" => array(
              "price" => (float)$price,
              "originalPrice" => (float)$originalPrice,
              "discount" =>  (float)$discount,
            ),
            "categories" => array(),
            "image" => array(),
            "tag" => array(),
            "variants" => array(),
            "status" => $status , 
            "_id" => $pro['pro_id'],
            "productId" => $pro['pro_id'],
            "productCode" => $pro['pro_code'],
            "sku" => "",
            "barcode" => "",
            "title" => array(
              "th" => $title_th,
              "en" => $pro['pro_nameEng']
            ),
            "description" => array(
              "th" => $description_th,
              "en" => ''
            ),
            "slug" => $slug, 
            "category" => array(
              "_id" => $pro['pro_mode'], 
              "name" => array(
                "th" => $pro['pd_name_TH'], 
                "en" => $pro['pd_name_Eng'], 
              ),
            ),
            "stock" => $quantity,
            "isCombination" => true,
            "createdAt" => $pro['pro_dateadd'], 
            "updatedAt" => date('Y-m-d'), 
            "sales" => 0,
            "__v" => 0
          );

          if ($pro['pro_glwa1']!=0) {$payload['categories'][] = 'ยาสามัญประจำบ้าน';}
          if ($pro['pro_glwa2']!=0) {$payload['categories'][] = 'ยาแผนโบราณ';}
          if ($pro['pro_glwa3']!=0) {$payload['categories'][] = 'อาหารเสริม';}
          if ($pro['pro_glwa4']!=0) {$payload['categories'][] = 'ยาอันตราย';}
          if ($pro['pro_glwa6']!=0) {$payload['categories'][] = 'ยาควบคุมพิเศษ';}
          if ($pro['pro_glwa7']!=0) {$payload['categories'][] = 'ยาตามใบสั่งแพทย์';}
          if ($pro['pro_glwa8']!=0) {$payload['categories'][] = 'ยาบรรจุเสร็จ';}
          if ($pro['pro_glwa9']!=0) {$payload['categories'][] = 'เครื่องมือแพทย์';}
          if ($pro['pro_glwa10']!=0) {$payload['categories'][] = 'เวชสำอาง';}

          $pro_img = str_replace('../',$site,$pro['pro_img']);
          if ($pro['pro_img']!='') {$payload['image'][] = $pro_img;}
          else {$pro_img = '';}

          $pro_imgU1 = str_replace('../',$site,$pro['pro_imgU1']);
          if ($pro['pro_imgU1']!='') {$payload['image'][] = $pro_imgU1;}

          $pro_imgU2 = str_replace('../',$site,$pro['pro_imgU2']);
          if ($pro['pro_imgU2']!='') {$payload['image'][] = $pro_imgU2;}

          $pro_imgU3 = str_replace('../',$site,$pro['pro_imgU3']);
          if ($pro['pro_imgU3']!='') {$payload['image'][] = $pro_imgU3;}

          $tag_x = array();
          if ($pro['pro_gs6']!=0) {$tag_x[] = 'แนะนำขาย';}
          if ($pro['pro_gs7']!=0) {$tag_x[] = 'สินค้าขายดี';}
          if ($pro['pro_gs8']!=0) {$tag_x[] = 'สินค้าใหม่';}
          if ($pro['pro_gs3']!=0) {$tag_x[] = 'โปรโมชั่น ฯ';}

          $tag_ms = '[';
          foreach ($tag_x as $key => $value) {
            $tag_ms .= '"'.$value;
            if ($key!=COUNT($tag_x)-1) {$tag_ms .= '",';}
            if ($key==COUNT($tag_x)-1) {$tag_ms .= '"';}
          }
          $tag_ms .= ']';

          $payload['tag'][] = (COUNT($tag_x)==0)? '':$tag_ms;

          $radio1 = $pro['pro_ratio1']/$pro['pro_ratio1'];
          $radio2 = $pro['pro_ratio1']/$pro['pro_ratio2'];
          $radio3 = $pro['pro_ratio1']/$pro['pro_ratio3'];

            if ($pro['pro_unit1']!='') {
              $payload_2 = array(
                'register' => $pro['pro_drugregister'],
                'view' => (int)$pro['pro_view'],
                'rating' => (float)number_format($pro['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($originalPrice,2,'.',''),
                'price' => (float)number_format($radio1*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio1*$discount,
                'productId' => $pro['pro_id'].'-0',
                'barcode' => $pro['pro_barcode1'],
                'sku' => null,
                'unit' => $pro['pro_unit1'],
                'image' => ($pro_imgU1!='')? $pro_imgU1:$pro_img
              );
              array_push($payload['variants'],$payload_2);
            }

            if ($pro['pro_unit2']!='') {

              $payload_2 = array(
                'register' => $pro['pro_drugregister'],
                'view' => (int)$pro['pro_view'],
                'rating' => (float)number_format($pro['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($originalPrice,2,'.',''),
                'price' => (float)number_format($radio2*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio2*$discount,
                'productId' => $pro['pro_id'].'-1',
                'barcode' => $pro['pro_barcode2'],
                'sku' => null,
                'unit' => $pro['pro_unit2'],
                'image' => ($pro_imgU2!='')? $pro_imgU2:$pro_img
              );
              array_push($payload['variants'],$payload_2);
            }

            if ($pro['pro_unit3']!='') {              
              $payload_2 = array(
                'register' => $pro['pro_drugregister'],
                'view' => (int)$pro['pro_view'],
                'rating' => (float)number_format($pro['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($originalPrice,2,'.',''),
                'price' => (float)number_format($radio3*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio3*$discount,
                'productId' => $pro['pro_id'].'-2',
                'barcode' => $pro['pro_barcode3'],
                'sku' => null,
                'unit' => $pro['pro_unit3'],
                'image' => ($pro_imgU3!='')? $pro_imgU3:$pro_img
              );
              array_push($payload['variants'],$payload_2);
            }

          array_push($json,$payload);
        }
        mysqli_close($Con_wang);
        echo json_encode($json);
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