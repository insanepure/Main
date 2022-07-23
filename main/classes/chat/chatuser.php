<?php
class ChatUser
{
  private $data;
  
  
	function __construct($data)
  {
    $this->data = $data;
  }
  
  public function GetID()
  {
    return $this->data['id'];
  }
  
  public function GetAcc()
  {
    return $this->data['acc'];
  }
  
  public function GetMain()
  {
    return $this->data['main'];
  }
  
  public function GetTitel()
  {
    return $this->data['titel'];
  }
  
  public function GetTitelColor()
  {
    return $this->data['titelcolor'];
  }
  
  public function GetGame()
  {
    return $this->data['game'];
  }
  
  public function GetName()
  {
    return $this->data['name'];
  }
  
  public function GetAdmin()
  {
    return $this->data['admin'];
  }
  
  public function GetChannel()
  {
    return $this->data['channel'];
  }
  
}

?>