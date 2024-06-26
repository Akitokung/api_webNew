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

      $sql = "SELECT * FROM `member` WHERE `mem_username`='".$Username."' OR `mem_code`='".$Username."' LIMIT 1";
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

            $json = array(
              'status' => true,
              'time' => date('Y-m-d H:i:s'),
              'meassage' => 'success',
              'name' => $result['mem_name'],
              'img' => $img,
              'token' => $jwt,
            );
            // response JSON Encode to Client
            echo json_encode($json);
          }
          // ถูกระงับการเข้าใช้งาน ชั่วคราว
          else {
            http_response_code(401);
            $json = array(
              'status' => false,
              'time' => date('Y-m-d H:i:s'),
              'meassage' => 'unauthorized ขออภัยในความไม่สะดวก ชื่อบัญชีผู้ใช้ถูกระงับการใช้งานชั่วคราว',
              'name' => null,'img' => null,'token' => null,
            );
            // response JSON Encode to Client
            echo json_encode($json);
          }
        }
        // หาก Password ไม่ถูกต้อง
        else {
          http_response_code(401);
          $json = array(
            'status' => false,
            'time' => date('Y-m-d H:i:s'),
            'meassage' => 'unauthorized รหัสผ่านไม่ถูกต้อง กรุณาตรวจสอบ Password แล้วลองใหม่อีกครั้งในภายหลัง ขอบคุณครับ/ค่ะ',
            'name' => null,'img' => null,'token' => null,
          );
          // response JSON Encode to Client
          echo json_encode($json);
        }
      }
      // ไม่พบ or ไม่มีชื่อบัญชีผู้ใช้ในระบบ
      else {
        http_response_code(401);
        $json = array(
          'status' => false,
          'time' => date('Y-m-d H:i:s'),
          'meassage' => 'unauthorized ไม่พบชื่อบัญชีผู้ใช้ ในระบบกรุณาตรวจสอบ Username แล้วลองใหม่อีกครั้งในภายหลัง ขอบคุณครับ/ค่ะ',
          'name' => null,'img' => null,'token' => null,
        );
        // response JSON Encode to Client
        echo json_encode($json);
      }
    }
    // ส่งค่ามาไม่ครับ or function ไม่รู้จักตัวแปลที่ส่งเข้ามา
    else {
      http_response_code(401);
      $json = array(
        'status' => false,
        'time' => date('Y-m-d H:i:s'),
        'meassage' => 'unauthorized ไม่เข้าใจ หรือ รับค่า POST[.....] จาก Client ไม่ได้',
        'name' => null,'img' => null,'token' => null,
      );
      // response JSON Encode to Client
      echo json_encode($json);
    }
  }
?>
