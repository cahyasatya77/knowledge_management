<?php

namespace app\controllers;

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
    
    protected function sendReplay($chatid, $msgid, $text, $mode)
    {
        $data = [
            'parse_mode' => $mode,
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
    
    protected function deleteMessage($chatid, $msgid)
    {
        $data = [
            'chat_id' => $chatid,
            'message_id' => $msgid,
        ];
        
        $client = new Client();
        $response = $client->createRequest()
                ->setMethod('post')
                ->setUrl($this->requesturl('deleteMessage'))
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
    
    protected function createResponseNewMember($msg_name, $msg_title, $msg_member_id)
    {
        $html = "Hai.. ".$msg_name." \n";
        $html .= "apa kabar... \n";
        $html .= "selamat datang di group ".$msg_title." \n";
        $html .= "jangan lupa memperkenalkan diri <a href='tg://user?id=".$msg_member_id."'>".$msg_name."</a>.";
        return $html;
    }


    protected function processMessage($message) 
    {
        $updateid = $message["update_id"];
        $message_data = $message["message"];
        
        // Membalas pesan otomatis
//        if (isset($message_data["text"])) {
//            $chatid = $message_data["chat"]["id"];
//            $message_id = $message_data["message_id"];
//            $text = $message_data["text"];
//            $response = $this->createResponse($text);
//            $this->sendReplay($chatid, $message_id, $response);
//        }
        
        // Menghapus pesan otomatis
        if (isset($message_data["text"])) {
            if ($message_data["text"] == "spam" || $message_data["text"] == "Spam") {
                $chatid = $message_data["chat"]["id"];
                $message_id = $message_data["reply_to_message"]["message_id"];
                $this->deleteMessage($chatid, $message_id);
            }
        }
        
        // Memberikan ucapan selamat datang di group
        if (isset($message_data["new_chat_member"])) {
            $chatid = $message_data["chat"]["id"];
            $message_id = $message_data["message_id"];
            $msg_name = $message_data['new_chat_member']['first_name'];
            $msg_title = $message_data['chat']['title'];
            $msg_member_id = $message_data['new_chat_participant']['id'];
            $response = $this->createResponseNewMember($msg_name, $msg_title, $msg_member_id);
            $parse_mode = 'html';
            $this->sendReplay($chatid, $message_id, $response, $parse_mode);
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
