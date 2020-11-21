<?php


namespace App\Controller;


class PositionController extends AbstractController
{
    public function add()
    {
        $json = file_get_contents('php://input');
        $jsonData = json_decode($json, true);
        $_SESSION[$jsonData['where'] . 'lat'] = $jsonData['lat'];
        $_SESSION[$jsonData['where'] . 'long'] = $jsonData['long'];
        $response = [
            'status' => 'success',
        ];
        return json_encode($response);
    }

    public function destroy(){
        session_destroy();
        session_start();
        header('Location: /');
    }
}