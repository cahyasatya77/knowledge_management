<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\httpclient\Client;

class YiibotController extends Controller 
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex()
    {
        while (true) {
            $this->processOne();
        }
    }
    
    protected function requesturl($method) {
        // $method = $method;
        return 'https://api.telegram.org/bot'.Yii::$app->params['token'].'/' . $method;
    }
    
    protected function getUpdates($offset)
    {
        $url = $this->requesturl("getUpdates"). "?offset=" . $offset;
        $resp = file_get_contents($url);
        $result = json_decode($resp, true);
        if ($result['ok'] == 1) {
            return $result["result"];
        }
        return [];
    }
    
    protected function sendReplay($chatid, $msgid, $text)
    {
        $data = [
            'chat_id' => $chatid,
            'text' => $text,
            'reply_to_message_id' => $msgid,
        ];
        
        $client = new Client();
        $response = $client->createRequest()
                ->setMethod('post')
                ->setUrl($this->requesturl('sendMessage'))
                ->addHeaders([
                    'content-type' => 'application/x-www-form-urlencoded',
                ])
                ->setData($data)
                ->send();
        if ($response->isOk) {
            $result = $response->content;
        }
        
        print_r($result);
    }
    
    protected function createResponse($text) 
    {
        return $text;
    }
    
    protected function processMessage($message) 
    {
        $updateid = $message["update_id"];
        $message_data = $message["message"];
        if (isset($message_data["text"])) {
            $chatid = $message_data["chat"]["id"];
            $message_id = $message_data["message_id"];
            $text = $message_data["text"];
            $response = $this->createResponse($text);
            $this->sendReplay($chatid, $message_id, $response);
        }
        
        if (isset($message_data["new_chat_member"])) {
            $chatid = $message_data["chat"]["id"];
            $message_id = $message_data["message_id"];
            $pesan = "Hai ".$message_data['new_chat_member']['first_name']."\n";
            $pesan.= "Selamat datang di Grup ".$message_data['chat']['title']."\n";
            $pesan.= "Silahkan perkenalkan diri anda.";
            $response = $this->createResponse($pesan);
            $this->sendReplay($chatid, $message_id, $response);
        }
        
        return $updateid;
    }

    protected function processOne() 
    {
        $update_id = 0 ;
        
        if (file_exists("last_update_id")) {
            $update_id = (int) file_get_contents("last_update_id");
        }
//        
//        if (file_exists("new_chat_members")) {
//            $update_id = file_get_contents("new_chat_members");
//        }
        
        $updates = $this->getUpdates($update_id);
        
        foreach ($updates as $message) {
            $update_id = $this->processMessage($message);
        }
        file_put_contents("last_update_id", $update_id + 1);
    }
}
