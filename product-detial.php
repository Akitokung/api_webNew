<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: * ");
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
        //echo $data['mem_code'];
        $sql = "
          SELECT 
            * 
          FROM 
            `product` AS `a` 
            LEFT JOIN `product_pharma` AS `b` ON `a`.`pro_code`=`b`.`pp_procode`
            LEFT JOIN `supplier` AS `c` ON `a`.`pro_supplier`=`c`.`sp_code`
          WHERE 
            `a`.`pro_show`!='1' AND 
            `a`.`pro_code`='".$_GET['pcode']."'
        ";
        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}
        $json = array();
        // Akitokung
        $site = 'https://www.wangpharma.com/';
        $result = mysqli_fetch_array($query,MYSQLI_ASSOC);
          //  array_push($json,$result['pro_id']);      // ถ้าเอาทุกตัวแปล

          $mem = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
              *
            FROM 
              `member` AS `a` 
              LEFT JOIN `member_payment` AS `b` ON `a`.`mem_code`=`b`.`mp_memcode`
            WHERE 
              `a`.`mem_code`='".$data['mem_code']."'
          "));

          $fb = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT `shf_procode` FROM `shopping_favorites` WHERE `shf_memcode`='".$data['mem_code']."' AND `shf_procode`='".$result['pro_code']."'
          "));

          $pro_nameMain = ($result['pro_nameMain']!='')? $result['pro_nameMain']:$result['pro_nameTH'];
          $pro_nameMain = ($pro_nameMain!='')? $pro_nameMain:$result['pro_name'];
          $pro_instock = ($result['pro_instock']!=0)? 'มี':'หมด';

          // $pro_instock = 'มี';

          $pro_img = str_replace('../',$site,$result['pro_img']);
          $pro_imgU1 = str_replace('../',$site,$result['pro_imgU1']);
          $pro_imgU2 = str_replace('../',$site,$result['pro_imgU2']);
          $pro_imgU3 = str_replace('../',$site,$result['pro_imgU3']);

          if ($result['pro_barcode1']!='') {$pro_barcode = $result['pro_barcode1'];}
          else if ($result['pro_barcode2']!='') {$pro_barcode = $result['pro_barcode2'];}
          else if ($result['pro_barcode3']!='') {$pro_barcode = $result['pro_barcode3'];}
          $Price_Tag = number_format($result['pro_priceTag'],2,'.','');

          $bad=array("\n","\r",' ');
          $pp_proper = str_replace($bad,'',strip_tags($result['pp_properties']));
          $pp_htu = str_replace($bad,'',strip_tags($result['pp_how_to_use']));
          $pro_details = str_replace($bad,'',strip_tags($result['pro_details']));

          $flashsale_end = ($result['pro_gs3']=='1')? date('Y-m-t 23:59:59'):null;
          $promotion = ($result['pro_gs3']=='1')? true:false;
          $pro_favorites = ($fb['shf_procode']!='')? true:false;

          $rating = explode('.', $result['pro_rating']);
          if ($rating[1]!='0') {
            if (($rating[1]>0) && ($rating[1]<=5)) {
              $result['pro_rating']=$rating[0].'.5';
            }
            if (($rating[1]>0) && ($rating[1]>5)) {
              $result['pro_rating']=($rating[0]+1).'.0';
            }
          }
          $pro_limitA = ($result['pro_limitA']!=0)? $result['pro_limitA']:'1.00';
          $pro_limitU = ($result['pro_limitA']!=0)? $result['pro_unit1']:$result['pro_unit1'];

          $radio1 = $result['pro_ratio1']/$result['pro_ratio1'];
          $radio2 = $result['pro_ratio1']/$result['pro_ratio2'];
          $radio3 = $result['pro_ratio1']/$result['pro_ratio3'];
          if ($mem['mp_price']=='A') {$price = $result['pro_priceA'];}
          else if ($mem['mp_price']=='B') {$price = $result['pro_priceB'];}
          else if ($mem['mp_price']=='C') {$price = $result['pro_priceC'];}

          $pro_priceU1 = ($result['pro_unit1']!='')? number_format($price*$radio1,2,'.',','):null;
          $pro_priceU2 = ($result['pro_unit2']!='')? number_format($price*$radio2,2,'.',','):null;
          $pro_priceU3 = ($result['pro_unit3']!='')? number_format($price*$radio3,2,'.',','):null;

          $Price_after = ($result['pro_priceA']>=1)? number_format($price,2,'.',''):number_format($result['pro_priceC'],2,'.','');         
          $Price_before = ($result['pro_priceTag']>=1)? number_format($result['pro_priceTag'],2,'.',''):number_format($result['pro_priceC'],2,'.','');


          $Price_pro = number_format($result['pro_priceA'],2,'.','');

          $Price_discount = number_format($Price_before-$Price_after,2,'.','');
          $Percent_save = number_format(($Price_discount/$Price_before)*100,2,'.','');

          $pro_url = 'https://www.wangpharma.com/shopping/shopping-detial.php?pc='.$result['pro_code'];

          $ship = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
            `a`.`mem_code`,
            `a`.`mem_route`,
            `b`.`ltr_date`
            FROM 
              member AS `a`
              LEFT JOIN `logistic_transportation` AS `b` ON `a`.`mem_route`=`b`.`ltr_mscode` 
            WHERE 
              `b`.`a`.`mem_code`='".$data['mem_code']."' AND 
              `b`.`ltr_date`>'".date('Y-m-d')."'
            ORDER BY 
              `b`.`ltr_date`
            ASC 
              LIMIT 
            1
          "));
          $ship['ltr_date'] = (strtotime($ship['ltr_date']>=1))? $ship['ltr_date']:date('Y-m-d');
          list($ys,$ms,$ds) = explode('-', $ship['ltr_date']);
          $ms = date('n',strtotime($ship['ltr_date']));
          $lt = mysqli_fetch_array(mysqli_query($Con_pharSYS,"
            SELECT `sDateFini` FROM `logistic` WHERE `sIdCus`='".$data['mem_code']."' ORDER BY `sDateFini` DESC LIMIT 1
          "));    list($ltdate,$lttime) = explode(' ',$lt['sDateFini']);
          $shipping = 'ถึงคุณวันที่ '.$ds.' / '.$month_abt_th[$ms].' / '.SUBSTR(($ys+543),-2,2).' ~ '.SUBSTR($lttime,0,5).' น.';
          $shipping = (strtotime($ship['ltr_date'])>=1)? $shipping:null;

          $payload = array(
            'pro_code' => $result['pro_code'],              // รหัสสินค้า
            'pro_nameMain' => $pro_nameMain,                // ชื่อภาษาไทย
            'pro_nameEng' => $result['pro_nameEng'],        // ชื่อภาษาอังกฤษ
            'pro_barcode' => $pro_barcode,                  // บาร์โค๊ด 1 , 2 , 3 
            'pro_unit1' => $result['pro_unit1'],            // หน่วยที่ 1
            'pro_priceU1' => $pro_priceU1,                  // ราคา / หน่วยที่ 1

            'pro_unit2' => $result['pro_unit2'],            // หน่วยที่ 2
            'pro_priceU2' => $pro_priceU2,                  // ราคา / หน่วยที่ 2

            'pro_unit3' => $result['pro_unit3'],            // หน่วยที่ 3
            'pro_priceU3' => ($result['pro_unit3']!='')? $pro_priceU3:'',                  // ราคา / หน่วยที่ 3

            'pro_view' => number_format($result['pro_view'],0,'.',','),              // ยอดเข้าชม
            'Price_before' => $Price_before,                // ราคาก่อนลด
            'Price_after' => $Price_after,                  // ราคาหลังลดแล้ว
            
            'Price_discount' => $Price_discount,            // มูลค่าที่ลดไป
            'Percent_save' => $Percent_save,                // เปอร์เซ็น ( % ) ที่ลดไป
            'promotion' => $promotion,                      // โปรโมชั่น = true

            'Price_pro' => number_format($Price_pro,2,'.',','),
            
            'pro_limitA' => $pro_limitA,          // จำนวนขั้นต่ำในการสั่งในราคาโปรโมชั้น ( สั่งต่ำกว่าได้ แต่จะไม่ได้ราคาโปรโมชั่น )
            'pro_limitU' => $pro_limitU,          // 
            'flastsale_end' => $flashsale_end,

            'Price_Tag' => $Price_Tag,                      // ราคาป้าย = ราคาที่พิมพ์ติดอยู่บนตัวสินค้า
            'pro_instock' => $pro_instock,                  // สถานะคงคลัง มี หรือ หมด สำหรับเงื่อนไขการเพิ่มใส่รถเข็น
            /*
            'pro_img' => $pro_img,                          // รูปหลักสินค้า
            'pro_imgU1' => $pro_imgU1,                      // รูปสินค้าหน่วยที่ 1
            'pro_imgU2' => $pro_imgU2,                      // รูปสินค้าหน่วยที่ 2
            'pro_imgU3' => $pro_imgU3,                      // รูปสินค้าหน่วยที่ 3
            */
            'pro_img' => array(),
            'pro_details' => $pro_details,
            'pro_properties' => $pp_proper,
            'pro_how_to_use' => $pp_htu,
            /*
            'pro_mode' => $result['pro_mode'],        
            'pro_easymode' => $result['pro_easy_mode'],
            */
            'pro_favorites' => $pro_favorites,
            'pro_rating' => number_format($result['pro_rating'],1,'.',','),
            'pro_url' => $pro_url,
            'shipping' => $shipping,
            
            'pro_spname' => $result['sp_name'], 
            'pro_stin' => ($result['pro_instock']!=0)? true:false, 
            'pro_instocknum' => ($result['pro_instock']!=0)? number_format($result['pro_instock'],2,'.',''):'',
            'pro_show' => ($result['pro_show']!=0)? false:true, 
            'pro_priceA' => ($result['pro_priceA']!=0)? number_format($result['pro_priceA'],2,'.',''):'',
            'pro_priceB' => ($result['pro_priceB']!=0)? number_format($result['pro_priceB'],2,'.',''):'',
            'pro_priceC' => ($result['pro_priceC']!=0)? number_format($result['pro_priceC'],2,'.',''):'',
            'pro_rec' => ($result['pro_gs6']==1)? true:false,
            'pro_base' => ($result['pro_gs7']==1)? true:false,
            'pro_new' => ($result['pro_gs8']==1)? true:false,
          );

          $itt = 0;
          if ($result['pro_img']!='') {
            $itt++;
            $img = 'img'.$itt;
            $payload['pro_img'][$img] = $pro_img;
          }
          if ($result['pro_imgU1']) {
            $itt++;
            $img = 'img'.$itt;
            $payload['pro_img'][$img] = $pro_imgU1;
          }
          if ($result['pro_imgU2']) {
            $itt++;
            $img = 'img'.$itt;
            $payload['pro_img'][$img] = $pro_imgU2;
          }
          if ($result['pro_imgU3']) {
            $itt++;
            $img = 'img'.$itt;
            $payload['pro_img'][$img] = $pro_imgU3;
          }


          $img = "SELECT `product_img_filePath` FROM `product_img` WHERE `product_img_productCode`='".$result['pro_code']."'";
          $qimg = mysqli_query($Con_pharSYS,$img);    $ig = $itt+1;
          while ($rimg = mysqli_fetch_array($qimg)) {
            $img = 'img'.$ig;
            $value = $site.'cms/product/'.$rimg['product_img_filePath'];
            $payload['pro_img'][$img] = $value;
            $ig++;
          }
        array_push($json,$payload);
          
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