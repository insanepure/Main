<?php
require_once('extern/browserdetect/browserdetect.php');
require_once('extern/geoplugin/geoplugin.php');

class LoginCharacter
{
  public $name;
  public $game;
}

class LoginGeoSystem
{
  public $browser = '';
  public $version = '';
  public $platform = '';
  
  public $country = '';
  public $region = '';
  public $city = '';
  public $longitude = '';
  public $latitude = '';
  public $accuracy = 0;
}

class LoginUser
{
  public $id = 0;
  public $password = '';
  
  public $cookies = array();
  public $charas = array();
  public $ips = array();
  public $sessionids = array();
  public $geosys = array();
  
  public $multipoints = 0;
  public $reason = '';
}

class LoginTracker
{
  static function GetMultiInteractions($db, $userid)
  {
  }
  
  static function AddInteraction($db, $charaids, $action, $game)
  {
    $table = 'interactions';
    $timestamp = date('Y-m-d H:i:s');
    $idString = implode(';',$charaids);
    
    //doesn't exist, so we add it
		$db->Insert('time, charaids, action, game',
    '"'.$timestamp.'","'.$idString.'","'.$action.'","'.$game.'"', $table);
  }
  
  static function AddEntry($db, &$users, $row , $reason, $score)
  {
    $newChara = new LoginCharacter();
    $newChara->name = $row['chara'];
    $newChara->game = $row['game'];
    
    $newGeoSys = new LoginGeoSystem();
    $newGeoSys->browser = $row['browser'];
    $newGeoSys->version = $row['version'];
    $newGeoSys->platform = $row['platform'];
    $newGeoSys->country = $row['country'];
    $newGeoSys->region = $row['region'];
    $newGeoSys->city = $row['city'];
    $newGeoSys->latitude = $row['latitude'];
    $newGeoSys->longitude = $row['longitude'];
    $newGeoSys->accuracy = $row['accuracy'];
    
    foreach ($users as &$user) 
    {
      if($row['user'] == $user->id)
      {
        $charaAdded = false;
        foreach ($user->charas as &$chara) 
        {
          //already added
          if($row['chara'] == $chara->name && $row['game'] == $chara->game)
          {
            $charaAdded = true;
            break;
          }
        }
        
        if(!$charaAdded)
          array_push($user->charas, $newChara);
        
        $sysAdded = false;
        foreach ($user->geosys as &$geosys) 
        {
          //already added
          if($row['country'] == $geosys->country && $row['region'] == $geosys->region && $row['city'] == $geosys->city && 
             $row['longitude'] == $geosys->longitude && $row['latitude'] == $geosys->latitude && $row['accuracy'] == $geosys->accuracy &&
             $row['browser'] == $geosys->browser && $row['version'] == $geosys->version && $row['platform'] == $geosys->platform)
          {
            $sysAdded = true;
            break;
          }
        }
        
        if(!$sysAdded)
          array_push($user->geosys, $newGeoSys);
        
        $ipAdded = false;
        foreach ($user->ips as &$ip) 
        {
          //already added
          if($row['ip'] == $ip)
          {
            $ipAdded = true;
            break;
          }
        }
        if(!$ipAdded)
          array_push($user->ips, $row['ip']);
        
        $cookieAdded = false;
        foreach ($user->cookies as &$cookie) 
        {
          //already added
          if($row['cookie'] == $cookie)
          {
            $cookieAdded = true;
            break;
          }
        }
        if(!$cookieAdded)
          array_push($user->cookies, $row['cookie']);
        
        
        $sessAdded = false;
        foreach ($user->sessionids as &$sessionid) 
        {
          //already added
          if($row['sessionid'] == $sessionid)
          {
            $sessAdded = true;
            break;
          }
        }
        if(!$sessAdded)
          array_push($user->sessionids, $row['sessionid']);
        
        return $user;
      }
    }
    
    //new User
    $newUser = new LoginUser();
    $newUser->id = $row['user'];
    $newUser->password = $row['password'];
    $newUser->multipoints = $score;
    $newUser->reason = $reason;   
    
    array_push($newUser->charas, $newChara);
    array_push($newUser->geosys, $newGeoSys);
    
    array_push($newUser->ips, $row['ip']);
    array_push($newUser->sessionids, $row['sessionid']);
    array_push($newUser->cookies, $row['cookie']);
    
    array_push($users, $newUser);
    
    return $newUser;
  }
  
