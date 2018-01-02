<?php
// terminals json url
//$terminals_url = file_get_contents('http://cdn.foxpost.hu/foxpost_terminals_extended_v3.json');
//$terminals_url = 'http://foxpost.hu/foxpost_terminals/foxpost_terminals.php';


$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, "http://cdn.foxpost.hu/foxpost_terminals_extended_v3.json"); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
$terminals_url = curl_exec($ch); 
curl_close($ch);
?>
