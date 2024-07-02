<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');
  
  $json = file_get_contents('php://input');     //  อ่านไฟล์ JSON ที่ทางแอพจะส่งเข้ามา
  $input = json_decode($json, true);              //  แปลงข้อมูลที่อ่านไฟล์ได้จาก JSON เข้า array ของ php

  $user = $input['Username'];   $pass = $input['Password'];

  // if (isset($_POST['POST'])){ }
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ตรวจสอบการส่งข้อมูล Username & Password จาก client
    if (isset($user) AND isset($pass)) {
      // รับข้อมูล Username & Password จาก client
      $Username = mysqli_real_escape_string($Con_wang,trim($user));
      $Password = mysqli_real_escape_string($Con_wang,trim($pass));

      $sql = "
        SELECT 
          * 
        FROM 
          `member` AS `a` 
          LEFT JOIN `member_phone` AS `b` ON `a`.`mem_code`=`b`.`mn_memcode`
        WHERE 
          `a`.`mem_username`='".$Username."'
        LIMIT 
          1
      ";

      $query = mysqli_query($Con_wang,$sql);    $num_rows = mysqli_num_rows($query);
      // พบ ข้อมูลที่ตรงกัน ใน Databases
      if ($num_rows != 0) {
        // ดึงข้อมูลที่พบออกมา
        $result = mysqli_fetch_array($query);
        // ตรวจสอบ Password จาก Client === Databases is true
        if ($Password == $result['mem_password']) {
          if ($result['mem_statuslogin']==0) {
            // Update status login user and lasttime login 
            mysqli_query($Con_wang,"
              UPDATE 
                `member` 
              SET 
                `mem_login`='1',
                `mem_logindate`=NOW(),
                `mem_loginupdate`=NOW()
              WHERE 
                `mem_id`='".$result['mem_id']."'
            ");
            //mysqli_close($Con_wang);  // close session connection to databases
            http_response_code(200);  //  response http status = 200 this OK
            $jwt = encode_jwt($result['mem_code']);  // ส่ง token ไปให้ client
            // สร้าง object สำหรับ response JSON 
            $site = 'https://www.wangpharma.com/';
            $img = ($result['mem_img1']!='')? $site.'Akitokung/'.$result['mem_img1']:null;

            $mem_address = ($result['mem_address']!='')? 'เลขที่ '.trim($result['mem_address']).' ':null;
            $mem_village = ($result['mem_village']!='')? 'หมู่ที่ '.trim($result['mem_village']).' ':null;
            $mem_alley = ($result['mem_alley']!='')? 'ซอย'.trim($result['mem_alley']).' ':null;
            $mem_road = ($result['mem_road']!='')? 'ถนน'.trim($result['mem_road']).' ':null;
            $mem_tumbon = ($result['mem_tumbon']!='')? 'ตำบล'.trim($result['mem_tumbon']).' ':null;
            $mem_amphur = ($result['mem_amphur']!='')? 'อำเภอ'.trim($result['mem_amphur']).' ':null;
            $mem_province = ($result['mem_province']!='')? 'จังหวัด'.trim($result['mem_province']).' ':null;
            $mem_post = ($result['mem_post']!='')? 'รหัสไปรษณีย์ '.trim($result['mem_post']):null;
            $country = 'ประเทศไทย';

            $address = $mem_address.$mem_village.$mem_alley.$mem_road.$mem_tumbon.$mem_amphur.$mem_province.$mem_post.$country;

            $json = array(
              'token' => $jwt,
              '_id' => $result['mem_code'],
              'name' => $result['mem_name'],
              'contact' => $result['mn_phoneshop'],
              'email' => $result['mn_emailshop'],
              'address' => $address,
              'country' => $country,
              'city' => ($result['mem_province']!='')? trim($result['mem_province']):null,
              'zipCode' => ($result['mem_post']!='')? trim($result['mem_post']):null, 

              'phone' => $result['mn_phoneshop'],

              'img' => $img,
              'status' => true,
              'meassage' => 'success',
              'time' => date('Y-m-d H:i:s'),
            );
            // response JSON Encode to Client
            echo json_encode($json);
          }
          // ถูกระงับการเข้าใช้งาน ชั่วคราว
          else {
            http_response_code(401);
            $json = array(
              'token' => null,
              '_id' => null,
              'name' => null,
              'contact' => null,
              'email' => null,
              'address' => null,
              'country' => null,
              'city' => null,
              'zipCode' => null,
              'phone' => null,

              'img' => null,
              'status' => false,
              'meassage' => 'unauthorized ขออภัยในความไม่สะดวก ชื่อบัญชีผู้ใช้ถูกระงับการใช้งานชั่วคราว',
              'time' => date('Y-m-d H:i:s'),
            );
            // response JSON Encode to Client
            echo json_encode($json);
          }
        }
        // หาก Password ไม่ถูกต้อง
        else {
          http_response_code(401);
          $json = array(
              'token' => null,
              '_id' => null,
              'name' => null,
              'contact' => null,
              'email' => null,
              'address' => null,
              'country' => null,
              'city' => null,
              'zipCode' => null,
              'phone' => null,

              'img' => null,
              'status' => false,
              'meassage' => 'unauthorized รหัสผ่านไม่ถูกต้อง กรุณาตรวจสอบ Password แล้วลองใหม่อีกครั้งในภายหลัง ขอบคุณครับ/ค่ะ',
              'time' => date('Y-m-d H:i:s'),
          );
          // response JSON Encode to Client
          echo json_encode($json);
        }
      }
      // ไม่พบ or ไม่มีชื่อบัญชีผู้ใช้ในระบบ
      else {
        http_response_code(401);
        $json = array(
              'token' => null,
              '_id' => null,
              'name' => null,
              'contact' => null,
              'email' => null,
              'address' => null,
              'country' => null,
              'city' => null,
              'zipCode' => null,
              'phone' => null,

              'img' => null,
              'status' => false,
              'meassage' => 'unauthorized ไม่พบชื่อบัญชีผู้ใช้ ในระบบกรุณาตรวจสอบ Username แล้วลองใหม่อีกครั้งในภายหลัง ขอบคุณครับ/ค่ะ',
              'time' => date('Y-m-d H:i:s'),
        );
        // response JSON Encode to Client
        echo json_encode($json);
      }
    }
    // ส่งค่ามาไม่ครับ or function ไม่รู้จักตัวแปลที่ส่งเข้ามา
    else {
      http_response_code(401);
      $json = array(
              'token' => null,
              '_id' => null,
              'name' => null,
              'contact' => null,
              'email' => null,
              'address' => null,
              'country' => null,
              'city' => null,
              'zipCode' => null,
              'phone' => null,

              'img' => null,
              'status' => false,
              'meassage' => 'unauthorized ไม่เข้าใจ หรือ รับค่า POST[.....] จาก Client ไม่ได้',
              'time' => date('Y-m-d H:i:s'),
      );
      // response JSON Encode to Client
      echo json_encode($json);
    }
  }
?>
