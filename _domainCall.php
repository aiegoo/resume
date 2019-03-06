<? 

$domain=$_SERVER["HTTP_HOST"];

if($domain=="eggs.or.kr"){ 
    ?> 
    <script> 
    location.href="http://www.eggs.or.kr/resume/view/"; 
    </script> 
    <? 
    exit; 
}

?>