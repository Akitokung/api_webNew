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

        $mem = mysqli_fetch_array(mysqli_query($Con_wang,"SELECT * FROM `member` WHERE `mem_code`='".$data['mem_code']."'"));

        $json = array(
          'products' => array(),
        );

        $search = $_GET['search'];
        $sql = "
          SELECT 
              *,
              `a`.`pro_priceTag` AS `p_tag` ,
              `a`.`pro_priceA` AS `p_a` , 
              `a`.`pro_priceB` AS `p_b` , 
              `a`.`pro_priceC` AS `p_c` 
          FROM 
            `product` AS `a`
            LEFT JOIN `product_drugmode` AS `b` ON `a`.`pro_mode`=`b`.`pd_code`
            LEFT JOIN `product_pharma` AS `c` ON `a`.`pro_code`=`c`.`pp_procode`
          WHERE 
            `a`.`pro_priceC`!='0' AND                             
            SUBSTR(`a`.`pro_name`,1,1)!='-' AND 
            SUBSTR(`a`.`pro_name`,1,3)!='ฟรี' AND 
            `a`.`pro_img`!='' AND 
            `a`.`pro_show`='0' AND 
            (
              `a`.`pro_code` LIKE '%".$search."%' OR
              `a`.`pro_barcode1` LIKE '%".$search."' OR
              `a`.`pro_barcode2` LIKE '%".$search."' OR
              `a`.`pro_barcode3` LIKE '%".$search."' OR
              `a`.`pro_name` LIKE '%".$search."%' OR
              `a`.`pro_nameTH` LIKE '%".$search."%' OR
              `a`.`pro_nameEng` LIKE '%".$search."%' OR
              `a`.`pro_nameMain` LIKE '%".$search."%' OR
              `a`.`pro_genericname` LIKE '%".$search."%' OR
              `a`.`pro_unit1` LIKE '%".$search."%' OR
              `a`.`pro_unit2` LIKE '%".$search."%' OR
              `a`.`pro_unit3` LIKE '%".$search."%' OR
              `a`.`pro_priceTag` LIKE '%".$search."%' OR
              `a`.`pro_details` LIKE '%".$search."%' OR
              `a`.`pro_keysearch` LIKE '%".$search."%' OR
              `c`.`pp_properties` LIKE '%".$search."%' OR
              `c`.`pp_how_to_use` LIKE '%".$search."%' OR
              `c`.`pp_caution` LIKE '%".$search."%' OR
              `c`.`pp_use_in_pregnant_women` LIKE '%".$search."%' OR
              `c`.`pp_side_effects` LIKE '%".$search."%' OR
              `c`.`pp_suggestion` LIKE '%".$search."%'
            )
        ";

        // echo $sql;
        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}        
         // Akitokung
        $site = 'https://www.wangpharma.com/';
        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {

          if ($mem['mem_price']=='A') {$price = number_format($result['p_a'],2,'.','');}
          else if ($mem['mem_price']=='B') {$price = number_format($result['p_b'],2,'.','');}
          else if ($mem['mem_price']=='C') {$price = number_format($result['p_c'],2,'.','');}
          $originalPrice = ($result['p_tag']!=0)? number_format($result['p_tag'],2,'.',''):number_format($result['p_c'],2,'.','');
          $discount = $originalPrice-$price;

          $status = ($result['pro_instock']>=$result['pro_limitA'])? 'show':'hide';
          $status = ($result['pro_show']==0)? 'show':'hide';

          $description_th = str_replace($bad,'',strip_tags($result['pro_details']));
          $title_th = ($result['pro_nameMain']!='')? $result['pro_nameMain']:$result['pro_nameTH'];
          $title_th = ($title_th!='')? $title_th:$result['pro_name'];
          $slug = ($result['pro_gs3']!=0)? 'โปรโมชั่น ฯ':'';

          $quantity = (int)$result['pro_instock'];
          $quantity = ($quantity>1)? 999:0;

          $prices = array(
            'prices' => array(
              'price' => (float)$price,
              'originalPrice' => (float)$originalPrice,
              'discount' => (float)$discount,
            ),

            'categories' => array(),
            'image' => array(),
            'tag' => array(),
            'variants' => array(),

            "status" => $status , 
            "_id" => $result['pro_id'],
            "productId" => $result['pro_id'],
            "productCode" => $result['pro_code'],
            "sku" => "",
            "barcode" => $result['pro_barcode1'],
            "title" => array(
              "th" => $title_th,
              "en" => $result['pro_nameEng']
            ),
            "description" => array(
              "th" => $description_th,
              "en" => ''
            ),
            "slug" => $slug, 
            "category" => array(
              "_id" => $result['pro_mode'], 
              "name" => array(
                "th" => $result['pd_name_TH'], 
                "en" => $result['pd_name_Eng'], 
              ),
            ),
            "stock" => $quantity,
            "isCombination" => true,
            "createdAt" => $result['pro_dateadd'], 
            "updatedAt" => date('Y-m-d'), 
            "sales" => 0,
            "__v" => 0
          );

          if ($result['pro_glwa1']!=0) {$prices['categories'][] = 'ยาสามัญประจำบ้าน';}
          if ($result['pro_glwa2']!=0) {$prices['categories'][] = 'ยาแผนโบราณ';}
          if ($result['pro_glwa3']!=0) {$prices['categories'][] = 'อาหารเสริม';}
          if ($result['pro_glwa4']!=0) {$prices['categories'][] = 'ยาอันตราย';}
          if ($result['pro_glwa6']!=0) {$prices['categories'][] = 'ยาควบคุมพิเศษ';}
          if ($result['pro_glwa7']!=0) {$prices['categories'][] = 'ยาตามใบสั่งแพทย์';}
          if ($result['pro_glwa8']!=0) {$prices['categories'][] = 'ยาบรรจุเสร็จ';}
          if ($result['pro_glwa9']!=0) {$prices['categories'][] = 'เครื่องมือแพทย์';}
          if ($result['pro_glwa10']!=0) {$prices['categories'][] = 'เวชสำอาง';}


          $pro_img = str_replace('../',$site,$result['pro_img']);
          if ($result['pro_img']!='') {$prices['image'][] = $pro_img;}
          else {$pro_img = '';}

          $pro_imgU1 = str_replace('../',$site,$result['pro_imgU1']);
          if ($result['pro_imgU1']!='') {$prices['image'][] = $pro_imgU1;}

          $pro_imgU2 = str_replace('../',$site,$result['pro_imgU2']);
          if ($result['pro_imgU2']!='') {$prices['image'][] = $pro_imgU2;}

          $pro_imgU3 = str_replace('../',$site,$result['pro_imgU3']);
          if ($result['pro_imgU3']!='') {$prices['image'][] = $pro_imgU3;}


          $tag_x = array();
          if ($result['pro_gs6']!=0) {$tag_x[] = 'แนะนำขาย';}
          if ($result['pro_gs7']!=0) {$tag_x[] = 'สินค้าขายดี';}
          if ($result['pro_gs8']!=0) {$tag_x[] = 'สินค้าใหม่';}
          if ($result['pro_gs3']!=0) {$tag_x[] = 'โปรโมชั่น ฯ';}

          $tag_ms = '[';
          foreach ($tag_x as $key => $value) {
            $tag_ms .= '"'.$value;
            if ($key!=COUNT($tag_x)-1) {$tag_ms .= '",';}
            if ($key==COUNT($tag_x)-1) {$tag_ms .= '"';}
          }
          $tag_ms .= ']';

          $prices['tag'][] = (COUNT($tag_x)==0)? '':$tag_ms;


          $radio1 = $result['pro_ratio1']/$result['pro_ratio1'];
          $radio2 = $result['pro_ratio1']/$result['pro_ratio2'];
          $radio3 = $result['pro_ratio1']/$result['pro_ratio3'];

            if ($result['pro_unit1']!='') {
              $payload_2 = array(
                'register' => $result['pro_drugregister'],
                'view' => (int)$result['pro_view'],
                'rating' => (float)number_format($result['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($originalPrice,2,'.',''),
                'price' => (float)number_format($radio1*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio1*$discount,
                'productId' => $result['pro_id'].'-0',
                'barcode' => $result['pro_barcode1'],
                'sku' => null,
                'unit' => $result['pro_unit1'],
                'image' => ($pro_imgU1!='')? $pro_imgU1:$pro_img
              );
              array_push($prices['variants'],$payload_2);
            }

            if ($result['pro_unit2']!='') {

              $payload_2 = array(
                'register' => $result['pro_drugregister'],
                'view' => (int)$result['pro_view'],
                'rating' => (float)number_format($result['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($originalPrice,2,'.',''),
                'price' => (float)number_format($radio2*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio2*$discount,
                'productId' => $result['pro_id'].'-1',
                'barcode' => $result['pro_barcode2'],
                'sku' => null,
                'unit' => $result['pro_unit2'],
                'image' => ($pro_imgU2!='')? $pro_imgU2:$pro_img
              );
              array_push($prices['variants'],$payload_2);
            }

            if ($result['pro_unit3']!='') {              
              $payload_2 = array(
                'register' => $result['pro_drugregister'],
                'view' => (int)$result['pro_view'],
                'rating' => (float)number_format($result['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($originalPrice,2,'.',''),
                'price' => (float)number_format($radio3*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio3*$discount,
                'productId' => $result['pro_id'].'-2',
                'barcode' => $result['pro_barcode3'],
                'sku' => null,
                'unit' => $result['pro_unit3'],
                'image' => ($pro_imgU3!='')? $pro_imgU3:$pro_img
              );
              array_push($prices['variants'],$payload_2);
            }

          array_push($json['products'],$prices);
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
