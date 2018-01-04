<?php 
class SakaFile extends S3 {

  // PHP Constructor
  public function __construct($access_key = null, $secret_key = null){
    // Dependencies
    $this->CI = &get_instance();
    $this->user = $this->CI->session->userdata();
    $this->user['id'] = &$this->user['userID'] || '';
    $this->user['username'] = &$this->user['username'] || '';
    // $this->is_login()?signin():'';
    $this->config = $this->CI->config->item('cloud_kilat');
    $this->user['folder'] = $this->create_folder_name();
    $this->folder = $this->config['default_path'];
    
    $this->cloud_kilat = &$this->ck;
    $this->bucket = $this->config['bucket'];
    $this->dirfile = '.dir';
    $this->scheme = 'http';
    // Hardcoded mime types
    // TODO: find and implement a mime library in composer 
    $this->mime = array(
      'image/jpg' => 'jpg',
      'image/jpeg' => 'jpg',
      'image/png' => 'png'
    );
    // override config if param exist
    if (isset($access_key)) $this->config['access_key'] = $access_key;
    if (isset($secret_key)) $this->config['secret_key'] = $secret_key;
    
    $this->ck = new parent($this->config['access_key'],$this->config['secret_key'],$this->config['host']);
    
  }
  // Struktur folder cdn saka
  public function create_folder_name(){
    // create folder name
    return $this->config['prefix'].$this->user['id'].'-'.$this->user['username'];
  }
  // Format filename cdn saka
  // actual CI Filename encryption implementation
  public function encrypt_name(){
    $filename = md5(uniqid(mt_rand()));
    return $filename;
  }
  // Cek login status
  // Otomatis deteksi session
  public function is_login(){
    if (empty($this->user['userID'])){
     return false;
    }
    return true;
   }
  
  // Cek keberadaan folder 
  public function check_folder($folder){
    // TODO: check trailing slash 
    $file_exist = $this->check_file($folder.$this->dirfile);
    if ($file_exist) {
      return true;
    }else{
      return false;
    }

  }

  // Memastikan keberadaan folder
  public function confirm_folder($folder){
    $folder_exist = $this->check_folder($folder);
    if ($folder_exist) {
      return true;
    }else{
        // create .dir
      return $this->ck->putObject('.dir',$this->bucket,$folder.'/'.$this->dirfile,$this->ck->ACL_PUBLIC_READ);
    }
  }

  // Cek keberadaan file
  public function check_file($filepath){
    $result=$this->ck->getObjectInfo($this->bucket,$filepath);
    if ($result == false || empty($result['hash'])) {
      return false;
    }else{
      return true;
    }
  }

  // Metode membuat url untuk result
  public function create_url($path){
    // create url
    return $this->scheme.'://'.$this->config['host'].'/'.$path;

  }

  // Membuat file secara satuan
  // create (upload file to cdn) file from $_FILES value
  public function create_file($file){

    // handle ekstensi
    if (empty($this->mime[$file['type']])) $ext = ''; // tanpa ekstensi
    else $ext = '.'.$this->mime[$file['type']]; // tambah titik sebelum ekstensi

    $folder = $this->folder.'/'.$this->user['folder'].'/'; // path folder user
    $file_path = $folder.$this->encrypt_name();// encrypt nama 
    $this->confirm_folder($folder); // pastikan folder ada
    $this->ck->putObjectFile($file['tmp_name'], 
      $this->bucket,
      $file_path.$ext,  // tambah ekstensi
      $this->ck::ACL_PUBLIC_READ // izin dibaca publik
    );
    // upload thumb
    if (isset($file['tmp_name_thumb'])){
      $this->ck->putObjectFile($file['tmp_name_thumb'], 
        $this->bucket,
        $file_path.'_thumb'.$ext, // tambah suffix dan ekstensi
        $this->ck::ACL_PUBLIC_READ // izin dibaca publik
      );
    }

    $result = array(
      'url'=>$this->create_url($file_path.$ext),
      'error'=>false,
      'success'=>true
    );
    return $result;
  }

  // Membuat file secara masal
  // create files from $_FILES
  public function create_files($FILES){
    $result = array();
    foreach ($FILES as $file){
      $result[] = $this->create_file($file);
    }
    return $result;
  }
}
?>