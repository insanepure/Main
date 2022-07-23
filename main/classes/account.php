<?php
class Account
{
	private $database;
	private $data;
	private $localplayer;
  private $valid;
	
	function __construct($db, $id=0)
	{
    $this->valid = false;
    $this->database = $db;
		$this->data = array();
    
    //$id if we want to get another user
		$this->data['id'] = $id;
		$key = 'id';
    $value = $id;
    
    //if no id is defined, we're trying to get the user by sessionid
		if($id == 0)
		{
			$key = 'sessionid';
      $value = session_id();
			$this->data[$key] = $value;
			$this->localplayer = true;
		}
    
    if($value == '')
      return;
    
		$this->LoadPlayer($key, '*', $value);

    if(!$this->IsLogged() && isset($_POST['GoogleCrawler']))
    { 
      $googleACC = 640;
      $googlePW = $this->GetSessionPW('');
      $this->LoginSession($googleACC, $googlePW);
		  $this->data['id'] = $googleACC;
    }
    
    if(!$this->IsLogged() && isset($_COOKIE['accid']) && isset($_COOKIE['accpw']))
    {
      $this->LoginSession($_COOKIE['accid'], $_COOKIE['accpw']);
    }
    
    if($this->IsLogged())
    {
      $this->UpdateLastAction();
    }
    
  }
  
  public function DeleteAccount()
  {
		$result = $this->database->Delete('users','id = "'.$this->Get('id').'"',1);
  }
  
  public function HasAnyCharacter()
  {
    $nbgpw = '';
    $oppw = '';
    $dbpw = '';
    $nbg1Characters = $this->GetCharacterCount('n_bg_db1','n_bg_db1', $nbgpw, 'charaktere', 'main='.$this->Get('id').'');
    $nbg2Characters = $this->GetCharacterCount('n_bg_db2','n_bg_db2', $nbgpw, 'charaktere', 'main='.$this->Get('id').'');
    $dbbgCharacters = $this->GetCharacterCount('db_bg_db1', 'db_bg_db1', $dbpw, 'accounts', 'userid='.$this->Get('id').'');
    $opbgCharacters = $this->GetCharacterCount('op_bg_db1', 'op_bg_db1', $oppw, 'accounts', 'main='.$this->Get('id').'');
    
    return $nbg1Characters != 0 || $nbg2Characters != 0 || $dbbgCharacters != 0 || $opbgCharacters != 0;
  }
  
  public function GetCharacterCount($dbName, $user, $pw, $table, $where)
  {
    $database = new Database($dbName, $user, $pw);
		$result = $database->Select('*',$table,$where,1);
    
    $characterCount = 0;
		if ($result) 
		{
      $characterCount = $result->num_rows > 0;
			$result->close();
		}
    return $characterCount;
  }
	
	public function GetRealIP()
	{
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $ip  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    return $ip;
  }
  
	public function GetIP()
	{
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $ip  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    $ip = hash('sha256', 'DASISTEIN'.$ip.'HASHDERIP');
    
    return $ip;
	}
  
  public function Activate($id, $code)
  {
    $id = $this->database->EscapeString($id);
		$code = $this->database->EscapeString($code);
    
    $activated = false;
		$result = $this->database->Select('login, password, email, activationcode','activations','id = "'.$id.'" AND activationcode="'.$code.'"',1);
    
    $login = '';
    $password = '';
    $email = '';
		if ($result) 
		{
		  if ($result->num_rows > 0)
			{
				$row = $result->fetch_assoc();
        $login = $row['login'];
        $password = $row['password'];
        $email = $row['email'];
        $activated = true;
			}
			$result->close();
		}
    
    if($activated)
    {
		  $this->database->Insert('login,password,email','"'.$login.'","'.$password.'","'.$email.'"', 'users');
		  $this->database->Delete('activations','login = "'.$login.'"',999999999);
    }
    
    return $activated;
  }
	
	public function IsValid()
	{
		return $this->valid;
	}
  
  public function Get($key)
  {
    return $this->data[$key];
  }
	
	public function IsLogged()
	{
		return $this->data['id'] != 0;
	}
  
  public function GetBanReason()
  {
    return $this->data['banreason'];
  }
  
  public function IsBanned()
  {
    return $this->data['bannedgames'] != '';
  }
  
  public function IsBannedInGame($game)
  {
    $bannedGames = explode(';',$this->data['bannedgames']);
    return in_array(strtolower($game), $bannedGames);
  }
  
  public function GetCode($acc, $email)
  {
    return md5($acc.$email);
  }
  
  public static function GetPassword($pw)
  {
    $pw = hash('sha256', 'MeinsteIchZeig'.$pw.'DenHashHiervon?');
		return md5($pw);
  }
  
  public function GetSessionPW($pw)
  {
    $pw = hash('sha256', 'ExtraSession'.$pw.'PWHashUndSo');
		return md5($pw);
  }
	
