<?php
  require_once('../../Akitokung/00-connection.class.sqli.php');
  
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    header("Access-Control-Allow-Origin: * ");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // token สำหรับ decode jwt
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);
      if ($data) {
        $spc_memcode = $data['mem_code'];

        $sql = "
          SELECT 
            * 
          FROM 
            `shopping_cart` AS `a`
            LEFT JOIN `product` AS `b` ON `a`.`spc_procode`=`b`.`pro_code`
          WHERE 
            `a`.`spc_memcode`='".$spc_memcode."'
          ORDER BY 
            `a`.`spc_check` DESC,
            `a`.`spc_procode` ASC,
            `a`.`spc_unit` ASC
        ";

        // echo $sql.'<br/>';

        $query = mysqli_query($Con_wang,$sql);      $num_rows = mysqli_num_rows($query);
        if (!$query) {http_response_code(404);}
        $json = array();
        // Akitokung
        $site = 'https://www.wangpharma.com/';

        if ($num_rows<=0) {
          http_response_code(401);
          echo 'ไม่ม่รายการสินค้า ในรถเข็น';
        }
        else {
          while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {

            $pro_nameMain = ($result['pro_nameMain']!='')? $result['pro_nameMain']:$result['pro_nameTH'];
            $pro_nameMain = ($pro_nameMain!='')? $pro_nameMain:$result['pro_name'];
            $pro_instock = ($result['pro_instock']>=$result['pro_limitA'])? 'มี':'หมด';

            // $pro_instock = 'มี';

            $pro_img = str_replace('../',$site,$result['pro_img']);
            $pro_imgU1 = str_replace('../',$site,$result['pro_imgU1']);
            $pro_imgU2 = str_replace('../',$site,$result['pro_imgU2']);
            $pro_imgU3 = str_replace('../',$site,$result['pro_imgU3']);

            if ($result['pro_barcode1']!='') {$pro_barcode = $result['pro_barcode1'];}
            else if ($result['pro_barcode2']!='') {$pro_barcode = $result['pro_barcode2'];}
            else if ($result['pro_barcode3']!='') {$pro_barcode = $result['pro_barcode3'];}
            $Price_Tag = number_format($result['pro_priceTag'],2,'.','');

            if ($result['spc_unit']=='1') {$spc_unit = $result['pro_unit1'];}
            else if ($result['spc_unit']=='2') {$spc_unit = $result['pro_unit2'];}
            else if ($result['spc_unit']=='3') {$spc_unit = $result['pro_unit3'];}

            $spc_check = ($result['spc_check']==1)? true:false;

            $mem = mysqli_fetch_array(mysqli_query($Con_wang,"
              SELECT `mem_route` FROM `member` WHERE `mem_code`='".$spc_memcode."'
            "));

            $promotion = ($result['pro_gs3']=='1')? true:false;
            $flashsale_end = ($result['pro_gs3']=='1')? date('Y-m-t 23:59:59'):null;
            $pro_favorites = ($fb['shf_procode']!='')? true:false;
            $pro_limitA = ($result['pro_limitA']!=0)? $result['pro_limitA']:'1.00';
            $pro_limitU = ($result['pro_limitA']!=0)? $result['pro_unit1']:null;

            $payload = array(
              'spc_id' => $result['spc_id'],              // รหัสสินค้า
              'spc_check' => $spc_check,                  // รายการที่เลือก = 1

              'pro_code' => $result['pro_code'],              // รหัสสินค้า
              'pro_nameMain' => $pro_nameMain,                // ชื่อภาษาไทย
              'pro_nameEng' => $result['pro_nameEng'],        // ชื่อภาษาอังกฤษ
              'pro_barcode' => $pro_barcode,                  // บาร์โค๊ด 1 , 2 , 3 

              'spc_amount' => $result['spc_amount'],          // จำนวนสั่ง
              'spc_unit' => $spc_unit,                        // หน่วยที่สั่ง
              'spc_ppu' => $result['spc_ppu'],                // ราคา / หน่วย
              'spc_discount' => $result['spc_discount'],      // % ส่วนลด
              'spc_total' => $result['spc_total'],            // มูลค่ารวมทั้งหมด

              'pro_img' => $pro_img,                          // รูปหลักสินค้า
              'pro_imgU1' => $pro_imgU1,                      // รูปสินค้าหน่วยที่ 1
              'pro_imgU2' => $pro_imgU2,                      // รูปสินค้าหน่วยที่ 2
              'pro_imgU3' => $pro_imgU3,                      // รูปสินค้าหน่วยที่ 3

              'promotion' => $promotion,
              'flashsale_end' => $flashsale_end,
              'pro_limitA' => $pro_limitA,
              'pro_limitU' => $pro_limitU,
            );
            array_push($json,$payload);
          }
          mysqli_close($Con_wang);
          echo json_encode($json);
        }
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