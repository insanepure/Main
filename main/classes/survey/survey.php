<?php
class Survey
{
	private $database;
  private $data;
  
	function __construct($db, $id)
  {
    $this->database = $db;
    $this->LoadSurvey($id);
  }
  
  function LoadSurvey($id)
  {
    
  }
  
}