	private function UpdateLastAction()
	{
    $timestamp = date('Y-m-d H:i:s');
		$update = 'lastAction="'.$timestamp.'"';
		$result = $this->database->Update($update,'users','id = '.$this->data['id'].'',1);
	}
	
	public function UpdateRecaptcha($recaptcha)
	{
		$update = 'recaptchascore="'.$recaptcha->score.'"';
		$result = $this->database->Update($update,'users','id = '.$this->data['id'].'',1);
	}
	
	public function Logout()
	{
		$result = $this->database->Update('sessionid=""','users','id = '.$this->data['id'].'',1);
		$this->data = null;
    
    $date_of_expiry = time() - 10;
    setcookie( "accid",  "", $date_of_expiry );
    setcookie( "accpw",  "", $date_of_expiry );
	}
	
	public function LoginSession($id, $pw)
	{
    $id = $this->database->EscapeString($id);
    $pw = $this->database->EscapeString($pw);
    
		$result = $this->database->Select('id, password','users','id='.$id.'',1);
    
    $id = 0;
		if ($result) 
		{
		  if ($result->num_rows > 0)
			{
				$row = $result->fetch_assoc();
        $pwHash = $this->GetSessionPW($row['password']);
        if($pwHash == $pw)
        {
          $id = $row['id'];
        }
			}
			$result->close();
		}
    
    if($id == 0)
      return false;
    
    $this->LoginInternal($id, true);
    
    return true;
  }
  
	public function LoginSafe($name, $pw, $stayLogged)
	{
    $name = $this->database->EscapeString($name);
    $pw = $this->database->EscapeString($pw);
    
		$result = $this->database->Select('id','users','login="'.$name.'" AND password = "'.$pw.'"',1);
    
    $id = 0;
		if ($result) 
		{
		  if ($result->num_rows > 0)
			{
				$row = $result->fetch_assoc();
        $id = $row['id'];
			}
			$result->close();
		}
    
    if($id == 0)
      return false;
		
    $this->LoginInternal($id, $stayLogged);
    
		return true;
	}
  
  
	private function LoginInternal($id, $stayLogged)
	{
		$result = $this->database->Update('ip="'.$this->GetIP().'", sessionid="'.session_id().'"','users','id='.$id.'',1);
    
		$this->LoadPlayer('id', '*', $id);
    
    if($stayLogged)
    {
      $date_of_expiry = time() + 60 * 60 * 24 * 30;
      setcookie( "accid",  $id, $date_of_expiry );
      setcookie( "accpw",  $this->GetSessionPW($this->data['password']), $date_of_expiry );
    }
    else
    {
      $date_of_expiry = time() - 10;
      setcookie( "accid",  "", $date_of_expiry );
      setcookie( "accpw",  "", $date_of_expiry );
    }
  }
	
	public function ChangePassword($pw)
	{
    if(!$this->IsLogged())
      return;
    
    $pw = $this->database->EscapeString($pw);
    $pw = Account::GetPassword($pw);
    
		$result = $this->database->Update('password="'.$pw.'"','users','id='.$this->Get('id').'',1);
  }
	
	public function ChangePasswordSafe($pw)
	{
    if(!$this->IsLogged())
      return;
    
    $pw = $this->database->EscapeString($pw);
    
		$result = $this->database->Update('password="'.$pw.'"','users','id='.$this->Get('id').'',1);
  }
	
