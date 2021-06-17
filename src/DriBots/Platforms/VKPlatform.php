<?php declare(strict_types=1);


namespace DriBots\Platforms;


use DriBots\Data\Attachment;
use DriBots\Data\Attachments\PhotoAttachment;
use DriBots\Data\Event;
use DriBots\Data\Message;
use JetBrains\PhpStorm\Pure;

class VKPlatform extends BasePlatform {

    private array $data;
    private VKPlatformProvider $platformProvider;
    private array $groupData;

    public function __construct(
        public string $accessToken,
        public int $groupId,
        public ?string $secretCode = null,
        public ?string $confirmationCode = null,
        public string $apiVersion = "5.104",
    ) {
        $this->platformProvider = new VKPlatformProvider($this);
        $this->groupData = $this->platformProvider->api->getGroup($this->groupId);
    }

    public function getName(): string {
        return "vk";
    }

    public function handleEnd(): void {
        if($this->data['type']==="confirmation"){
            if($this->confirmationCode!==null){
                echo $this->confirmationCode;
            }else if($response = $this->platformProvider->api->request(
                "groups.getCallbackConfirmationCode", [
                    "group_id"=>$this->groupId
                ])){
                echo $response['code'];
            }else{
                echo "Error :(";
            }
        }else {
            echo "ok";
        }
    }

    public function requestIsAccept(): bool {
        $this->data = json_decode(file_get_contents("php://input"),
            true, flags: JSON_THROW_ON_ERROR);
        return isset($this->data['type'], $this->data['group_id'])&&
            (!($this->secretCode!==null)||(isset($this->data["secret"])&&
                    $this->data["secret"]===$this->secretCode));
    }

    public function getEvent(): Event|false {
        switch ($this->data['type']) {
            case "message_new":
                $message = $this->parseMessage($this->data['object']['message']);
                if(str_starts_with($message->text, "@{$this->groupData['screen_name']}")){
                    return Event::INLINE_QUERY(new VKInlineQuery((string) $message->id,
                        $message->chatId, $message->user, substr($message->text,
                            count($this->groupData['screen_name'])+1)));
                }

                return Event::NEW_MESSAGE($message);
        }

        return false;
    }

    public function parseMessage(array $data): Message {
        return new Message(
            id: $data['conversation_message_id'],
            chatId: $data['peer_id'],
            ownerId: $data['from_id'],
            text: $data['text'],
            attachment: count($data['attachments'])!==0?
                $this->parseAttachment($data['attachments'][0]):null,
            user: $data['from_id']>0?
                $this->platformProvider->getUser(0, $data['from_id']):null
        );
    }

    #[Pure] public function parseAttachment(array $attachment): ?Attachment{
        if($attachment['type']==="photo"){
            $attachment = $attachment['photo'];

            return new PhotoAttachment(
                path: $attachment['sizes'][(int) (count($attachment['sizes'])/2)]['url'],
                extension: "jpg"
            );
        }

        return null;
    }

    public function getPlatformProvider(): VKPlatformProvider {
        return $this->platformProvider;
    }
}