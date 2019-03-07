<? 

$domain=$_SERVER["HTTP_HOST"];

if($domain=="eggs.or.kr"){ 
    ?> 
    <script> 
    location.href="https://www.eggs.or.kr/resume/view/"; 
    </script> 
    <? 
    exit; 
}

?>