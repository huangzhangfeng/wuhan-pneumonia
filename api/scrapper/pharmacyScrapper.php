<?php
class pharmacyScrapper {
  public function __construct() {
    $this->scrap_url = "https://docs.google.com/spreadsheets/d/1x4gHNkS5cfKO8qi-MIp7EiNZP2m5zhK-yv9XSseZqmA/gviz/tq?tqx=out:html&tq&gid=225766462";
    $this->store_folder = 'scraps';
    $this->store_filename = 'pharmacy.json';
  }
  
  public function scrapContent() {
    try {   
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->scrap_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $server_output = curl_exec ($ch);
      $scrap_error = curl_error($ch);
      $scrap_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      
      curl_close ($ch);

      // Error handling
      if ($scrap_error || $scrap_status_code !== 200) {
        throw new Exception($scrap_error);
      }
      
      // Convert scrapped table to json
      $extractedRows = $this->convertTableToJson($server_output);
      $api_output = json_encode($extractedRows);

      // Backup scrap data for future use
      if(!is_dir($this->store_folder)){
        if (!mkdir($this->store_folder, 0777, true)) {
          // Failed to create folder, skip saving
          return $api_output;
        }
      }

      $fp = fopen($this->store_folder . "/" . $this->store_filename, "w");
      
      // Lock file for writing
      if (flock($fp, LOCK_EX)) {  
        ftruncate($fp, 0);
        fwrite($fp, $api_output);
        fflush($fp);
        flock($fp, LOCK_UN);
      } 
      
      fclose($fp);

      return $api_output;
    } catch (Exception $e) {
      // Show backup data
      if(file_exists($this->store_folder . "/" . $this->store_filename)) {
        $backup_data = file_get_contents($this->store_folder . "/" . $this->store_filename);
        return $backup_data;
      }
        
      return '{"status": 500, "reason": "' . $e . '"}';
    }
  }

  public function convertTableToJson($html) {
    $dom = new DOMDocument;
    $dom->loadHTML($html);
    $result = [];
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//table/tr') as $tr) {
      $row = [];
      foreach ($xpath->query("td", $tr) as $key=>$td) {
        $row[$key] = trim($td->textContent);
      }

      array_push($result, $row);
    }

    return $result;
  }
}
?>