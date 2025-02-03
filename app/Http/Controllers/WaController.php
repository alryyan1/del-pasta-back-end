<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Settings;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class WaController extends Controller
{
    public function sendMsg(Request $request,Order $order)
    {
        $meals_names = $order->orderMealsNames();
        $totalPrice = $order->totalPrice();
        /** @var Settings $settings */
        $settings = Settings::first();
        $msg = $settings->header_content;
        $client = new \GuzzleHttp\Client();
        try {

            $response = $client->post( 'https://waapi.app/api/v1/instances/36160/client/action/send-message', [
                'body' => json_encode([
                    'message' => $msg,
                     'chatId' =>'968'.$order?->customer?->phone .'@c.us',
                ]),
                'headers' => [
                    'accept' => 'application/json',
                    'authorization' => 'Bearer RMWHYZImQM4NrIB2ttFhlsxF4DaMTPpL7qyn2U329d42cb18',
                    'content-type' => 'application/json',
                ],
            ]);
            $body = $response->getBody()->getContents();

            return ["Response" =>json_decode($body),'show'=>true,'message'=>json_decode($body)->status];
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $error = $e->getResponse()->getBody()->getContents();
                echo "Error: " . $error;
            } else {
                echo "Error: " . $e->getMessage();
            }
        }
    }
    public function sendLocation(Request $request,Order $order)
    {

        $client = new \GuzzleHttp\Client();
        try {

            $response = $client->post( 'https://waapi.app/api/v1/instances/36160/client/action/send-location', [
                'body' => json_encode([
                    'longitude' => 56.822308144953524,
                    'latitude' => 24.258748156049695,
                    'chatId' =>'968'.$order?->customer?->phone .'@c.us',
                ]),
                'headers' => [
                    'accept' => 'application/json',
                    'authorization' => 'Bearer RMWHYZImQM4NrIB2ttFhlsxF4DaMTPpL7qyn2U329d42cb18',
                    'content-type' => 'application/json',
                ],
            ]);

            $body = $response->getBody()->getContents();

            return ["Response" =>json_decode($body),'show'=>true,'message'=>json_decode($body)->status];
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $error = $e->getResponse()->getBody()->getContents();
                echo "Error: " . $error;
            } else {
                echo "Error: " . $e->getMessage();
            }
        }


    }
    public function sendDocument(Request $request,$data,$phone)
    {
//        return $data;

        $client = new \GuzzleHttp\Client();
        try {



$client = new \GuzzleHttp\Client();

$response = $client->request('POST', 'https://waapi.app/api/v1/instances/36160/client/action/send-media', [
    'body' => json_encode(["mediaBase64"=>"$data","mediaName"=>"file.pdf","chatId"=>"968"."$phone@c.us"]),
    'headers' => [
        'accept' => 'application/json',
        'authorization' => 'Bearer RMWHYZImQM4NrIB2ttFhlsxF4DaMTPpL7qyn2U329d42cb18',
        'content-type' => 'application/json',
    ],
]);
            $body = $response->getBody()->getContents();

            return ["Response" =>json_decode($body),'show'=>true,'message'=>json_decode($body)->status];
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $error = $e->getResponse()->getBody()->getContents();
                return "Error: " . $error;
            } else {
                return "Error: " . $e->getMessage();
            }
        }


    }
}
