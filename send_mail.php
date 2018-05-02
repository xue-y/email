<?php
date_default_timezone_set("PRC");
	/**
	 * 已兼容php7
	 * 注：本邮件类都是经过我测试成功了的，如果大家发送邮件的时候遇到了失败的问题，请从以下几点排查：
	 * 1. 用户名和密码是否正确；
	 * 2. 检查邮箱设置是否启用了smtp服务；
	 * 3. 是否是php环境的问题导致；
	 * 4. 将26行的$smtp->debug = false改为true，可以显示错误信息，然后可以复制报错信息到网上搜一下错误的原因；
	 * 5. 如果还是不能解决，可以访问：http://www.daixiaorui.com/read/16.html#viewpl 
	 *    下面的评论中，可能有你要找的答案。
	 *
	 *
	 * Last update time:2017/06
	 * UPDATE:
	 * 1、替换了高版本不支持的写法，如ereg、ereg_replace.
	 * 2、将 var 改为 public/private等.
	 * 3、使其兼容php7.
	 * 
	 */

	if(!filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL))
	{
	   echo "用户邮箱错误";exit;
	}
     $s_mail=addstr($_POST["mail"]);// 服务器邮箱地址

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

	$name= addstr($_POST["name"]);
	$tit= addstr($_POST["tit"]);
	$con= addstr($_POST["con"]);
	$email= addstr($_POST["email"]);

    //发送邮件---带附件---单个文件
   if(isset($_FILES["file"]["name"]) && !empty($_FILES["file"]["name"]))
   {
       if($_FILES["file"]["error"]!==0)
       {
           echo "上传文件错误 .{$_FILES["file"]["error"]}";exit;
       }else
       {
		    $up_dir="./file/";
		   if(!is_dir($up_dir))
		   {
			   if(!mkdir($up_dir,0777))
				   echo "上传文件夹失败";
		   }
          
           $new_name=$up_dir.time()."_".$_FILES["file"]["name"];
           $new_name=getGb2312($new_name);
           if(!move_uploaded_file($_FILES["file"]["tmp_name"],$new_name))
           {
               echo "上传文件失败";exit;
           };
       }
       //引入PHPMailer的核心文件 使用require_once包含避免出现PHPMailer类重复定义的警告
       require_once "class.phpmailer.php";
       $title = getGb2312($tit);
       $mail = new PHPMailer(); //PHPMailer对象
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
       $mail->Subject = getGb2312($tit); //邮件标题
       $mail->SetFrom($smtpuser,getGb2312($tit.'加附件'));
       $mail->MsgHTML(getGb2312($con));
       $mail->AddAddress(getGb2312($email),getGb2312($name));  //发送多个人员 ---第二个参数不显示
 
       $mail->AddAttachment($new_name);
       if (is_array($attachment)) { // 添加附件
           foreach ($attachment as $file) {
               is_file($file) && $mail->AddAttachment($file);
           }
       }
       $state = $mail->Send() ? true : $mail->ErrorInfo;
   }
   else
   {
       require_once "Smtp.class.php";
       //******************** 配置信息 ********************************
       $smtpserverport =25;//SMTP服务器端口
       $smtpemailto = $email;//发送给谁 ---收件人邮箱
       $mailtitle = $name.'用户'.$tit;//邮件主题
       $mailcontent = $con;//邮件内容
       $mailtype = "HTML";//邮件格式（HTML/TXT）,TXT为文本邮件
       //************************ 配置信息 ****************************
       $smtp = new Smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
       //-------------smtp 邮件服务， SMTP服务器端口 ，身份验证，  SMTP服务器用户名，SMTP服务器密码
       $smtp->debug = false;//是否显示发送的调试信息
       $state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailtitle, $mailcontent, $mailtype); //发送多人
   //    $state = $smtp->sendmail("邮箱地址", 发件人, 邮件主题,邮件内容, 邮件格式);
  }
	//过滤字符
    function addstr($str)
	{
		if (!get_magic_quotes_gpc())
		{
			return addslashes(trim($str));
		} else 
		{
			return trim($str);
		}
	}

	// 中文转码
    function getGb2312($file) {
        return iconv('UTF-8', 'GB2312', $file);
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


?>
