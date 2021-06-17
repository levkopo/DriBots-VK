<?php


namespace DriBots\Platforms;


use CURLFile;
use DriBots\Data\Attachment;
use DriBots\Data\Attachments\PhotoAttachment;
use DriBots\Data\InlineQuery;
use DriBots\Data\InlineQueryResult;
use DriBots\Data\Message;
use DriBots\Data\User;
use levkopo\VKApi\VKApi;

class VKPlatformProvider implements BasePlatformProvider {

    public VKApi $api;

    public function __construct(public VKPlatform $platform) {
        $this->api = VKApi::group($this->platform->accessToken, $this->platform->apiVersion);
    }

    public function sendMessage(int $chatId, string $text, Attachment $attachment = null): Message|false {
        if($messageId = $this->api->sendMessage(peerId: $chatId,
            message: $text,
            attachments: [$this->uploadAttachment($chatId, $attachment)])){
            return new Message(
                id: $messageId,
                chatId: $chatId,
                ownerId: $this->platform->groupId,
                text: $text,
                attachment: $attachment,
            );
        }

        return false;
    }

    private function uploadAttachment(int $peerId, ?Attachment $attachment): string {
        $response = "";
        if($attachment instanceof PhotoAttachment){
            $photo = $this->uploadPhoto($peerId, $attachment);
            $response = "photo".$photo[0]['owner_id']."_".$photo[0]['id'];
        }

        return $response;
    }

    private function uploadPhoto(int $peer_id, PhotoAttachment $file): array|false {
        $response = $this->api->request('photos.getMessagesUploadServer', [
            "peer_id"=>$peer_id
        ]);

        $upload_response = $this->upload($response['upload_url'], $file->path);
        return $this->api->request('photos.saveMessagesPhoto', [
            'photo' => $upload_response['photo'],
            'server' => $upload_response['server'],
            'hash' => $upload_response['hash'],
        ]);
    }


    private function upload(string $url, string $file): array {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile(realpath($file))));
        $json = curl_exec($curl);
        curl_close($curl);

        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }

    public function getUser(int $chatId, int $userId): User|false {
        if($userData = $this->api->getUser($userId, ["domain"])){
            $user = $userData[0];

            return new User(
                id: $user['id'],
                username: $user['domain']
            );
        }

        return false;
    }

    public function answerToQuery(InlineQuery $query, InlineQueryResult $inlineQueryResult): bool {
        if(!($query instanceof VKInlineQuery))
            return false;

        if(!$this->api->editMessage($query->chatId, $query->id, $inlineQueryResult->messageText)||
                $this->api->sendMessage($query->chatId, $inlineQueryResult->messageText))
            return false;

        return true;
    }
}