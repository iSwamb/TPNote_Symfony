<?php

namespace App\Services;

class PoleEmploiService
{
    public function getOffers()
    {
        $apiUrl = 'http://rh.ptitlabo.xyz/?method=offers&limit=100';
        $jsonData = file_get_contents($apiUrl);
        $offers = json_decode($jsonData, true);
        return $offers;
    }
}