  static function CheckCookies($db, $game)
  {
    $selectMaxLimit = 10000;
    $selectLimit = 1;
    
    $users = array();
    $result = $db->Select('*','logins','user !=cookie AND game="'.$game.'" AND user != 0',$selectMaxLimit);
    if ($result) 
    {
      if ($result->num_rows > 0)
      {
        while($row = $result->fetch_assoc()) 
        {
           LoginTracker::AddEntry($db, $users, $row, 'Selber Cookie wie ID '.$row['cookie'], 100);
        }
      }
      $result->close();
    }
    
    foreach ($users as &$user) 
    {
      foreach($user->cookies as &$cookie) 
      {
        //check for cookie
        $result = $db->Select('*','logins','user="'.$cookie.'" AND game="'.$game.'"',$selectLimit);
        if ($result) 
        {
          if ($result->num_rows > 0)
          {
            while($row = $result->fetch_assoc()) 
            {
              LoginTracker::AddEntry($db, $users, $row, 'Andere ID vom Cookie '.$cookie, 100);
            }
          }
          $result->close();
        }
      }
    }
    
    $returnText = '';
    foreach ($users as &$user) 
    {
      if($user->multipoints < $minScore)
        continue;
      
      $returnText =  $returnText.'UserID: <b>'.$user->id.'</b></br>';
      $returnText =  $returnText.' - Reason: <b>'.$user->reason.'</b><br/>';
      foreach ($user->charas as &$chara) 
      {
        $returnText =  $returnText.'Character: <b>'.$chara->name.'</b> von <b>'.$chara->game.'</b></br>';
      }
      $returnText =  $returnText.'<br/>';
    }
    
    return $returnText;
    
  }
  
