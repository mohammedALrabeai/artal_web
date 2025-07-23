<?php 

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\DTOs\WhatsAppGroupParticipant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGroupService
{
    protected string $baseUrl = 'https://wappi.pro/api/sync';
    protected string $profileId = '0589178f-06d5';
    protected string $authToken = '40703bb7812b727ec01c24f2da518c407342559c';

    public function createGroup(string $name, array $participants): ?array
    {
        $url = "{$this->baseUrl}/group/create?profile_id={$this->profileId}";

        $response = Http::withHeaders([
            'Authorization' => $this->authToken,
        ])->post($url, [
            'name' => $name,
            'participants' => $participants,
        ]);

        if (!$response->successful() || !isset($response['result']['JID'])) {
            Log::error('Failed to create WhatsApp group', ['response' => $response->json()]);
            return null;
        }

        $groupJid = $response['result']['JID'];
        $rawParticipants = $response['result']['Participants'] ?? [];

        $parsedParticipants = collect($rawParticipants)
            ->map(fn($item) => WhatsAppGroupParticipant::fromArray($item))
            ->values();

        return [
            'group_jid' => $groupJid,
            'participants' => $parsedParticipants,
        ];
    }

   public function getInviteLink(string $groupJid): ?string
{
    $url = "{$this->baseUrl}/group/invitelink/get?profile_id={$this->profileId}&group_id={$groupJid}";

    $response = Http::withHeaders([
        'Authorization' => $this->authToken,
    ])->get($url);

    return $response->successful() && isset($response['detail'])
        ? $response['detail']
        : null;
}




    public function addParticipants(string $groupJid, array $participants): ?array
{
    $url = "{$this->baseUrl}/group/participant/add?profile_id={$this->profileId}";

    $response = Http::withHeaders([
        'Authorization' => $this->authToken,
        'Content-Type' => 'application/json',
    ])->post($url, [
        'group_id' => $groupJid,
        'participants' => $participants,
    ]);

    if ($response->successful() && isset($response['result'])) {
        return $response['result'];
    }

    Log::error('Failed to add participants to WhatsApp group (manual)', [
        'group_id' => $groupJid,
        'response' => $response->json(),
    ]);

    return null;
}

}