	public function RegisterSafe($acc, $pw, $email)
	{
		//Return -1 = acc invalid
    //Return -2 = email invalid
    //Return -3 = account taken
    //Return -4 = email taken
    
    $acc = $this->database->EscapeString($acc);
		$email = $this->database->EscapeString($email);
    $pw = $this->database->EscapeString($pw);
		
		if($acc == '')
		{
			return -1;
		}
		else if($email == '' || !isset(explode('@', $email)[1]))
		{
			return -2;
		}
   
    $invalidEmails = array("0815.ru", "0815.ru0clickemail.com", "0815.ry", "0815.su", "0845.ru", "0clickemail.com", "0-mail.com", "0wnd.net", "0wnd.org", "10mail.com", "10mail.org", "10minut.com.pl", "10minutemail.cf", "10minutemail.co.za", "10minutemail.com", "10minutemail.de", "10minutemail.ga", "10minutemail.gq", "10minutemail.ml", "10minutemail.net", "10minutesmail.com", "10x9.com", "123-m.com", "126.com", "12houremail.com", "12minutemail.com", "12minutemail.net", "139.com", "163.com", "1ce.us", "1chuan.com", "1fsdfdsfsdf.tk", "1mail.ml", "1pad.de", "1zhuan.com", "20mail.it", "20minutemail.com", "21cn.com", "24hourmail.com", "2fdgdfgdfgdf.tk", "2prong.com", "30minutemail.com", "33mail.com", "3d-painting.com", "3mail.ga", "3trtretgfrfe.tk", "420blaze.it", "4gfdsgfdgfd.tk", "4mail.cf", "4mail.ga", "4warding.com", "4warding.net", "4warding.org", "5ghgfhfghfgh.tk", "5mail.cf", "5mail.ga", "60minutemail.com", "675hosting.com", "675hosting.net", "675hosting.org", "6hjgjhgkilkj.tk", "6ip.us", "6mail.cf", "6mail.ga", "6mail.ml", "6paq.com", "6url.com", "75hosting.com", "75hosting.net", "75hosting.org", "7days-printing.com", "7mail.ga", "7mail.ml", "7tags.com", "8127ep.com", "8chan.co", "8mail.cf", "8mail.ga", "8mail.ml", "99experts.com", "9mail.cf", "9ox.net", "a.mailcker.com", "a.vztc.com", "a45.in", "a-bc.net", "abyssmail.com", "afrobacon.com", "ag.us.to", "agedmail.com", "ajaxapp.net", "akapost.com", "akerd.com", "aktiefmail.nl", "alivance.com", "amail4.me", "ama-trade.de", "ama-trans.de", "amilegit.com", "amiri.net", "amiriindustries.com", "anappthat.com", "ano-mail.net", "anonbox.net", "anon-mail.de", "anonmails.de", "anonymail.dk", "anonymbox.com", "anonymousmail.org", "anonymousspeech.com", "antichef.com", "antichef.net", "antireg.com", "antireg.ru", "antispam.de", "antispam24.de", "antispammail.de", "armyspy.com", "artman-conception.com", "asdasd.nl", "asdasd.ru", "atvclub.msk.ru", "auti.st", "avpa.nl", "azmeil.tk", "b2cmail.de", "baxomale.ht.cx", "beddly.com", "beefmilk.com", "big1.us", "bigprofessor.so", "bigstring.com", "binkmail.com", "bio-muesli.info", "bio-muesli.net", "blackmarket.to", "bladesmail.net", "bloatbox.com", "blogmyway.org", "blogos.com", "bluebottle.com", "bobmail.info", "bodhi.lawlita.com", "bofthew.com", "bootybay.de", "boun.cr", "bouncr.com", "boxformail.in", "boximail.com", "br.mintemail.com", "brainonfire.net", "breakthru.com", "brefmail.com", "brennendesreich.de", "broadbandninja.com", "bsnow.net", "bspamfree.org", "bu.mintemail.com", "buffemail.com", "bugmenever.com", "bugmenot.com", "bumpymail.com", "bund.us", "bundes-li.ga", "burnthespam.info", "burstmail.info", "buymoreplays.com", "buyusedlibrarybooks.org", "byom.de", "c2.hu", "cachedot.net", "cam4you.cc", "card.zp.ua", "casualdx.com", "cc.liamria", "cek.pm", "cellurl.com", "centermail.com", "centermail.net", "chammy.info", "cheatmail.de", "childsavetrust.org", "chogmail.com", "choicemail1.com", "chong-mail.com", "chong-mail.net", "chong-mail.org", "clixser.com", "clrmail.com", "cmail.com", "cmail.net", "cmail.org", "cock.li", "coieo.com", "coldemail.info", "consumerriot.com", "cool.fr.nf", "correo.blogos.net", "cosmorph.com", "courriel.fr.nf", "courrieltemporaire.com", "crapmail.org", "crazymailing.com", "cubiclink.com", "cumallover.me", "curryworld.de", "cust.in", "cuvox.de", "d3p.dk", "dacoolest.com", "dandikmail.com", "dayrep.com", "dbunker.com", "dcemail.com", "deadaddress.com", "deadchildren.org", "deadfake.cf", "deadfake.ga", "deadfake.ml", "deadfake.tk", "deadspam.com", "deagot.com", "dealja.com", "delikkt.de", "despam.it", "despammed.com", "devnullmail.com", "dfgh.net", "dharmatel.net", "dicksinhisan.us", "dicksinmyan.us", "digitalsanctuary.com", "dingbone.com", "discard.cf", "discard.email", "discard.ga", "discard.gq", "discard.ml", "discard.tk", "discardmail.com", "discardmail.de", "disposable.cf", "disposable.ga", "disposable.ml", "disposableaddress.com", "disposable-email.ml", "disposableemailaddresses.com", "disposableinbox.com", "dispose.it", "disposeamail.com", "disposemail.com", "dispostable.com", "divermail.com", "dm.w3internet.co.uk", "dm.w3internet.co.ukexample.com", "docmail.com", "dodgeit.com", "dodgit.com", "dodgit.org", "doiea.com", "domozmail.com", "donemail.ru", "dontreg.com", "dontsendmespam.de", "dotman.de", "dotmsg.com", "drdrb.com", "drdrb.net", "dropcake.de", "droplister.com", "dropmail.me", "dudmail.com", "dumpandjunk.com", "dump-email.info", "dumpmail.de", "dumpyemail.com", "duskmail.com", "e4ward.com", "easytrashmail.com", "edv.to", "ee1.pl", "ee2.pl", "eelmail.com", "einmalmail.de", "einrot.com", "einrot.de", "eintagsmail.de", "e-mail.com", "email.net", "e-mail.org", "email60.com", "emailage.cf", "emailage.ga", "emailage.gq", "emailage.ml", "emailage.tk", "emaildienst.de", "email-fake.cf", "email-fake.ga", "email-fake.gq", "email-fake.ml", "email-fake.tk", "emailgo.de", "emailias.com", "emailigo.de", "emailinfive.com", "emaillime.com", "emailmiser.com", "emails.ga", "emailsensei.com", "emailspam.cf", "emailspam.ga", "emailspam.gq", "emailspam.ml", "emailspam.tk", "emailtemporanea.com", "emailtemporanea.net", "emailtemporar.ro", "emailtemporario.com.br", "emailthe.net", "emailtmp.com", "emailto.de", "emailwarden.com", "emailx.at.hm", "emailxfer.com", "emailz.cf", "emailz.ga", "emailz.gq", "emailz.ml", "emeil.in", "emeil.ir", "emkei.cf", "emkei.ga", "emkei.gq", "emkei.ml", "emkei.tk", "emz.net", "enterto.com", "ephemail.net", "e-postkasten.com", "e-postkasten.de", "e-postkasten.eu", "e-postkasten.info", "ero-tube.org", "etranquil.com", "etranquil.net", "etranquil.org", "evopo.com", "example.com", "explodemail.com", "express.net.ua", "eyepaste.com", "facebook-email.cf", "facebook-email.ga", "facebook-email.ml", "facebookmail.gq", "facebookmail.ml", "faecesmail.me", "fakedemail.com", "fakeinbox.cf", "fakeinbox.com", "fakeinbox.ga", "fakeinbox.ml", "fakeinbox.tk", "fakeinformation.com", "fake-mail.cf", "fakemail.fr", "fake-mail.ga", "fake-mail.ml", "fakemailgenerator.com", "fakemailz.com", "fammix.com", "fansworldwide.de", "fantasymail.de", "fastacura.com", "fastchevy.com", "fastchrysler.com", "fastermail.com", "fastkawasaki.com", "fastmail.fm", "fastmazda.com", "fastmitsubishi.com", "fastnissan.com", "fastsubaru.com", "fastsuzuki.com", "fasttoyota.com", "fastyamaha.com", "fatflap.com", "fdfdsfds.com", "fightallspam.com", "film-blog.biz", "filzmail.com", "fivemail.de", "fixmail.tk", "fizmail.com", "fleckens.hu", "flurred.com", "flyspam.com", "fly-ts.de", "footard.com", "forgetmail.com", "fornow.eu", "fr33mail.info", "frapmail.com", "freecoolemail.com", "free-email.cf", "free-email.ga", "freeletter.me", "freemail.ms", "freemails.cf", "freemails.ga", "freemails.ml", "freundin.ru", "friendlymail.co.uk", "front14.org", "fuckingduh.com", "fuckmail.me", "fudgerub.com", "fux0ringduh.com", "fyii.de", "garbagemail.org", "garliclife.com", "garrifulio.mailexpire.com", "gawab.com", "gehensiemirnichtaufdensack.de", "gelitik.in", "geschent.biz", "get1mail.com", "get2mail.fr", "getairmail.cf", "getairmail.com", "getairmail.ga", "getairmail.gq", "getairmail.ml", "getairmail.tk", "get-mail.cf", "get-mail.ga", "get-mail.ml", "get-mail.tk", "getmails.eu", "getonemail.com", "getonemail.net", "ghosttexter.de", "giantmail.de", "girlsundertheinfluence.com", "gishpuppy.com", "gmal.com", "gmial.com", "gmx.com", "goat.si", "goemailgo.com", "gomail.in", "gorillaswithdirtyarmpits.com", "gotmail.com", "gotmail.net", "gotmail.org", "gotti.otherinbox.com", "gowikibooks.com", "gowikicampus.com", "gowikicars.com", "gowikifilms.com", "gowikigames.com", "gowikimusic.com", "gowikinetwork.com", "gowikitravel.com", "gowikitv.com", "grandmamail.com", "grandmasmail.com", "great-host.in", "greensloth.com", "grr.la", "gsrv.co.uk", "guerillamail.biz", "guerillamail.com", "guerillamail.net", "guerillamail.org", "guerillamailblock.com", "guerrillamail.biz", "guerrillamail.com", "guerrillamail.de", "guerrillamail.info", "guerrillamail.net", "guerrillamail.org", "guerrillamailblock.com", "gustr.com", "h.mintemail.com", "h8s.org", "hacccc.com", "haltospam.com", "harakirimail.com", "hartbot.de", "hatespam.org", "hat-geld.de", "herp.in", "hidemail.de", "hidzz.com", "hmamail.com", "hochsitze.com", "hooohush.ai", "hopemail.biz", "horsefucker.org", "hotmai.com", "hot-mail.cf", "hot-mail.ga", "hot-mail.gq", "hot-mail.ml", "hot-mail.tk", "hotmial.com", "hotpop.com", "huajiachem.cn", "hulapla.de", "humaility.com", "hush.ai", "hush.com", "hushmail.com", "hushmail.me", "i2pmail.org", "ieatspam.eu", "ieatspam.info", "ieh-mail.de", "ignoremail.com", "ihateyoualot.info", "iheartspam.org", "ikbenspamvrij.nl", "imails.info", "imgof.com", "imgv.de", "imstations.com", "inbax.tk", "inbox.si", "inbox2.info", "inboxalias.com", "inboxclean.com", "inboxclean.org", "inboxdesign.me", "inboxed.im", "inboxed.pw", "inboxstore.me", "incognitomail.com", "incognitomail.net", "incognitomail.org", "infocom.zp.ua", "insorg-mail.info", "instantemailaddress.com", "instant-mail.de", "iozak.com", "ip6.li", "ipoo.org", "irish2me.com", "iroid.com", "is.af", "iwantmyname.com", "iwi.net", "jetable.com", "jetable.fr.nf", "jetable.net", "jetable.org", "jnxjn.com", "jourrapide.com", "jsrsolutions.com", "junk.to", "junk1e.com", "junkmail.ga", "junkmail.gq", "k2-herbal-incenses.com", "kasmail.com", "kaspop.com", "keepmymail.com", "killmail.com", "killmail.net", "kir.ch.tc", "klassmaster.com", "klassmaster.net", "klzlk.com", "kmhow.com", "kostenlosemailadresse.de", "koszmail.pl", "kulturbetrieb.info", "kurzepost.de", "l33r.eu", "lackmail.net", "lags.us", "landmail.co", "lastmail.co", "lavabit.com", "lawlita.com", "letthemeatspam.com", "lhsdv.com", "lifebyfood.com", "link2mail.net", "linuxmail.so", "litedrop.com", "llogin.ru", "loadby.us", "login-email.cf", "login-email.ga", "login-email.ml", "login-email.tk", "lol.com", "lol.ovpn.to", "lolfreak.net", "lookugly.com", "lopl.co.cc", "lortemail.dk", "losemymail.com", "lovebitco.in", "lovemeleaveme.com", "loves.dicksinhisan.us", "loves.dicksinmyan.us", "lr7.us", "lr78.com", "lroid.com", "luckymail.org", "lukop.dk", "luv2.us", "m21.cc", "m4ilweb.info", "ma1l.bij.pl", "maboard.com", "mac.hush.com", "mail.by", "mail.me", "mail.mezimages.net", "mail.ru", "mail.zp.ua", "mail114.net", "mail1a.de", "mail21.cc", "mail2rss.org", "mail2world.com", "mail333.com", "mail4trash.com", "mailbidon.com", "mailbiz.biz", "mailblocks.com", "mailbucket.org", "mailcat.biz", "mailcatch.com", "mailde.de", "mailde.info", "maildrop.cc", "maildrop.cf", "maildrop.ga", "maildrop.gq", "maildrop.ml", "maildu.de", "maileater.com", "mailed.in", "maileimer.de", "mailexpire.com", "mailfa.tk", "mail-filter.com", "mailforspam.com", "mailfree.ga", "mailfree.gq", "mailfree.ml", "mailfreeonline.com", "mailguard.me", "mailhazard.com", "mailhazard.us", "mailhz.me", "mailimate.com", "mailin8r.com", "mailinater.com", "mailinator.com", "mailinator.gq", "mailinator.net", "mailinator.org", "mailinator.us", "mailinator2.com", "mailincubator.com", "mailismagic.com", "mailita.tk", "mailjunk.cf", "mailjunk.ga", "mailjunk.gq", "mailjunk.ml", "mailjunk.tk", "mailme.gq", "mailme.ir", "mailme.lv", "mailme24.com", "mailmetrash.com", "mailmoat.com", "mailms.com", "mailnator.com", "mailnesia.com", "mailnull.com", "mailorg.org", "mailpick.biz", "mailquack.com", "mailrock.biz", "mailsac.com", "mailscrap.com", "mailseal.de", "mailshell.com", "mailsiphon.com", "mailslapping.com", "mailslite.com", "mailtemp.info", "mail-temporaire.fr", "mailtome.de", "mailtothis.com", "mailtrash.net", "mailtv.net", "mailtv.tv", "mailwithyou.com", "mailzilla.com", "mailzilla.org", "makemetheking.com", "malahov.de", "manifestgenerator.com", "manybrain.com", "mbx.cc", "mega.zik.dj", "meinspamschutz.de", "meltmail.com", "messagebeamer.de", "mezimages.net", "mierdamail.com", "migmail.pl", "migumail.com", "ministry-of-silly-walks.de", "mintemail.com", "misterpinball.de", "mjukglass.nu", "mmmmail.com", "moakt.com", "mobi.web.id", "mobileninja.co.uk", "moburl.com", "moncourrier.fr.nf", "monemail.fr.nf", "monmail.fr.nf", "monumentmail.com", "ms9.mailslite.com", "msa.minsmail.com", "msb.minsmail.com", "msg.mailslite.com", "mt2009.com", "mt2014.com", "mt2015.com", "muchomail.com", "mx0.wwwnew.eu", "my10minutemail.com", "mycard.net.ua", "mycleaninbox.net", "myemailboxy.com", "mymail-in.net", "mynetstore.de", "mypacks.net", "mypartyclip.de", "myphantomemail.com", "mysamp.de", "myspaceinc.com", "myspaceinc.net", "myspaceinc.org", "myspacepimpedup.com", "myspamless.com", "mytempemail.com", "mytempmail.com", "mythrashmail.net", "mytrashmail.com", "nabuma.com", "national.shitposting.agency", "naver.com", "neomailbox.com", "nepwk.com", "nervmich.net", "nervtmich.net", "netmails.com", "netmails.net", "netzidiot.de", "neverbox.com", "nevermail.de", "nice-4u.com", "nigge.rs", "nincsmail.hu", "nmail.cf", "nnh.com", "noblepioneer.com", "nobugmail.com", "nobulk.com", "nobuma.com", "noclickemail.com", "nogmailspam.info", "nomail.pw", "nomail.xl.cx", "nomail2me.com", "nomorespamemails.com", "nonspam.eu", "nonspammer.de", "noref.in", "nospam.wins.com.br", "no-spam.ws", "nospam.ze.tc", "nospam4.us", "nospamfor.us", "nospammail.net", "nospamthanks.info", "notmailinator.com", "notsharingmy.info", "nowhere.org", "nowmymail.com", "ntlhelp.net", "nullbox.info", "nurfuerspam.de", "nus.edu.sg", "nwldx.com", "o2.co.uk", "o2.pl", "objectmail.com", "obobbo.com", "odaymail.com", "odnorazovoe.ru", "ohaaa.de", "omail.pro", "oneoffemail.com", "oneoffmail.com", "onewaymail.com", "onlatedotcom.info", "online.ms", "oopi.org", "opayq.com", "ordinaryamerican.net", "otherinbox.com", "ourklips.com", "outlawspam.com", "ovpn.to", "owlpic.com", "pancakemail.com", "paplease.com", "pcusers.otherinbox.com", "pepbot.com", "pfui.ru", "phentermine-mortgages-texas-holdem.biz", "pimpedupmyspace.com", "pjjkp.com", "plexolan.de", "poczta.onet.pl", "politikerclub.de", "poofy.org", "pookmail.com", "postonline.me", "powered.name", "privacy.net", "privatdemail.net", "privy-mail.com", "privymail.de", "privy-mail.de", "proxymail.eu", "prtnx.com", "prtz.eu", "punkass.com", "put2.net", "putthisinyourspamdatabase.com", "pwrby.com", "qasti.com", "qisdo.com", "qisoa.com", "qoika.com", "qq.com", "quickinbox.com", "quickmail.nl", "rcpt.at", "rcs.gaggle.net", "reallymymail.com", "realtyalerts.ca", "receiveee.com", "recode.me", "recursor.net", "recyclemail.dk", "redchan.it", "regbypass.com", "regbypass.comsafe-mail.net", "rejectmail.com", "reliable-mail.com", "remail.cf", "remail.ga", "rhyta.com", "rklips.com", "rmqkr.net", "royal.net", "rppkn.com", "rtrtr.com", "s0ny.net", "safe-mail.net", "safersignup.de", "safetymail.info", "safetypost.de", "sandelf.de", "saynotospams.com", "scatmail.com", "schafmail.de", "schmeissweg.tk", "schrott-email.de", "secmail.pw", "secretemail.de", "secure-mail.biz", "secure-mail.cc", "selfdestructingmail.com", "selfdestructingmail.org", "sendspamhere.com", "senseless-entertainment.com", "server.ms", "services391.com", "sharklasers.com", "shieldedmail.com", "shieldemail.com", "shiftmail.com", "shitmail.me", "shitmail.org", "shitware.nl", "shmeriously.com", "shortmail.net", "shut.name", "shut.ws", "sibmail.com", "sify.com", "sina.cn", "sina.com", "sinnlos-mail.de", "siteposter.net", "skeefmail.com", "sky-ts.de", "slapsfromlastnight.com", "slaskpost.se", "slave-auctions.net", "slopsbox.com", "slushmail.com", "smaakt.naar.gravel", "smapfree24.com", "smapfree24.de", "smapfree24.eu", "smapfree24.info", "smapfree24.org", "smashmail.de", "smellfear.com", "snakemail.com", "sneakemail.com", "sneakmail.de", "snkmail.com", "sofimail.com", "sofortmail.de", "sofort-mail.de", "sogetthis.com", "sohu.com", "solvemail.info", "soodomail.com", "soodonims.com", "spam.la", "spam.su", "spam4.me", "spamail.de", "spamarrest.com", "spamavert.com", "spam-be-gone.com", "spambob.com", "spambob.net", "spambob.org", "spambog.com", "spambog.de", "spambog.net", "spambog.ru", "spambooger.com", "spambox.info", "spambox.irishspringrealty.com", "spambox.org", "spambox.us", "spamcannon.com", "spamcannon.net", "spamcero.com", "spamcon.org", "spamcorptastic.com", "spamcowboy.com", "spamcowboy.net", "spamcowboy.org", "spamday.com", "spamdecoy.net", "spamex.com", "spamfighter.cf", "spamfighter.ga", "spamfighter.gq", "spamfighter.ml", "spamfighter.tk", "spamfree.eu", "spamfree24.com", "spamfree24.de", "spamfree24.eu", "spamfree24.info", "spamfree24.net", "spamfree24.org", "spamgoes.in", "spamgourmet.com", "spamgourmet.net", "spamgourmet.org", "spamherelots.com", "spamhereplease.com", "spamhole.com", "spamify.com", "spaminator.de", "spamkill.info", "spaml.com", "spaml.de", "spammotel.com", "spamobox.com", "spamoff.de", "spamsalad.in", "spamslicer.com", "spamspot.com", "spamstack.net", "spamthis.co.uk", "spamthisplease.com", "spamtrail.com", "spamtroll.net", "speed.1s.fr", "spoofmail.de", "squizzy.de", "sry.li", "ssoia.com", "startkeys.com", "stinkefinger.net", "stop-my-spam.cf", "stop-my-spam.com", "stop-my-spam.ga", "stop-my-spam.ml", "stop-my-spam.tk", "stuffmail.de", "suioe.com", "super-auswahl.de", "supergreatmail.com", "supermailer.jp", "superplatyna.com", "superrito.com", "superstachel.de", "suremail.info", "sweetxxx.de", "tafmail.com", "tagyourself.com", "talkinator.com", "tapchicuoihoi.com", "techemail.com", "techgroup.me", "teewars.org", "teleworm.com", "teleworm.us", "temp.emeraldwebmail.com", "tempail.com", "tempalias.com", "tempemail.biz", "tempemail.co.za", "tempemail.com", "tempe-mail.com", "tempemail.net", "tempimbox.com", "tempinbox.co.uk", "tempinbox.com", "tempmail.eu", "tempmail.it", "temp-mail.org", "temp-mail.ru", "tempmail2.com", "tempmaildemo.com", "tempmailer.com", "tempmailer.de", "tempomail.fr", "temporarily.de", "temporarioemail.com.br", "temporaryemail.net", "temporaryemail.us", "temporaryforwarding.com", "temporaryinbox.com", "temporarymailaddress.com", "tempthe.net", "tempymail.com", "tfwno.gf", "thanksnospam.info", "thankyou2010.com", "thc.st", "thecloudindex.com", "thelimestones.com", "thisisnotmyrealemail.com", "thismail.net", "thrma.com", "throam.com", "throwawayemailaddress.com", "throwawaymail.com", "tijdelijkmailadres.nl", "tilien.com", "tittbit.in", "tizi.com", "tmail.com", "tmailinator.com", "toiea.com", "tokem.co", "toomail.biz", "topcoolemail.com", "topfreeemail.com", "topranklist.de", "tormail.net", "tormail.org", "tradermail.info", "trash2009.com", "trash2010.com", "trash2011.com", "trash-amil.com", "trashcanmail.com", "trashdevil.com", "trashdevil.de", "trashemail.de", "trashinbox.com", "trashmail.at", "trash-mail.at", "trash-mail.cf", "trashmail.com", "trash-mail.com", "trashmail.de", "trash-mail.de", "trash-mail.ga", "trash-mail.gq", "trashmail.me", "trash-mail.ml", "trashmail.net", "trashmail.org", "trash-mail.tk", "trashmail.ws", "trashmailer.com", "trashymail.com", "trashymail.net", "trayna.com", "trbvm.com", "trialmail.de", "trickmail.net", "trillianpro.com", "tryalert.com", "turual.com", "twinmail.de", "tyldd.com", "ubismail.net", "uggsrock.com", "umail.net", "upliftnow.com", "uplipht.com", "uroid.com", "us.af", "uyhip.com", "valemail.net", "venompen.com", "verticalscope.com", "veryrealemail.com", "veryrealmail.com", "vidchart.com", "viditag.com", "viewcastmedia.com", "viewcastmedia.net", "viewcastmedia.org", "vipmail.name", "vipmail.pw", "viralplays.com", "vistomail.com", "vomoto.com", "vpn.st", "vsimcard.com", "vubby.com", "vztc.com", "walala.org", "walkmail.net", "wants.dicksinhisan.us", "wants.dicksinmyan.us", "wasteland.rfc822.org", "watchfull.net", "watch-harry-potter.com", "webemail.me", "webm4il.info", "webuser.in", "wegwerfadresse.de", "wegwerfemail.com", "wegwerfemail.de", "wegwerf-email.de", "weg-werf-email.de", "wegwerfemail.net", "wegwerf-email.net", "wegwerfemail.org", "wegwerf-email-addressen.de", "wegwerfemailadresse.com", "wegwerf-email-adressen.de", "wegwerf-emails.de", "wegwerfmail.de", "wegwerfmail.info", "wegwerfmail.net", "wegwerfmail.org", "wegwerpmailadres.nl", "wegwrfmail.de", "wegwrfmail.net", "wegwrfmail.org", "wetrainbayarea.com", "wetrainbayarea.org", "wh4f.org", "whatiaas.com", "whatpaas.com", "whatsaas.com", "whopy.com", "whyspam.me", "wickmail.net", "wilemail.com", "willhackforfood.biz", "willselfdestruct.com", "winemaven.info", "wmail.cf", "wolfsmail.tk", "writeme.us", "wronghead.com", "wuzup.net", "wuzupmail.net", "www.e4ward.com", "www.gishpuppy.com", "www.mailinator.com", "wwwnew.eu", "x.ip6.li", "xagloo.co", "xagloo.com", "xemaps.com", "xents.com", "xmail.com", "xmaily.com", "xoxox.cc", "xoxy.net", "xxtreamcam.com", "xyzfree.net", "yandex.com", "yanet.me", "yapped.net", "yeah.net", "yep.it", "yogamaven.com", "yomail.info", "yopmail.com", "yopmail.fr", "yopmail.gq", "yopmail.net", "youmail.ga", "youmailr.com", "yourdomain.com", "you-spam.com", "ypmail.webarnak.fr.eu.org", "yuurok.com", "yxzx.net", "z1p.biz", "za.com", "zebins.com", "zebins.eu", "zehnminuten.de", "zehnminutenmail.de", "zetmail.com", "zippymail.info", "zoaxe.com", "zoemail.com", "zoemail.net", "zoemail.org", "zomg.info");
    
    foreach ($invalidEmails as $invalidEmail) 
    {
      if (strpos($email, $invalidEmail) !== FALSE) 
      {
        return -2;
      }
    }
    
    $exist = false;
		$result = $this->database->Select('login','users','login = "'.$acc.'"',1);
		if ($result) 
		{
			$exist = $result->num_rows > 0;
			$result->close();
		}
		if ($exist)
		{
			return -3;
		}
    
		$result = $this->database->Select('email','users','email = "'.$email.'"',1);
		if ($result) 
		{
			$exist = $result->num_rows > 0;
			$result->close();
		}
			if ($exist)
			{
				return -4;
			}
    
    $code = $this->GetCode($acc, $email);
		
		$result = $this->database->Insert('login,password,email,activationcode','"'.$acc.'","'.$pw.'","'.$email.'", "'.$code.'"', 'activations');
		
		return $this->database->GetLastID();
	}
	
