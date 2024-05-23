<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');
  
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // token สำหรับ decode jwt
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);
      if ($data) {
        //echo $data['mem_code'];

        $start = ($_GET['start']!='')? $_GET['start']:'0';    
        $end = ($_GET['end']!='')? $_GET['end']:'40';

        $sql = "
          SELECT 
            *
          FROM 
            `product` AS `a` 
            LEFT JOIN `product_promotion` AS `b` ON `a`.`pro_code`=`b`.`pmo_procode`
            LEFT JOIN `product_drugmode` AS `c` ON `a`.`pro_mode`=`c`.`pd_code`
          WHERE 
            `a`.`pro_show`!='1' AND 
            `a`.`pro_img`!='' AND 
            `a`.`pro_instock`!='0' AND 
            `a`.`pro_priceA`!='0' AND 
            `a`.`pro_priceC`!='0' AND 
            `b`.`pmo_start`='".date('Y-m-01')."' AND 
            `b`.`pmo_end`='".date('Y-m-t')."'
          ORDER BY 
            `b`.`pmo_sumsale` DESC,
            `a`.`pro_code` ASC
          LIMIT
            ".$start.",".$end."
        ";
        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}
        $json = array();          
        // Akitokung
        $site = 'https://www.wangpharma.com/';
        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {
          //  array_push($json,$result['pro_id']);      // ถ้าเอาทุกตัวแปล

          $pro_nameMain = ($result['pro_nameMain']!='')? $result['pro_nameMain']:$result['pro_nameTH'];
          $pro_nameMain = ($pro_nameMain!='')? $pro_nameMain:$result['pro_name'];
          $pro_instock = ($result['pro_instock']>=$result['pro_limitA'])? 'มี':'หมด';
          $pro_instock = (int)999;

          // $pro_instock = 'มี';

          $pro_img = str_replace('../',$site,$result['pro_img']);

          if ($result['pro_barcode1']!='') {$pro_barcode = $result['pro_barcode1'];}
          else if ($result['pro_barcode2']!='') {$pro_barcode = $result['pro_barcode2'];}
          else if ($result['pro_barcode3']!='') {$pro_barcode = $result['pro_barcode3'];}

          $Price_pro = ($result['pro_priceA']>=1)? number_format($result['pro_priceA'],2,'.',''):number_format($result['pro_priceC'],2,'.','');
          $Price_dis = ($result['pro_priceTag']>=1)? number_format($result['pro_priceTag'],2,'.',''):number_format($result['pro_priceC'],2,'.','');

          $Price_save = number_format($Price_dis-$Price_pro,2,'.','');
          $Percent_save = number_format(($Price_save/$Price_dis)*100,2,'.','');
          $pmo_sumsale = number_format($result['pmo_sumsale'],2,'.','');

          $isPromotion = true;
          $flashsale_end = ($result['pro_gs3']=='1')? date('Y-m-t 23:59:59'):null;
          $pro_limitA = ($result['pro_limitA']!=0)? $result['pro_limitA']:'1.00';
          $pro_limitU = ($result['pro_limitA']!=0)? $result['pro_unit1']:null;

          $Price_Tag = number_format($result['pro_priceTag'],2,'.','');
          
          $payload = array(
            'pro_code' => $result['pro_code'],              // รหัสสินค้า
            'pro_nameMain' => $pro_nameMain,                // ชื่อภาษาไทย
            'pro_nameEng' => $result['pro_nameEng'],        // ชื่อภาษาอังกฤษ
            'pro_barcode' => $pro_barcode,                  // บาร์โค๊ด 1 , 2 , 3 
            'pro_unit1' => $result['pro_unit1'],            // หน่วยที่ 1
            'pro_unit2' => $result['pro_unit2'],            // หน่วยที่ 2
            'pro_unit3' => $result['pro_unit3'],            // หน่วยที่ 3
            'Price_dis' => $Price_dis,                      // ราคาก่อนโปรโมชั่น
            'Price_pro' => $Price_pro,                      // ราคาโปรโมชั่น
            'Price_save' => $Price_save,                    // ราคาที่ประหยัดได้
            'Percent_save' => $Percent_save,                // เปอร์เซ็น ( % ) ที่ประหยัดได้
            'pro_instock' => $pro_instock,                  // สถานะคงคลัง มี หรือ หมด สำหรับเงื่อนไขการเพิ่มใส่รถเข็น
            'pro_details' => $result['pro_details'],

            'pro_limitA' => $pro_limitA,          // จำนวนขั้นต่ำในการสั่งในราคาโปรมั่งชั้น ( สั่งต่ำกว่าได้ แต่จะไม่ได้ราคาโปรโมชั่น )
            'pro_limitU' => $pro_limitU,          // จำนวนขั้นต่ำในการสั่งในราคาโปรมั่งชั้น ( สั่งต่ำกว่าได้ แต่จะไม่ได้ราคาโปรโมชั่น )
            'pro_mode' => $result['pd_name'],
            'variants' => array(),
            'Price_Tag' => $Price_Tag, 
            'pro_img' => $pro_img,                          // รูปหลักสินค้า
            'pmo_sumsale' => $pmo_sumsale,                  // ขายแล้วกว่า ++
            'isPromotion' => $isPromotion,
            'flashsale_end' => $flashsale_end
          );

          $radio1 = $result['pro_ratio1']/$result['pro_ratio1'];
          $radio2 = $result['pro_ratio1']/$result['pro_ratio2'];
          $radio3 = $result['pro_ratio1']/$result['pro_ratio3'];

            if ($result['pro_unit1']!='') {
              $pro_before = $radio1*$result['pro_priceC'];
              $pro_after = $radio1*$result['pro_priceA'];

              $payload_2 = array(
                'pro_unit' => $result['pro_unit1'],
                'Price_Tag' => $Price_Tag,
                'pro_before' => number_format($pro_before,2,'.',','),
                'pro_after' => number_format($pro_after,2,'.',','),
              );
              array_push($payload['variants'],$payload_2);
            }

            if ($result['pro_unit2']!='') {
              $pro_before = $radio2*$result['pro_priceC'];
              $pro_after = $radio2*$result['pro_priceA'];

              $payload_2 = array(
                'pro_unit' => $result['pro_unit2'],
                'Price_Tag' => $Price_Tag,
                'pro_before' => number_format($pro_before,2,'.',','),
                'pro_after' => number_format($pro_after,2,'.',','),
              );
              array_push($payload['variants'],$payload_2);
            }

            if ($result['pro_unit3']!='') {
              $pro_before = $radio3*$result['pro_priceC'];
              $pro_after = $radio3*$result['pro_priceA'];
              
              $payload_2 = array(
                'pro_unit' => $result['pro_unit3'],
                'Price_Tag' => $Price_Tag,
                'pro_before' => number_format($pro_before,2,'.',','),
                'pro_after' => number_format($pro_after,2,'.',','),
              );
              array_push($payload['variants'],$payload_2);
            }


          // $payload_2 = array(
          //   'pro_unit1' => $result['pro_unit1'],
          //   'pro_unit2' => $result['pro_unit2'],
          //   'pro_unit3' => $result['pro_unit3'],
          // );
          // array_push($payload['variants'],$payload_2);

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