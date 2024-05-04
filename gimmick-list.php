<?php
  require_once('../../Akitokung/00-connection.class.sqli.php');
  
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    header("Access-Control-Allow-Origin: * ");
    //header("Content-Type: text/html; charset=UTF-8");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
            `product` 
          WHERE 
            `pro_gs5`='1' AND 
            `pro_point`!='0' AND 
            `pro_instock`>'10'
          ORDER BY 
            `pro_point` DESC,
            `pro_code` ASC
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
          // $pro_instock = 'มี';
          
          $payload = array(
            'pro_code' => $result['pro_code'],              // รหัสสินค้า
            'pro_img' => $pro_img,                          // รูปหลักสินค้า
            'pro_nameMain' => $pro_nameMain,                // ชื่อภาษาไทย
            'pro_nameEng' => $result['pro_nameEng'],        // ชื่อภาษาอังกฤษ
            'pro_point' => $result['pro_point'],            // แต้มที่ต้องใช้และ / 1 หน่วยที่ 1
            'pro_unit' => $result['pro_unit1'],            // หน่วยที่ 1
          );
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