  static function CheckCharacter($db, $chara, $game, $minScore)
  {
    $users = array();
    
    $selectMaxLimit = 100;
    $selectLimit = 1;
    
    //get the Main Account for this Character
    $result = $db->Select('*','logins','chara="'.$chara.'" AND game="'.$game.'" AND user != 0',$selectMaxLimit);
    if ($result) 
    {
      if ($result->num_rows > 0)
      {
        while($row = $result->fetch_assoc()) 
        {
           LoginTracker::AddEntry($db, $users, $row, 'Hauptaccount', 100);
        }
      }
      $result->close();
    }
    
    foreach ($users as &$user) 
    {
      
      //Get all of the characters for this user
      $result = $db->Select('*','logins','user='.$user->id.' ',$selectLimit);
      if ($result) 
      {
        if ($result->num_rows > 0)
        {
          while($row = $result->fetch_assoc()) 
          {
            LoginTracker::AddEntry($db, $users, $row, 'Hauptaccount', 100);
          }
        }
        $result->close();
      }
    
      foreach ($user->cookies as &$cookie) 
      {
        //check for cookie
        $result = $db->Select('*','logins','cookie="'.$cookie.'"',$selectLimit);
        if ($result) 
        {
          if ($result->num_rows > 0)
          {
            while($row = $result->fetch_assoc()) 
            {
              LoginTracker::AddEntry($db, $users, $row, 'Selber Cookie', 100);
            }
          }
          $result->close();
        }
      }
    
      foreach ($user->sessionids as &$sessionid) 
      {
        //check for sessionids
        $result = $db->Select('*','logins','sessionid="'.$sessionid.'"',$selectLimit);
        if ($result) 
        {
          if ($result->num_rows > 0)
          {
            while($row = $result->fetch_assoc()) 
            {
              LoginTracker::AddEntry($db, $users, $row, 'Selbe Session', 100);
            }
          }
          $result->close();
        }
      }
    
      //check for password
      $result = $db->Select('*','logins','password="'.$user->password.'"',$selectLimit);
      if ($result) 
      {
        if ($result->num_rows > 0)
        {
          while($row = $result->fetch_assoc()) 
          {
            LoginTracker::AddEntry($db, $users, $row, 'Password', 100);
          }
        }
        $result->close();
      }
    
      foreach ($user->ips as &$ip) 
      {
        //check for ip
        $result = $db->Select('*','logins','ip="'.$ip.'"',$selectLimit);
        if ($result) 
        {
          if ($result->num_rows > 0)
          {
            while($row = $result->fetch_assoc()) 
            {
              LoginTracker::AddEntry($db, $users, $row, 'Selbe IP: '.$ip, 50);
            }
          }
          $result->close();
        }
      }
      
    }
    
    $otherUsers = array();
    //Now check geolocation and system
    foreach ($users as &$user) 
    {
      foreach ($user->geosys as &$geosys) 
      {
          //check for location
          $result = $db->Select('*','logins','',$selectLimit);
          if ($result) 
          {
            if ($result->num_rows > 0)
            {
              while($row = $result->fetch_assoc()) 
              {
                $score = 0;
                $reason = '';
                if($row['country'] != '' && $row['country'] == $geosys->country && $row['region'] != '' && $row['region'] == $geosys->region && $row['city'] != '' && $row['city'] == $geosys->city 
                   && $row['accuracy'] <= 50 && $geosys->accuracy <= 50)
                {
                  if($row['accuracy'] != 0 && $row['latitude'] == $geosys->latitude && $row['longitude'] == $geosys->longitude && $row['accuracy'] <= 20)
                  {
                    if($reason != '')
                      $reason = $reason.' & ';
                    $reason = $reason.'Selbe GPS zwischen '.$user->id.' und '.$row['user'];
                    $score += 75;
                  }
                  else
                  {
                    $geoDiff = abs($row['latitude'] - $geosys->latitude) + abs( $row['longitude'] - $geosys->longitude);
                    $geoDiff = 50 - round($geoDiff * 50);
                    $latitudeDiff = abs($row['latitude'] - $geosys->latitude);
                    
                    if($reason != '')
                      $reason = $reason.' & ';
                    $reason = $reason.'Selbe Location';
                    $score += $geoDiff;
                  }
                }
                
                if($row['browser'] == $geosys->browser && $row['platform'] == $geosys->platform && $row['version'] == $geosys->version)
                {
                  if($reason != '')
                    $reason = $reason.' & ';
                  $reason = $reason.'Selbes System';
                  $score += 25;
                }
                
                if($score > 20)
                  LoginTracker::AddEntry($db, $otherUsers, $row, $reason, $score);
              }
            }
            $result->close();
          }
      }
    }
    
    foreach ($otherUsers as &$otherUser) 
    {
      $isWithin = false;
      foreach ($users as &$user) 
      {
        if($otherUser->id == $user->id)
        {
          $isWithin = true;
        }
      }
      
      if(!$isWithin)
      array_push($users, $otherUser);
    }
    
    $returnText = '';
    foreach ($users as &$user) 
    {
      if($user->multipoints < $minScore)
        continue;
      
      $returnText =  $returnText.'UserID: <b>'.$user->id.'</b></br>';
      $returnText =  $returnText.' - MultiScore: <b>'.$user->multipoints.'</b><br/>';
      $returnText =  $returnText.' - Reason: <b>'.$user->reason.'</b><br/>';
      foreach ($user->charas as &$chara) 
      {
        $returnText =  $returnText.'Character: <b>'.$chara->name.'</b> von <b>'.$chara->game.'</b></br>';
      }
      $returnText =  $returnText.'<br/>';
    }
    
    return $returnText;
  }
  
