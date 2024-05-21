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
        $spc_memcode = $data['mem_code'];

        // $branch = "
        //   SELECT 
        //     * 
        //   FROM 
        //     `member_branch` AS `a` 
        //     LEFT JOIN `member` AS `b` ON `a`.`mb_memcode`=`b`.`mem_code` 
        //   WHERE 
        //     `a`.`mb_memcode`='".$spc_memcode."' OR 
        //     `a`.`mb_maincode`='".$spc_memcode."'
        //   GROUP BY
        //     `a`.`mb_memcode`
        //   ";
        // @$qbranch = mysqli_query($Con_wang,$branch); @$nbranch = mysqli_num_rows($qbranch);
        // $memg = array();
        // while (@$rbranch = mysqli_fetch_array($qbranch)) {
        //   $memg[] = $rbranch['mb_memcode'];
        // }
        // $imem = "('".implode("','",$arr)."')";
        // $sql = "
        //   SELECT 
        //     * 
        //   FROM 
        //     `shopping_cart` AS `a`
        //     LEFT JOIN `product` AS `b` ON `a`.`spc_procode`=`b`.`pro_code`
        //   WHERE 
        //     `a`.`spc_memcode` IN '".$imem."'
        //   ORDER BY 
        //     `a`.`spc_procode` ASC,
        //     `a`.`spc_unit` ASC
        // ";

        $sql = "
          SELECT 
            * 
          FROM 
            `shopping_cart` AS `a`
            LEFT JOIN `product` AS `b` ON `a`.`spc_procode`=`b`.`pro_code`
            LEFT JOIN `product_drugmode` AS `c` ON `b`.`pro_mode`=`c`.`pd_code`
          WHERE 
            `a`.`spc_memcode`='".$spc_memcode."'
          ORDER BY 
            `a`.`spc_check` DESC,
            `a`.`spc_procode` ASC,
            `a`.`spc_unit` ASC
        ";
        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}
        $json = array();
        // Akitokung
        $site = 'https://www.wangpharma.com/';
        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {
          //  array_push($json,$result['pro_id']);      // ถ้าเอาทุกตัวแปล
          $fb = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT `shf_procode` FROM `shopping_favorites` WHERE `shf_memcode`='".$data['mem_code']."' AND `shf_procode`='".$result['pro_code']."'
          "));

          $pro_nameMain = ($result['pro_nameMain']!='')? $result['pro_nameMain']:$result['pro_nameTH'];
          $pro_nameMain = ($pro_nameMain!='')? $pro_nameMain:$result['pro_name'];
          $pro_instock = ($result['pro_instock']>=$result['pro_limitA'])? 'มี':'หมด';
          $pro_instock = (int)999;

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
          $ship = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
              `ltr_date`
            FROM 
              `logistic_transportation` 
            WHERE 
              `ltr_mscode`='".$mem['mem_route']."' AND 
              `ltr_date`>'".date('Y-m-d')."'
            ORDER BY 
              `ltr_date`
            ASC 
              LIMIT 
            1
          "));
          $ship['ltr_date'] = (strtotime($ship['ltr_date']>=1))? $ship['ltr_date']:date('Y-m-d');
          list($ys,$ms,$ds) = explode('-', $ship['ltr_date']);
          $ms = date('n',strtotime($ship['ltr_date']));
          $shipping = 'ถึงคุณในวันที่ '.$ds.' / '.$month_full_th[$ms].' / '.($ys+543);
          $shipping = (strtotime($ship['ltr_date'])>=1)? $shipping:null;

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
            'shipping' => $shipping,
            'pro_mode' => $result['pd_name'],
            'Price_Tag' => $Price_Tag,
            'variants' => array(),
            'promotion' => $promotion,
            'flashsale_end' => $flashsale_end,
            'pro_favorites' => $pro_favorites,
            'pro_limitA' => $pro_limitA,
            'pro_limitU' => $pro_limitU,
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