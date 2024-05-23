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
        //$spc_memcode = $data['mem_code'];
        $sql = "
          SELECT 
            * 
          FROM 
            `product` AS `b` 
            LEFT JOIN `product_drugmode` AS `c` ON `b`.`pro_mode`=`c`.`pd_code`
          WHERE 
            `b`.`pro_gs5`='1' AND 
            `b`.`pro_point`!='0' AND 
            `b`.`pro_instock`>'10'
          ORDER BY 
            `b`.`pro_point` DESC,
            `b`.`pro_code` ASC
        ";
        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}
        $json = array();
          
        $site = 'https://www.wangpharma.com/';
        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {

          $fb = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT `shf_procode` FROM `shopping_favorites` WHERE `shf_memcode`='".$data['mem_code']."' AND `shf_procode`='".$result['pro_code']."'
          "));

          $pro_nameMain = ($result['pro_nameMain']!='')? $result['pro_nameMain']:$result['pro_nameTH'];
          $pro_nameMain = ($pro_nameMain!='')? $pro_nameMain:$result['pro_name'];
          $pro_img = str_replace('../',$site,$result['pro_img']);

          $pro_instock = ($result['pro_instock']>=$result['pro_limitA'])? 'มี':'หมด';
          $pro_instock = (int)999;
          // $pro_instock = 'มี';
          $Price_Tag = number_format($result['pro_priceTag'],2,'.','');

          $payload = array(
            'pro_code' => $result['pro_code'],              // รหัสสินค้า
            'pro_mode' => $result['pd_name'],
            'Price_Tag' => $Price_Tag,
            'variants' => array(),
            'pro_img' => $pro_img,                          // รูปหลักสินค้า
            'pro_nameMain' => $pro_nameMain,                // ชื่อภาษาไทย
            'pro_nameEng' => $result['pro_nameEng'],        // ชื่อภาษาอังกฤษ
            'pro_point' => $result['pro_point'],            // แต้มที่ต้องใช้และ / 1 หน่วยที่ 1
            'pro_unit' => $result['pro_unit1'],            // หน่วยที่ 1
            'pro_details' => $result['pro_details'],
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