<?php declare(strict_types=1);


namespace DriBots\Platforms;


use DriBots\Data\Attachment;
use DriBots\Data\Attachments\PhotoAttachment;
use DriBots\Data\Event;
use DriBots\Data\Message;
use JetBrains\PhpStorm\Pure;
use JsonException;

class VKPlatform extends BasePlatform {

    private array $data;
    private VKPlatformProvider $platformProvider;

    #[Pure] public function __construct(
        public string $accessToken,
        public int $groupId,
        public ?string $secretCode = null,
        public ?string $confirmationCode = null,
        public string $apiVersion = "5.103",
        public string $apiUrl = "https://api.vk.com/method/",
    ) {
        $this->platformProvider = new VKPlatformProvider($this);
    }

    public function getName(): string {
        return "vk";
    }

    /**
     * @throws JsonException
     */
    public function handleEnd(): void {
        if($this->data['type']==="confirmation"){
            if($this->confirmationCode!==null){
                echo $this->confirmationCode;
            }else if($response = $this->platformProvider->call("groups.getCallbackConfirmationCode", [
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
        try {
            $this->data = json_decode(file_get_contents("php://input"),
                true, 512, JSON_THROW_ON_ERROR);
            return isset($this->data['type'], $this->data['group_id'])&&
                (!($this->secretCode!==null)||(isset($this->data["secret"])&&
                        $this->data["secret"]===$this->secretCode));
        }catch (JsonException){}

        return false;
    }

    #[Pure] public function getEvent(): Event|false {
        return match($this->data['type']) {
            "message_new" => Event::NEW_MESSAGE($this->parseMessage($this->data['object']['message'])),
            default => false
        };
    }

    #[Pure] public function parseMessage(array $data): Message {
        return new Message(
            id: $data['conversation_message_id'],
            fromId: $data['peer_id'],
            text: $data['text'],
            attachment: count($data['attachments'])!==0?
                $this->parseAttachment($data['attachments'][0]):null
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