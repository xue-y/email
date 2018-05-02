<?php
error_reporting(0);
date_default_timezone_set("PRC");
set_time_limit(0); //设置脚本执行时间--0 不限制时间
ini_set('max_execution_time', '600');
ini_set('max_input_time ', '600');
ini_set('memory_limit', '200M'); // 好像设置不成功，不起作用---如果不起作用请在php.ini 文件中设置
/**　相关设置
 * php.ini  file_uploads  =  on
 * 如果是apache 2 需要修改
    /etc/httpd/conf.d/php.conf
    中的LimitRequestBody 524288将524288（＝512×1024）改大，比如5M（＝5×1024×1024）
    max_execution_time  =  600 ;每个PHP页面运行的最大时间值(秒)，默认30秒
 * 计算的只是PHP脚本本身执行的时间,执行之外的时间都不会计算在内.哪些属于执行之外的时间呢?包含sleep、数据交互、socket交互等等
    max_input_time = 600 ;每个PHP页面接收数据所需的最大时间，默认60秒
    memory_limit  =  128m  ;每个PHP页面所吃掉的最大内存，默认128M
 * */

	/**
	 * 已兼容php7
	 * 注：本邮件类都是经过我测试成功了的，如果大家发送邮件的时候遇到了失败的问题，请从以下几点排查：
	 * 1. 用户名和密码是否正确；
	 * 2. 检查邮箱设置是否启用了smtp服务；
	 * 3. 是否是php环境的问题导致；
	 * 4. 将26行的$smtp->debug = false改为true，可以显示错误信息，然后可以复制报错信息到网上搜一下错误的原因；
	 * 5. 如果还是不能解决，可以访问：http://www.daixiaorui.com/read/16.html#viewpl 
	 *    下面的评论中，可能有你要找的答案。
	 * Last update time:2017/06
	 * UPDATE:
	 * 1、替换了高版本不支持的写法，如ereg、ereg_replace.
	 * 2、将 var 改为 public/private等.
	 * 3、使其兼容php7.
	 * 
	 */
    //var_dump($_FILES);
    /*$email = "lastchiliarch@163.com";
    $a=explode("@",$email);
    var_dump(checkdnsrr(array_pop($a),"MX"));
    $email = "123@qq.com";
    $b=explode("@",$email);
    var_dump(checkdnsrr(array_pop($b),"MX"));
    exit;*/

