<?php
class ImageHandler
{    
  private $path = '';
	
	public function __construct($path)
	{
    $this->path = $path;
  }
  
  public function generateRandomString($length = 10) 
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) 
    {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }
  
  public function UploadMultiple($file, &$imagePath, $maxWidth=200, $maxHeight=200,$maxSize = 2097152, $random=true)
  {
    $countfiles = count($file['name']);
    for( $i=0; $i < $countfiles; $i++)
    {
      $format = array('image/jpeg','image/png','image/jpg','image/gif');
      
      $image_info = getimagesize($file["tmp_name"][$i]);
      $image_width = $image_info[0];
      $image_height = $image_info[1];
      
      $fileSize = $file['size'][$i];
      $fileTypes = $file['type'][$i];
      
      //-1 == file size to large
      //-2 == file size invalid
      //-3 == Format not supported
      //-4 == upload was not possible
      //1 == works
      if($fileSize >= $maxSize || $image_width > $maxWidth || $image_height > $maxHeight)
        return -1;
      else if($fileSize == 0)
        return -2;
      else if(!in_array($fileTypes, $format))
        return -3;
      if (!file_exists($this->path)) 
        mkdir($this->path, 0777, true);
      
      $fileName = $file["name"][$i];
      $fileName = preg_replace("/[^a-zA-Z0-9.]+/", "", $fileName);
      
      if($random)
        $newFileName = $this->generateRandomString().$fileName;
      else
        $newFileName = $fileName;
      
      $filePath = $this->path.$newFileName;
      if(file_exists($filePath))
        return -4;
      
      $tmpName = $file["tmp_name"][$i];
      
      if(!move_uploaded_file($tmpName, $filePath))
        return -5;
      
      $imagePath = $filePath.'<br/>';
      }
    return 1;
  }
  
  public function Upload($file, &$imagePath, $maxWidth=200, $maxHeight=200,$maxSize = 2097152, $random=true)
  {
    $format = array('image/jpeg','image/png','image/jpg','image/gif');
    
    $image_info = getimagesize($file["tmp_name"]);
    $image_width = $image_info[0];
    $image_height = $image_info[1];
    
    $fileSize = $file['size'];
    $fileTypes = $file['type'];
    
    //-1 == file size to large
    //-2 == file size invalid
    //-3 == Format not supported
    //-4 == upload was not possible
    //1 == works
    if($fileSize >= $maxSize || $image_width > $maxWidth || $image_height > $maxHeight)
      return -1;
    else if($fileSize == 0)
      return -2;
    else if(!in_array($fileTypes, $format))
      return -3;
    if (!file_exists($this->path)) 
      mkdir($this->path, 0777, true);
    
    $fileName = $file["name"];
    $fileName = preg_replace("/[^a-zA-Z0-9.]+/", "", $fileName);
    
    if($random)
      $newFileName = $this->generateRandomString().$fileName;
    else
      $newFileName = $fileName;
    
    $filePath = $this->path.$newFileName;
    if(file_exists($filePath))
      return -4;
    
    $tmpName = $file["tmp_name"];
    
    if(!move_uploaded_file($tmpName, $filePath))
      return -5;
    
    $imagePath = $filePath;
    return 1;
  }
}
?>