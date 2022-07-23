<?php
class ChatMessage
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
  
  public function GetName()
  {
    return $this->data['name'];
  }
  
  public function GetAcc()
  {
    return $this->data['acc'];
  }
  
  public function GetMain()
  {
    return $this->data['main'];
  }
  
  public function GetGame()
  {
    return $this->data['game'];
  }
  
  public function GetChannel()
  {
    return $this->data['channel'];
  }
  
  public function GetText()
  {
    $text = $this->data['text'];
    //if emojis, add here
    return $text;
  }
  
  public function GetTitel()
  {
    return $this->data['titel'];
  }
  
  public function GetTitelColor()
  {
    return $this->data['titelcolor'];
  }
  
  public function GetAdmin()
  {
    return $this->data['admin'];
  }
  
  public function GetType()
  {
    return $this->data['type'];
  }
  
  public function GetTime()
  {
    return $this->data['time'];
  }
  
}

?>