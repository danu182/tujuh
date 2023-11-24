<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RajaOngkirController extends Controller
{
    public function index()
    {
        $response =Http::withHeaders([
            'key'=> '74e72558201e5c7db167c146420ab0dd'])->get('https://api.rajaongkir.com/starter/city');
            $kota= $response['rajaongkir']['results']   ;
            return view('coba.rajaongkir.index', compact('kota') );
    }

    public function cities()
    {
        $response =Http::withHeaders([
            'key'=> '74e72558201e5c7db167c146420ab0dd'])->get('https://api.rajaongkir.com/starter/city');
            $kota= $response['rajaongkir']['results']   ;


            return $kota->json();
    }
        
    
    public function cek_ongkir(Request $request)
    {   
        try {
            $response = Http::withOptions(['verify' => false,])->withHeaders([
                'key' => '74e72558201e5c7db167c146420ab0dd'
            ])->post('https://api.rajaongkir.com/starter/cost',[
                'origin'        => $request->origin,
                'destination'   => $request->destination,
                'weight'        => $request->weight,
                'courier'       => $request->courier
            ])
            ->json()['rajaongkir']['results'][0]['costs'];

            return response()->json($response);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'data'    => []
            ]);
        }

    }


}
