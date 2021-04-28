<?php


namespace DriBots\Platforms;


use DriBots\Data\Message;
use JsonException;

class VKPlatformProvider implements BasePlatformProvider {
    public function __construct(
        public VKPlatform $platform
    ) {}

    /**
     * @throws JsonException
     */
    public function sendMessage(int $toId, string $text): Message|false {
        if($messageData = $this->call("messages.send", [
            "peer_ids"=>$toId,
            "message"=>$text
        ])){
            $messageData = $messageData['peer_ids'][0];
            if(!isset($messageData["error"]))
                return new Message(
                    id: $messageData['conversation_message_id'],
                    fromId: $this->platform->groupId,
                    text: $text
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

        if(isset($data["response"])){
            return $data["response"];
        }

        return false;
    }
}