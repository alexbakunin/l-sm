<?php

declare(strict_types=1);

namespace App\Services\CrmEmail\DTO;

use Illuminate\Support\Facades\Validator;

class EmailDto
{
    public string $to;
    public string $from;
    public string $fromName;
    public string $subject;
    public string $content;
    public string $plain;
    public ?int   $tplId;
    public ?int   $senderId;
    public ?int   $officeId;
    public string $warning        = '';
    public string $comment        = '';
    public string $senderSystemId = '';
    public string $smtp           = '';
    public string $sendType       = '';

    public static function makeFromArray(array $data): ?self
    {
        $data['content'] = base64_decode($data['content'] ?? '');
        $data['plain'] = base64_decode($data['plain'] ?? '');
        $data['subject'] = htmlspecialchars($data['subject'] ?? '');

        $validator = Validator::make($data, [
            'to'       => 'required|email',
            'from'     => 'required|email',
            'fromName' => 'required|string',
            'subject'  => 'required|string',
            'content'  => 'required|string',
            'plain'    => 'present|string',
            'senderId' => 'required|integer',
            'tplId'    => 'present|integer',
            'officeId' => 'present|integer',
        ]);

        if ($validator->fails()) {
            return null;
        }

        $validated = $validator->validated();

        $dto = new self();
        $dto->to = $validated['to'];
        $dto->from = $validated['from'];
        $dto->fromName = $validated['fromName'];
        $dto->subject = $validated['subject'];
        $dto->content = $validated['content'];
        $dto->plain = $validated['plain'];
        $dto->senderId = $validated['senderId'];
        $dto->tplId = $validated['tplId'];
        $dto->officeId = $validated['officeId'];

        return $dto;
    }
}
