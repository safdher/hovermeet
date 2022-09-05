<?php

require_once("base.php");
$input = json_decode(file_get_contents("php://input"));
$apiKey = "12341234";

if($input->apiKey != $apiKey){
    echo json_encode(
        array(
            "status" => "400",
            "message" => "Invalid API Key"
        ));
    exit;
}


$name = $input->functionName;
$param = $input->param;
switch($name){
    case "login_user":
        login_user($conn,$param);
        break;
    case "register_user":
        register_user($conn,$param);
        break;
    case "add_catagory":
        add_catagory($conn,$param);
        break;
    case "add_live_class":
        add_live_class($conn,$param);
        break;
    case "get_all_classes":
        get_all_classes($conn,$param);
        break;
    case "student_enrollment":
        student_enrollment($conn,$param);
        break;
    default:
        echo json_encode(
            array(
                "status" => "404",
                "message" => "Function Not Found"
            ));
        break;
}

function student_enrollment($conn,$param){

    $sql = "INSERT INTO enrollment(cId,uId)
    VALUES (".$param->cId.",".$param->uId.")";
    
    $isInserted = FALSE;
    try{
        $isInserted = $conn->query($sql);
        if ($isInserted === TRUE) {
            $last = $conn->insert_id;
            echo json_encode(
            array(
                "status" => "200",
                "message" => "Inserted"
            ));
        } else {
            echo json_encode(
                array(
                    "status" => "400",
                    "data" => "Error"
                ));
        }
    }catch(Exception $e){
        echo json_encode(
            array(
                "status" => "400",
                "data" => "Already exist"
            ));
    }
}

function get_all_classes($conn,$param){

    $sql = "SELECT * FROM liveclass";

    if(isset($param->search)){
        $sql .= " Where LOWER(title) LIKE LOWER('%".$param->search."%')";
    }

    $res = [];
    $res2 = [];
    $result = $conn->query($sql);
    if($result->num_rows>0){
        while(  $row = $result->fetch_assoc()){
            array_push($res,$row);
        }
    }

    if($param->uId != NULL){
        $sql = "SELECT l.* FROM liveclass as l join enrollment as e on l.liveId = e.cId where e.uId = $param->uId";
        
        if(isset($param->search_enroll)){
            $sql .= " and LOWER(l.title) LIKE LOWER('%".$param->search_enroll."%')";
        }

        $result = $conn->query($sql);
        if($result->num_rows>0){
            while(  $row = $result->fetch_assoc()){
                array_push($res2,$row);
            }
        }
    }

    $json_data=json_encode(array(
        "status" => "200",
        "data" => $res,
        "eroll_data" => $res2
    ));      
    echo $json_data;
}

function add_live_class($conn,$param){

    $liveLink = guidv4();
    $password = guidv4();

    $sql = "INSERT INTO liveclass(tId,cId,liveLink,password,title,descr)
    VALUES (".$param->tId.",".$param->cId.",'".$liveLink."','".$password."','".$param->title."','".$param->descr."')";
    
    $isInserted = FALSE;
    try{
        $isInserted = $conn->query($sql);
        if ($isInserted === TRUE) {
            $last = $conn->insert_id;
            foreach($param->days as $value){
                $value->liveId = $last;
                add_live_time($conn,$value);
            }

            echo json_encode(
            array(
                "status" => "200",
                "message" => "Inserted"
            ));
        } else {
            echo json_encode(
                array(
                    "status" => "400",
                    "data" => "Error"
                ));
        }
    }catch(Exception $e){
        echo json_encode(
            array(
                "status" => "400",
                "data" => "Already exist"
            ));
    }
}

function add_live_time($conn,$param){

    $sql = "INSERT INTO timetable(liveId,day,from_time,to_time)
    VALUES (".$param->liveId.",'".$param->day."','".$param->from_time."','".$param->to_time."')";
    
    $isInserted = FALSE;
    
    try{
        $isInserted = $conn->query($sql);
        if ($isInserted === TRUE) {
            return true;
        } else {
            return false;
        }
    }catch(Exception $e){
        return false;
    }
}

function login_user($conn,$param){
    $email=$param->email;
    $pass=$param->passwrd;
    $utype=$param->usrtyp;
    $res=[];
    $sql = "SELECT * FROM user WHERE emailId='".$email."'";
    $result = $conn->query($sql);
    if($result->num_rows>0){
        while(  $row = $result->fetch_assoc()){
            if($row["emailId"]===$email && $row["password"]===md5($pass)){
                $res['status']=200;
                $res['message']="Login Successfully";
                $res['userInfo']=$row;
            }
        }
    }
    else
    {
        $res['status']=400;
        $res['message']="Invalid Login Details";
    }
    $json_data=json_encode($res);      
    echo $json_data;
}

function register_user($conn,$param){
    $sql = "INSERT INTO user(name,emailId,phone,password,userType)
    VALUES ('".$param->name."','".$param->email."','".$param->phone."','".md5($param->password)."',".$param->usertype.")";
    $isInserted = FALSE;

    try{
        $isInserted = $conn->query($sql);
        if ($isInserted === TRUE) {
            echo json_encode(
            array(
                "status" => "200",
                "message" => "Inserted"
            ));
        } else {
            echo json_encode(
                array(
                    "status" => "400",
                    "data" => "Error"
                ));
        }
    }catch(Exception $e){
        echo json_encode(
            array(
                "status" => "400",
                "data" => "Already exist"
            ));
    }
}

function add_catagory($conn,$param){
    $sql = "INSERT INTO category(categoryName)
    VALUES ('".$param->categoryName."')";
    
    $isInserted = FALSE;
    try{
        $isInserted = $conn->query($sql);
        if ($isInserted === TRUE) {
            echo json_encode(
            array(
                "status" => "200",
                "message" => "Inserted"
            ));
        } else {
            echo json_encode(
                array(
                    "status" => "400",
                    "data" => "Error"
                ));
        }
    }catch(Exception $e){
        echo json_encode(
            array(
                "status" => "400",
                "data" => "Already exist"
            ));
    }
}

function guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));
}

?>