/*	if(!filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL))
	{
	   echo "用户邮箱错误";exit;---验证单个邮箱
	}*/

    //--验证邮箱地址是否真实存在？

    $post=add_str($_POST);
    if(strlen($post["con"])<5)
    {
        echo "邮箱内容不得少于5个字符";exit;
    }
    foreach($post["email"] as $v)
    {
        if(!filter_var($v,FILTER_VALIDATE_EMAIL)){
            echo "用户邮箱错误";exit;
        }
    }
	$email_arr=array_unique($post["email"]);
	$email_arr=array_values($email_arr);
    $mail_addr=count($email_arr);
    $mail_name=count($post["name"]);
    if(($mail_name!=$mail_addr) && ($mail_name<$mail_addr))
    {
        $post["name"]=$post["name"][0];
    } //---------------------------收件地址对应收件人姓名
    $s_mail=$post["mail"];// 服务器邮箱地址
    if($s_mail=="139")
    {
        $smtpusermail = "";//SMTP服务器的用户邮箱
        $smtpuser= "";//SMTP服务器的用户帐号，注：部分邮箱只需@前面的用户名
        $smtppass = "";//SMTP服务器的用户密码
    }else
    {
        $smtpusermail = "";//SMTP服务器的用户邮箱
        $smtpuser= "";//SMTP服务器的用户帐号，注：部分邮箱只需@前面的用户名
        $smtppass = "";//SMTP服务器的用户密码
    }
    $smtpserver ="smtp.".$s_mail.".com";//---SMTP服务器

    //发送邮件---带附件
    $tit= $post["tit"];
    $con= $post["con"];
   if(!empty($_FILES["file"]["name"][0]) || !empty($post["file_server"]))
   {
       $lang="zh_cn";
       $attachment=array();
       if(!empty($post["file_server"])) //--------------------------------------------选择服务器文件
       {
           $dir_name=basename(dirname(__FILE__));
           $file_server=str_replace("/".$dir_name,".",$post["file_server"]);

          if($lang=="zh_cn")  //----------------------处理中文编码 函数判断文件名 转为 中文gb2312
           {
               $file_server2=getGb2312($file_server);
           }
           if(!file_exists($file_server2))
           {
               echo "文件不存在".$file_server; exit;
           }
           array_push($attachment,$file_server2);
       }

       if(!empty($_FILES["file"]["name"][0]))  //-----------------------------发送本地附件
       {
           $file_c=count($_FILES["file"]["name"]);// -----上传文件个数【本地】
           if($file_c>10)
           {
               echo "本地上传文件超过10个";exit;
           }
           $file_array=array();
           $file_size_c=8388608;
           $file_size_c2=0;
           $file_size_one=2097152;
           $file_name=add_str($_FILES["file"]["name"]);
           for($i=0;$i<$file_c;$i++)
           {
               if(!empty($_FILES["file"]["error"][$i]))
               {
                   $error=file_up_error($_FILES["file"]["error"][$i]);
                   echo $error;break; exit;
               }
               if($_FILES["file"]["size"][$i]>$file_size_one)
               {
                   echo "上传文件".$_FILES["file"]["name"][$i]."大于2MB";exit;
               }
               $file_array[$i]["name"]=$file_name[$i];
               $file_array[$i]["type"]=$_FILES["file"]["type"][$i];
               $file_array[$i]["tmp_name"]=$_FILES["file"]["tmp_name"][$i];
               $file_array[$i]["error"]=$_FILES["file"]["error"][$i];
               $file_array[$i]["size"]=$_FILES["file"]["size"][$i];
               $file_size_c2+=$_FILES["file"]["size"][$i];
           }
           if($file_size_c2>$file_size_c)
           {
               echo "上传总文件大小".round($file_size_c/1024/1024,2)."超过8MB";exit;
           }
           $up_dir="./kindeditor/attached/";
	   if(!is_dir($up_dir))
	   {
	    @mkdir($up_dir,0777);
	   }
           foreach($file_array as $k=>$v)
           {
               $new_dir=trim(strrchr($v["type"], '/'),'/');
               $new_dir=$up_dir.$new_dir.'/';

               if(!is_dir($new_dir))
               {
                   mkdir($new_dir,0777);
               }

                if($lang =="zh_cn")  //----------------------处理中文编码 上传文件乱码转为 中文 gb2312
                {
                     $n_name=getGb2312($v["name"]); // 中文字符转义后为空
                     if(!isset($n_name) || empty($n_name))
                     {
                         $n_name=iconv("UTF-8","GB2312//IGNORE",$v["name"]);
                         /*$ext = pathinfo($v["name"]);
                         $n_name="Unknown.".$ext['extension'];*/
                     }
                }else             //---------------------------其他语言
                {
                   $n_name=$v["name"];
                }
               $new_name=$new_dir.time()."_".$n_name;

               if(!move_uploaded_file($v["tmp_name"],$new_name))
               {
                   echo "上传文件失败";
                   break;exit;
               }
               array_push($attachment,$new_name);
           }
       }
       //引入PHPMailer的核心文件 使用require_once包含避免出现PHPMailer类重复定义的警告
       require_once "class.phpmailer.php";
       $mail = new PHPMailer(); //PHPMailer对象
       $tit=getGb2312($tit);
       $con=getGb2312($con);
       $mail->SetLanguage($lang);
       $mail->CharSet = 'GB2312'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
       $mail->Encoding = "base64";
       $mail->IsSMTP();  // 设定使用SMTP服务
       $mail->SMTPDebug = 0;                     // 关闭SMTP调试功能
       $mail->SMTPAuth = true;                  // 启用 SMTP 验证功能
       $mail->SMTPSecure = '';                 // 使用安全协议
       $mail->Host =$smtpserver;  // SMTP 服务器
       $mail->Port = "25";  // SMTP服务器的端口号
       $mail->Username = $smtpusermail;  // SMTP服务器用户名
       $mail->Password = $smtppass;  // SMTP服务器密码
       $mail->Subject =$tit; //邮件标题
       $mail->SetFrom($smtpuser,$tit);
       $mail->MsgHTML($con);
       foreach($email_arr as $k=>$v) //发送多个人员 ---第二个参数不显示
       {
           if(is_array($post["name"]))
           {
               $n=getGb2312($post["name"][$k]);
               $mail->AddAddress($v,$n);
           }
           else
           {
               $n=getGb2312($post["name"]);
               $mail->AddAddress($v,$n);
           }
       }
     // 添加附件
       foreach ($attachment as $k=>$v)
       {
          is_file($v) &&  $mail->AddAttachment($v); // 邮箱接收附件--附件中文名称必须是gb3212,utf8 接收不到
       }
       $state = $mail->Send() ? true : $mail->ErrorInfo;
   }
   else  //-----------------------------------没有附件的
   {
       require_once "Smtp.class.php";
       $smtpserverport =25;//SMTP服务器端口
       $smtp = new Smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);
       //SMTP服务器   SMTP服务器端口  true是表示使用身份验证,否则不使用身份验证  SMTP服务器的用户帐号  SMTP服务器的用户密码
       $smtp->debug = false;//是否显示发送的调试信息
       $state=0;
       foreach($post["email"] as $k=>$v)
       {
           if(is_array($post["name"]))
           {
               $tit.="----用户".$post["name"][$k];
           }else
           {
               $tit.="----用户".$post["name"];
           }
           $state+=$smtp->sendmail($v,$smtpusermail, $tit, $con,"HTML");//发送多人
           //--------收件人邮箱-----SMTP服务器的用户邮箱------//邮件主题 邮件内容 //邮件格式（HTML/TXT）,TXT为文本邮件
       }
       if($mail_addr!=$state)
       {
           echo "发送邮件失败".$mail_addr-$state."个";exit;
       }
  }

	echo "<div style='width:300px; margin:36px auto;'>";
	if(!isset($state) || $state==""){
		echo "对不起，邮件发送失败！请检查邮箱填写是否有误。";
		echo "<a href='index.html'>点此返回</a>";
		exit();
	}
	echo "恭喜！邮件发送成功！！";
	echo "<a href='index.html'>$state 点此返回</a>";
	echo "</div>";

    //**************************************************************************************************************//
    /** 中文字符转义
     * */
    function getGb2312($file) {
        return iconv('UTF-8', 'GB2312', $file);// $mail->CharSet = 'GB2312/GBK'; 默认ISO-8859-1
        //	iconv("UTF-8","GB2312//IGNORE",$data);
    }
    /** 字符转义
     * */
    function add_str($string)
    {
        if(!is_array($string))
            return addslashes(trim($string));
        foreach($string as $key => $val)
        {
            $string[$key] =add_str($val);
        }
        return $string;
    }

    //PHP上传失败
    function file_up_error($f_error)
    {
        switch($f_error){
            case '1':
                $error = '超过php.ini允许的大小。';
                break;
            case '2':
                $error = '超过表单允许的大小。';
                break;
            case '3':
                $error = '图片只有部分被上传。';
                break;
            case '4':
                $error = '请选择图片。';
                break;
            case '6':
                $error = '找不到临时目录。';
                break;
            case '7':
                $error = '写文件到硬盘出错。';
                break;
            case '8':
                $error = 'File upload stopped by extension。';
                break;
            case '999':
            default:
                $error = '未知错误。';
        }
        return $error;

    }


?>