	public function Register($acc, $pw, $email)
	{
		//Return -1 = acc invalid
    //Return -2 = email invalid
    //Return -3 = account taken
    //Return -4 = email taken
    
    $acc = $this->database->EscapeString($acc);
		$email = $this->database->EscapeString($email);
    $pw = $this->database->EscapeString($pw);
    $pw = Account::GetPassword($pw);
		
		if($acc == '')
		{
			return -1;
		}
		else if($email == '' || !isset(explode('@', $email)[1]))
		{
			return -2;
		}
    
    $exist = false;
		$result = $this->database->Select('login','users','login = "'.$acc.'"',1);
		if ($result) 
		{
			$exist = $result->num_rows > 0;
			$result->close();
		}
		if ($exist)
		{
			return -3;
		}
    
		$result = $this->database->Select('email','users','email = "'.$email.'"',1);
		if ($result) 
		{
			$exist = $result->num_rows > 0;
			$result->close();
		}
			if ($exist)
			{
				return -4;
			}
    
    $code = $this->GetCode($acc, $email);
		
		$result = $this->database->Insert('login,password,email,activationcode','"'.$acc.'","'.$pw.'","'.$email.'", "'.$code.'"', 'activations');
		
		return $this->database->GetLastID();
	}
  
	private function LoadPlayer($key, $select = '*', $id = -1)
	{
    if($key == '')
    {
      return;
    }
		$result = $this->database->Select($select,'users',$key.' = "'.$id.'"',1);
    
		if ($result) 
		{
		  if ($result->num_rows > 0)
			{
				$row = $result->fetch_assoc();
				$this->data = $row;
				$this->valid = true;
			}
      
			$result->close();
		}
	}
}

$account = new Account($accountDB, 0);