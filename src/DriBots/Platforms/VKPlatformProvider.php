<?php


namespace DriBots\Platforms;


use CURLFile;
use DriBots\Data\Attachment;
use DriBots\Data\Attachments\PhotoAttachment;
use DriBots\Data\Message;
use DriBots\Data\User;
use JsonException;
use stdClass;

class VKPlatformProvider implements BasePlatformProvider {
    public function __construct(
        public VKPlatform $platform
    ) {}

    /**
     * @throws JsonException
     */
    public function sendMessage(int $toId, string $text, Attachment $attachment = null): Message|false {
        if($messageData = $this->call("messages.send", [
            "peer_ids" => $toId,
            "message" => $text,
            "random_id" => 0,
            "attachment" => $this->uploadAttachment($toId, $attachment)
        ])){
            $messageData = $messageData[0];
            if(!isset($messageData["error"])) {
                return new Message(
                    id: $messageData['conversation_message_id']??$messageData['message_id'],
                    fromId: $this->platform->groupId,
                    text: $text,
                    attachment: $attachment
                );
            }
        }

        return false;
    }

    /**
     * @throws JsonException
     */
    private function uploadAttachment(int $peerId, ?Attachment $attachment): string {
        $response = "";
        if($attachment instanceof PhotoAttachment){
            $photo = $this->uploadPhoto($peerId, $attachment);
            $response = "photo".$photo[0]['owner_id']."_".$photo[0]['id'];
        }

        return $response;
    }

    /**
     * @throws JsonException
     */
    private function uploadPhoto(int $peer_id, PhotoAttachment $file): array{
        $response = $this->call('photos.getMessagesUploadServer', array(
            'peer_id' => $peer_id,
        ));

        $upload_response = $this->upload($response['upload_url'], $file->path);
        return $this->call('photos.saveMessagesPhoto', array(
            'photo' => $upload_response['photo'],
            'server' => $upload_response['server'],
            'hash' => $upload_response['hash'],
        ));
    }

    /**
     * @throws JsonException
     */
    private function upload(string $url, string $file): array {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile(realpath($file))));
        $json = curl_exec($curl);
        curl_close($curl);

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public function getUser(int $userId): User|false {
        if($userData = $this->call("users.get", [
            "user_ids"=>$userId,
            "fields"=>"domain"
        ])){
            $user = $userData[0];

            return new User(
                id: $user['id'],
                username: $user['domain']
            );
        }

        return false;
    }

    /**
     * @throws JsonException
     */
    public function call(string $method, array $params = []): array|false {
        if(!isset($params["access_token"])) {
            $params["access_token"] = $this->platform->accessToken;
        }

        if(!isset($params["v"])) {
            $params["v"] = $this->platform->apiVersion;
        }

        $data = file_get_contents($this->platform->apiUrl.$method."?".http_build_query($params));
        if(!$data) {
            return false;
        }

        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        if(isset($data["error"])){
            return false;
        }

        return $data["response"]??false;
    }
}