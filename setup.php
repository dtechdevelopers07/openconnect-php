<?php 
class Core
{
	
	function __construct()
	{
		# code...
	}
	public function replace_a_line($data)
    {
	    GLOBAL $username;
	    if (stristr($data, $username))
	    {  
	    	return "";
	    }
	    return $data;
    }
	public function addBulkVPN($jsondata)
    {
        $DataInArray = json_decode($jsondata,true);
        $data="";
        foreach ($DataInArray as $key => $value) {
            if($this->findUser($key)['state']==1){
                unset($DataInArray[$key]);
            }else{
                $data.=$value;
            }
        }
        $file = fopen("/etc/ocserv/ocpasswd", "a");
        fputs($file,$data);
        fclose($file);
        return 1;
    }
    public function deleteBulkVPN($jsondata)
    {
        $DataInArray = json_decode($jsondata,true);
        $data = file("/etc/ocserv/ocpasswd"); 
        $this->backupVPN();
        foreach ($DataInArray as $key) {
        	$response=$this->findUser($key['username']);
            if($response['state']==1){
				$index = array_search($response['line'], $data);
				unset($data[$index]);
		    exec("occtl disconnect user ".$key['username']);
            }
        }
        file_put_contents("/etc/ocserv/ocpasswd", implode('', $data));
        return 1;
    }
	public function addVPN($username,$data)
	{
		$result=0;
	    $response=$this->findUser($username);
	    if($response['state']==1){
	        $result=2;
	    }else{
	    	try {
		        $file = fopen("/etc/ocserv/ocpasswd", "a");
		        fputs($file,$data);
		        fclose($file);
		        $result=1;     	
		    } catch (Exception $e) {
		        $result=0;
		    }
	    }
	    return $result;
	}
  public function writeLogs($string)
    {
    	try {
    		$config=fopen("/etc/ocserv/.ocpasswd_log", "a+") or die("Can't Open File");
        $txt="[ ".date("d/m/Y H:i:s")." ]\t".$string."\r\n";
        fputs($config, $txt);
        fclose($config);
    	} catch (Exception $e) {
    		echo $e;
    	}
    }
	public function deleteVPN($user)
	{ 
		$result=0;
		$response=$this->findUser($user);
	    if($response['state']==1)
	    {
		    $this->backupVPN();
			try{
			    GLOBAL $username;
				$username = $user.':';
				$data = file("/etc/ocserv/ocpasswd"); // reads an array of lines 
				$array = explode("*:",$response['line']);
				$data = array_map(array($this,'replace_a_line'),$data);
				exec("occtl disconnect user ".$user);
				file_put_contents("/etc/ocserv/ocpasswd", implode('', $data));
				$result=1;
		    }catch(Exception $e){
		    	$result=0;
		    }
	    }
	    else
	    {
	       	$result=2;
	    }			
	    return $result;
    }
    public function findUser($user)
    {
        $response = 0;
        try{
            $file=fopen("/etc/ocserv/ocpasswd","r");
            while(!feof($file))
            {
                $line = fgets($file);
                $array = explode(":*:",$line);
                if(trim($array[0]) == $user)
                {
                    $response=1;
                    break;
                }
            }
            fclose($file);
        }catch(Exception $e){
            echo "Failed to open File";
        }
        return array(
        		"state" => $response,
        		"line" => $line
        	);
    }
    public function backupVPN()
    {
    	  $result=0;
        date_default_timezone_set("Asia/Karachi");
        $today= date("M,d,Y h-i-s A");
        $file = '/etc/ocserv/ocpasswd';
        $newfile = '/etc/ocserv/backup/ocpasswd '.$today;
        if (copy($file, $newfile)) {
            $result=1;
        }else{
            $result=0;
        }
    }
    public function numbActiveUser()
    {
        return count(file("/etc/ocserv/ocpasswd"));
    }
}
$core=new Core();
if (isset($_POST['UserName'])) {
	echo $core->addVPN($_POST['UserName'],$_POST['data']);
}else if(isset($_POST['username'])){
	echo $core->deleteVPN($_POST['username']);
}elseif (isset($_POST['jsonData']) && isset($_POST['type'])) {
	if($_POST['type']==1){
		echo $core->addBulkVPN($_POST['jsonData']);
	}else{
		echo $core->deleteBulkVPN($_POST['jsonData']);
	}
}
?>