  static function TrackReferer()
  {
    $cookieName = 'referer';    
    $referer = $_SERVER['HTTP_REFERER'];
    $cookie = $referer;
    if(!isset($_COOKIE[$cookieName]))
    {
      setcookie( $cookieName,  $cookie, 0 );
    }
  }
  
  static function TrackUser($db, $user, $chara, $game, $password, $email, $sessionid, $ip, $realip, $cookieConsent=false)
  {
    if($chara == 'Google')
      return;
    
    $maxRequests = 100;
    $table = 'logins';
    
    $browser = new Wolfcast\BrowserDetection();
    
    $platform = $browser->getPlatformVersion();
    $browserName = $browser->getName();
    $version = $browser->getVersion();
    
    $referer = $_SERVER['HTTP_REFERER'];
    $cookieName = 'referer';
    if(isset($_COOKIE[$cookieName]))
    {
      $referer = $_COOKIE[$cookieName];
    }
    
    $timestamp = date('Y-m-d H:i:s');
    
    $cookieName = 'logincookie';    
    
    $cookie = $user;
    if(isset($_COOKIE[$cookieName]))
      $cookie = $_COOKIE[$cookieName];
    else if($cookieConsent)
    {
      $date_of_expiry = time() + 60 * 60 * 24 * 30;
      setcookie( $cookieName,  $cookie, $date_of_expiry );
    }
      
    
    /*
    if($user == 1)
    {
		  $result = $db->Select('time', $table,'TIMESTAMPDIFF(SECOND, time, NOW()) <= 60',100);
      echo $result->num_rows;
    }
    */
    
    //Check if this already exists
    $result = $db->Select('id, user, chara, game, ip, sessionid, cookie, browser, platform, version', $table, 'user='.$user.'', 1, 'id', 'DESC');
    if($result && $result->num_rows != 0)
    {
      $row = $result->fetch_assoc();
      if(
         $row['user'] == $user 
      && $row['chara'] == $chara
      && $row['game'] == $game
      && $row['ip'] == $ip
      && $row['sessionid'] == $sessionid
      && $row['cookie'] == $cookie
      && $row['browser'] == $browserName
      && $row['platform'] == $platform
      && $row['version'] == $version
      )
      {
        $result->close();
        return;
      }
      $result->close();
    }
    
    $city = '';
    $region = '';
    $country = '';
    $latitude = '';
    $longitude = '';
    $accuracyMiles = '';
    
		$result = $db->Select('time', $table,'TIMESTAMPDIFF(SECOND, time, NOW()) <= 60',$maxRequests);
    
    if($result && $result->num_rows < $maxRequests)
    {
      $geoplugin = new geoPlugin();
      $geoplugin->locate($realip);
      $city = $geoplugin->city;
      $region = $geoplugin->region;
      $country = $geoplugin->countryName;
      $latitude = $geoplugin->latitude;
      $longitude = $geoplugin->longitude;
      $accuracyMiles = $geoplugin->locationAccuracyRadius;
    }
    
    
    
    //doesn't exist, so we add it
		$db->Insert('time, user, chara, game, password, email, sessionid, ip, cookie, browser, platform, version, city, region, country, latitude, longitude, accuracy, referer',
    '"'.$timestamp.'","'.$user.'","'.$chara.'","'.$game.'","'.$password.'","'.$email.'","'.$sessionid.'",
    "'.$ip.'","'.$cookie.'","'.$browserName.'","'.$platform.'","'.$version.'","'.$city.'","'.$region.'"
    ,"'.$country.'","'.$latitude.'","'.$longitude.'","'.$accuracyMiles.'", "'.$referer.'"', $table);
    
  }
}

?>