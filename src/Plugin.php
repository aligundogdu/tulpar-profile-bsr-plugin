<?php

declare(strict_types=1);

namespace TulparPlugin\BuildShipRepeat;

use Tulpar\Plugin\PluginContext;

/**
 * Referansli davet. Yonetici olmayan bir uye birini davet etmek istediginde
 * /davet ile talep acar; yoneticilere Davet Botu'ndan ozel mesaj gider, biri
 * onaylayinca eklenti onun adina tek kullanimlik bir davet olusturur ve linki
 * talep eden uyeye iletir. Kisi katilinca uyeliginde kim onayladi, kim onerdi
 * kaydi kalir (cekirdek tutar).
 *
 * Standart davet mekanizmasina dokunmaz: yoneticiler davet penceresini
 * kullanmaya devam eder.
 */
class Plugin
{
    private const COMMAND = 'davet';
    private const MODAL_ID = 'bsr-referral-request';
    private const REQUESTS_KEY = 'referralRequests';
    private const SEQUENCE_KEY = 'referralSequence';
    private const MAX_PENDING_SETTING = 'maxPendingPerMember';
    private const DEFAULT_MAX_PENDING = 3;
    private const INVITE_HOURS = 168;
    private const HISTORY_LIMIT = 20;

    private const PENDING = 'pending';
    private const APPROVED = 'approved';
    private const REJECTED = 'rejected';

    private const MESSAGES = [
        'run_in_channel' => ['tr' => 'Bu komutu bir kanalda yazın; davet o kanalın çalışma alanı için açılır.', 'en' => 'Run this command in a channel; the invite is for that channel\'s workspace.'],
        'usage' => ['tr' => 'Kullanım: `/davet` (talep aç) · `/davet benim` · yöneticiler için `/davet talepler`, `/davet onayla <no>`, `/davet reddet <no> [sebep]`, `/davet gecmis`', 'en' => 'Usage: `/davet` (request) · `/davet benim` · for admins `/davet talepler`, `/davet onayla <no>`, `/davet reddet <no> [reason]`, `/davet gecmis`'],
        'invalid_form' => ['tr' => 'Ad ve geçerli bir e-posta adresi gerekli.', 'en' => 'A name and a valid email address are required.'],
        'already_pending' => ['tr' => '{email} için zaten bekleyen bir davet talebi var.', 'en' => 'There is already a pending request for {email}.'],
        'too_many_pending' => ['tr' => 'En fazla {limit} bekleyen talebin olabilir. Yöneticiler karar verince yenisini açabilirsin.', 'en' => 'You can have at most {limit} pending requests. You can open a new one once admins decide.'],
        'request_sent' => ['tr' => '{name} için davet talebin yöneticilere iletildi. Karar verilince sana haber vereceğim.', 'en' => 'Your request to invite {name} went to the admins. I\'ll let you know when they decide.'],
        'admins_use_invite_window' => ['tr' => 'Yöneticiler davet penceresinden doğrudan davet oluşturabilir. Bu komut üyelerin referanslı davet talebi içindir.', 'en' => 'Admins can create invites directly in the invite window. This command is for members\' referral requests.'],
        'admins_only' => ['tr' => 'Bu komut yalnızca yöneticiler içindir.', 'en' => 'This command is for admins only.'],
        'form_title' => ['tr' => 'Birini davet et', 'en' => 'Invite someone'],
        'form_submit' => ['tr' => 'Talebi gönder', 'en' => 'Send request'],
        'form_name' => ['tr' => 'Ad soyad', 'en' => 'Full name'],
        'form_email' => ['tr' => 'E-posta', 'en' => 'Email'],
        'form_note' => ['tr' => 'Neden davet etmek istiyorsun?', 'en' => 'Why do you want to invite them?'],
        'form_note_placeholder' => ['tr' => 'Yöneticiler için kısa bir not', 'en' => 'A short note for the admins'],
        'pending_title' => ['tr' => 'Bekleyen davet talepleri', 'en' => 'Pending invite requests'],
        'pending_line' => ['tr' => '**#{id}** {name} ({email}), referans: {requester}{note}', 'en' => '**#{id}** {name} ({email}), referred by {requester}{note}'],
        'pending_empty' => ['tr' => 'Bekleyen talep yok.', 'en' => 'No pending requests.'],
        'pending_hint' => ['tr' => 'Onaylamak için `/davet onayla <no>`, reddetmek için `/davet reddet <no> [sebep]`.', 'en' => 'Approve with `/davet onayla <no>`, reject with `/davet reddet <no> [reason]`.'],
        'not_found' => ['tr' => '{id} numaralı talep bulunamadı.', 'en' => 'Request {id} was not found.'],
        'already_decided' => ['tr' => '#{id} numaralı talep için zaten karar verilmiş.', 'en' => 'Request #{id} has already been decided.'],
        'approved_dm' => ['tr' => '🎉 **{name}** ({email}) için davet talebin {admin} tarafından onaylandı.

Bu linki ona ilet, {days} gün geçerli ve tek kullanımlık:
{url}', 'en' => '🎉 {admin} approved your request to invite **{name}** ({email}).

Send them this link; it works once and expires in {days} days:
{url}'],
        'approved_toast' => ['tr' => 'Onaylandı. Davet linki {requester} kişisine iletildi.', 'en' => 'Approved. The invite link went to {requester}.'],
        'rejected_dm' => ['tr' => '**{name}** için davet talebin {admin} tarafından reddedildi.{reason}', 'en' => '{admin} turned down your request to invite **{name}**.{reason}'],
        'reason_suffix' => ['tr' => '

Sebep: {reason}', 'en' => '

Reason: {reason}'],
        'rejected_toast' => ['tr' => '{name} için talep reddedildi.', 'en' => 'The request for {name} was rejected.'],
        'history_title' => ['tr' => 'Davet geçmişi', 'en' => 'Invite history'],
        'history_line' => ['tr' => '**#{id}** {name} · referans: {requester} · karar: {admin} · {state}', 'en' => '**#{id}** {name} · referred by {requester} · decided by {admin} · {state}'],
        'history_empty' => ['tr' => 'Henüz karar verilmiş talep yok.', 'en' => 'No decided requests yet.'],
        'mine_title' => ['tr' => 'Davet taleplerim', 'en' => 'My invite requests'],
        'mine_line' => ['tr' => '**#{id}** {name} · {state}', 'en' => '**#{id}** {name} · {state}'],
        'mine_empty' => ['tr' => 'Henüz davet talebin yok. Açmak için `/davet` yaz.', 'en' => 'You have no invite requests yet. Type `/davet` to open one.'],
        'state_pending' => ['tr' => 'bekliyor', 'en' => 'pending'],
        'state_rejected' => ['tr' => 'reddedildi', 'en' => 'rejected'],
        'state_approved' => ['tr' => 'onaylandı, henüz katılmadı', 'en' => 'approved, not joined yet'],
        'state_joined' => ['tr' => 'katıldı ({user})', 'en' => 'joined ({user})'],
        'admin_request' => ['tr' => '📨 **Davet talebi #{id}**
{requester}, **{name}** ({email}) kişisini davet etmek istiyor.{note}

Onaylamak için bir kanalda `/davet onayla {id}`, reddetmek için `/davet reddet {id} [sebep]` yaz.', 'en' => '📨 **Invite request #{id}**
{requester} wants to invite **{name}** ({email}).{note}

In any channel, type `/davet onayla {id}` to approve or `/davet reddet {id} [reason]` to reject.'],
        'decided_approved' => ['tr' => '✓ {admin} onayladı', 'en' => '✓ Approved by {admin}'],
        'decided_rejected' => ['tr' => '✗ {admin} reddetti', 'en' => '✗ Rejected by {admin}'],
    ];

    public function __construct(private PluginContext $ctx)
    {
    }

    /** @return array<string, mixed> */
    public function executeCommand(string $command, string $conversationId, string $userId, string $args): array
    {
        $workspaceId = $this->ctx->conversations()->workspaceId($conversationId);
        if ($workspaceId === null) {
            return $this->notice($userId, 'run_in_channel');
        }

        $parts = preg_split('/\s+/', trim($args), 3) ?: [];
        $action = strtolower($parts[0] ?? '');

        return match ($action) {
            '', 'iste', 'request' => $this->openRequestForm($workspaceId, $userId),
            'talepler', 'pending' => $this->listPending($workspaceId, $userId),
            'onayla', 'approve' => $this->approve($workspaceId, $userId, $parts[1] ?? ''),
            'reddet', 'reject' => $this->reject($workspaceId, $userId, $parts[1] ?? '', $parts[2] ?? ''),
            'gecmis', 'history' => $this->history($workspaceId, $userId),
            'benim', 'mine' => $this->mine($workspaceId, $userId),
            default => $this->notice($userId, 'usage'),
        };
    }

    /**
     * @param array<string, mixed> $formData
     *
     * @return array<string, mixed>
     */
    public function handleModalSubmit(string $modalId, string $submitAction, array $formData, string $conversationId, string $userId): array
    {
        $workspaceId = $this->ctx->conversations()->workspaceId($conversationId);
        if ($modalId !== self::MODAL_ID || $workspaceId === null) {
            return $this->notice($userId, 'run_in_channel');
        }

        $name = trim((string) ($formData['name'] ?? ''));
        $email = strtolower(trim((string) ($formData['email'] ?? '')));
        $note = trim((string) ($formData['note'] ?? ''));

        if ($name === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->notice($userId, 'invalid_form');
        }

        $requests = $this->requests($workspaceId);
        foreach ($requests as $request) {
            if ($request['status'] === self::PENDING && $request['email'] === $email) {
                return $this->notice($userId, 'already_pending', ['email' => $email]);
            }
        }

        $pending = count(array_filter($requests, static fn (array $r): bool => $r['status'] === self::PENDING && $r['requesterId'] === $userId));
        $limit = $this->maxPending($workspaceId);
        if ($pending >= $limit) {
            return $this->notice($userId, 'too_many_pending', ['limit' => (string) $limit]);
        }

        $id = $this->nextId($workspaceId);
        $request = [
            'id' => $id,
            'requesterId' => $userId,
            'name' => mb_substr($name, 0, 100),
            'email' => $email,
            'note' => mb_substr($note, 0, 500),
            'status' => self::PENDING,
            'createdAt' => $this->now(),
            'adminMessages' => [],
        ];

        $requester = $this->displayName($userId);
        foreach ($this->ctx->users()->workspaceAdminIds($workspaceId) as $adminId) {
            $request['adminMessages'][$adminId] = $this->ctx->bot()->sendDirectMessage(
                $adminId,
                $this->adminRequestText($adminId, $request, $requester),
            );
        }

        $requests[(string) $id] = $request;
        $this->saveRequests($workspaceId, $requests);

        return $this->toast($userId, 'request_sent', ['name' => $request['name']]);
    }

    /** @return array<string, mixed> */
    private function openRequestForm(string $workspaceId, string $userId): array
    {
        // Yoneticiler standart davet penceresini kullanir; bu yol onlar icin degil
        if ($this->ctx->users()->isWorkspaceAdmin($workspaceId, $userId)) {
            return $this->notice($userId, 'admins_use_invite_window');
        }

        return ['modal' => [
            'id' => self::MODAL_ID,
            'title' => $this->t($userId, 'form_title'),
            'submitAction' => 'request',
            'submitLabel' => $this->t($userId, 'form_submit'),
            'fields' => [
                ['name' => 'name', 'label' => $this->t($userId, 'form_name'), 'type' => 'text', 'required' => true],
                ['name' => 'email', 'label' => $this->t($userId, 'form_email'), 'type' => 'text', 'required' => true, 'placeholder' => 'ad@ornek.com'],
                ['name' => 'note', 'label' => $this->t($userId, 'form_note'), 'type' => 'textarea', 'placeholder' => $this->t($userId, 'form_note_placeholder')],
            ],
        ]];
    }

    /** @return array<string, mixed> */
    private function listPending(string $workspaceId, string $userId): array
    {
        if (!$this->ctx->users()->isWorkspaceAdmin($workspaceId, $userId)) {
            return $this->notice($userId, 'admins_only');
        }

        $lines = [];
        foreach ($this->requests($workspaceId) as $request) {
            if ($request['status'] === self::PENDING) {
                $lines[] = $this->t($userId, 'pending_line', [
                    'id' => (string) $request['id'],
                    'name' => $request['name'],
                    'email' => $request['email'],
                    'requester' => $this->displayName($request['requesterId']),
                    'note' => $request['note'] !== '' ? ' · ' . $request['note'] : '',
                ]);
            }
        }

        return $this->panel($userId, 'pending_title', $lines === [] ? $this->t($userId, 'pending_empty') : implode("\n", $lines) . "\n\n" . $this->t($userId, 'pending_hint'));
    }

    /** @return array<string, mixed> */
    private function approve(string $workspaceId, string $userId, string $rawId): array
    {
        if (!$this->ctx->users()->isWorkspaceAdmin($workspaceId, $userId)) {
            return $this->notice($userId, 'admins_only');
        }

        $requests = $this->requests($workspaceId);
        $request = $requests[ltrim($rawId, '#')] ?? null;
        if ($request === null) {
            return $this->notice($userId, 'not_found', ['id' => $rawId]);
        }
        if ($request['status'] !== self::PENDING) {
            return $this->notice($userId, 'already_decided', ['id' => (string) $request['id']]);
        }

        $invite = $this->ctx->invites()->create($workspaceId, approvedBy: $userId, referredBy: $request['requesterId'], expiresInHours: self::INVITE_HOURS);

        $request['status'] = self::APPROVED;
        $request['decidedBy'] = $userId;
        $request['decidedAt'] = $this->now();
        $request['inviteToken'] = $invite['token'];
        $requests[(string) $request['id']] = $request;
        $this->saveRequests($workspaceId, $requests);

        $admin = $this->displayName($userId);
        $this->ctx->bot()->sendDirectMessage($request['requesterId'], $this->t($request['requesterId'], 'approved_dm', [
            'name' => $request['name'],
            'email' => $request['email'],
            'admin' => $admin,
            'url' => $invite['url'],
            'days' => (string) intdiv(self::INVITE_HOURS, 24),
        ]));
        $this->markAdminMessages($request, 'decided_approved', $admin);

        return $this->toast($userId, 'approved_toast', ['name' => $request['name'], 'requester' => $this->displayName($request['requesterId'])]);
    }

    /** @return array<string, mixed> */
    private function reject(string $workspaceId, string $userId, string $rawId, string $reason): array
    {
        if (!$this->ctx->users()->isWorkspaceAdmin($workspaceId, $userId)) {
            return $this->notice($userId, 'admins_only');
        }

        $requests = $this->requests($workspaceId);
        $request = $requests[ltrim($rawId, '#')] ?? null;
        if ($request === null) {
            return $this->notice($userId, 'not_found', ['id' => $rawId]);
        }
        if ($request['status'] !== self::PENDING) {
            return $this->notice($userId, 'already_decided', ['id' => (string) $request['id']]);
        }

        $request['status'] = self::REJECTED;
        $request['decidedBy'] = $userId;
        $request['decidedAt'] = $this->now();
        $request['reason'] = mb_substr(trim($reason), 0, 300);
        $requests[(string) $request['id']] = $request;
        $this->saveRequests($workspaceId, $requests);

        $admin = $this->displayName($userId);
        $this->ctx->bot()->sendDirectMessage($request['requesterId'], $this->t($request['requesterId'], 'rejected_dm', [
            'name' => $request['name'],
            'admin' => $admin,
            'reason' => $request['reason'] !== '' ? $this->t($request['requesterId'], 'reason_suffix', ['reason' => $request['reason']]) : '',
        ]));
        $this->markAdminMessages($request, 'decided_rejected', $admin);

        return $this->toast($userId, 'rejected_toast', ['name' => $request['name']]);
    }

    /** @return array<string, mixed> */
    private function history(string $workspaceId, string $userId): array
    {
        if (!$this->ctx->users()->isWorkspaceAdmin($workspaceId, $userId)) {
            return $this->notice($userId, 'admins_only');
        }

        $decided = array_filter($this->requests($workspaceId), static fn (array $r): bool => $r['status'] !== self::PENDING);
        usort($decided, static fn (array $a, array $b): int => strcmp((string) ($b['decidedAt'] ?? ''), (string) ($a['decidedAt'] ?? '')));

        $lines = [];
        foreach (array_slice($decided, 0, self::HISTORY_LIMIT) as $request) {
            $lines[] = $this->t($userId, 'history_line', [
                'id' => (string) $request['id'],
                'name' => $request['name'],
                'requester' => $this->displayName($request['requesterId']),
                'admin' => $this->displayName((string) ($request['decidedBy'] ?? '')),
                'state' => $this->stateLabel($userId, $request),
            ]);
        }

        return $this->panel($userId, 'history_title', $lines === [] ? $this->t($userId, 'history_empty') : implode("\n", $lines));
    }

    /** @return array<string, mixed> */
    private function mine(string $workspaceId, string $userId): array
    {
        $lines = [];
        foreach ($this->requests($workspaceId) as $request) {
            if ($request['requesterId'] === $userId) {
                $lines[] = $this->t($userId, 'mine_line', [
                    'id' => (string) $request['id'],
                    'name' => $request['name'],
                    'state' => $this->stateLabel($userId, $request),
                ]);
            }
        }

        return $this->panel($userId, 'mine_title', $lines === [] ? $this->t($userId, 'mine_empty') : implode("\n", array_reverse($lines)));
    }

    /** @param array<string, mixed> $request */
    private function stateLabel(string $userId, array $request): string
    {
        return match ($request['status']) {
            self::PENDING => $this->t($userId, 'state_pending'),
            self::REJECTED => $this->t($userId, 'state_rejected'),
            default => isset($request['inviteToken']) && ($joined = $this->ctx->invites()->joinedWith((string) $request['inviteToken'])) !== null
                ? $this->t($userId, 'state_joined', ['user' => $this->displayName($joined)])
                : $this->t($userId, 'state_approved'),
        };
    }

    /**
     * Diger yoneticilerin mesaji da guncellenir; ayni talebe ikinci kez bakilmasin.
     *
     * @param array<string, mixed> $request
     */
    private function markAdminMessages(array $request, string $key, string $admin): void
    {
        $requester = $this->displayName($request['requesterId']);
        foreach ($request['adminMessages'] as $adminId => $messageId) {
            try {
                $this->ctx->bot()->updateMessage((string) $messageId, $this->adminRequestText((string) $adminId, $request, $requester)
                    . "\n\n" . $this->t((string) $adminId, $key, ['admin' => $admin]));
            } catch (\Throwable) {
                // Mesaj silinmis olabilir; karari engellemez
            }
        }
    }

    /** @param array<string, mixed> $request */
    private function adminRequestText(string $adminId, array $request, string $requester): string
    {
        return $this->t($adminId, 'admin_request', [
            'id' => (string) $request['id'],
            'requester' => $requester,
            'name' => $request['name'],
            'email' => $request['email'],
            'note' => $request['note'] !== '' ? "\n> " . $request['note'] : '',
        ]);
    }

    /** @return array<string, array<string, mixed>> */
    private function requests(string $workspaceId): array
    {
        $requests = $this->ctx->storage()->forWorkspace($workspaceId)->get(self::REQUESTS_KEY, []);

        return is_array($requests) ? $requests : [];
    }

    /** @param array<string, array<string, mixed>> $requests */
    private function saveRequests(string $workspaceId, array $requests): void
    {
        $this->ctx->storage()->forWorkspace($workspaceId)->set(self::REQUESTS_KEY, $requests);
    }

    private function nextId(string $workspaceId): int
    {
        $store = $this->ctx->storage()->forWorkspace($workspaceId);
        $id = (int) $store->get(self::SEQUENCE_KEY, 0) + 1;
        $store->set(self::SEQUENCE_KEY, $id);

        return $id;
    }

    private function maxPending(string $workspaceId): int
    {
        $value = (int) ($this->ctx->settings()->get(self::MAX_PENDING_SETTING, (string) self::DEFAULT_MAX_PENDING, $workspaceId) ?? self::DEFAULT_MAX_PENDING);

        return $value > 0 ? $value : self::DEFAULT_MAX_PENDING;
    }

    private function displayName(string $userId): string
    {
        return $userId === '' ? '?' : ($this->ctx->users()->get($userId)['displayName'] ?? '?');
    }

    private function now(): string
    {
        return $this->ctx->clock()->now()->format(\DateTimeInterface::ATOM);
    }

    /**
     * @param array<string, string> $params
     *
     * @return array<string, mixed>
     */
    private function notice(string $userId, string $key, array $params = []): array
    {
        return ['response' => $this->t($userId, $key, $params), 'ephemeral' => true];
    }

    /**
     * @param array<string, string> $params
     *
     * @return array<string, mixed>
     */
    private function toast(string $userId, string $key, array $params = []): array
    {
        return ['toast' => ['message' => $this->t($userId, $key, $params), 'type' => 'success']];
    }

    /** @return array<string, mixed> */
    private function panel(string $userId, string $titleKey, string $content): array
    {
        return ['panel' => ['title' => $this->t($userId, $titleKey), 'content' => $content, 'markdown' => true]];
    }

    /** @param array<string, string> $params */
    private function t(string $userId, string $key, array $params = []): string
    {
        $locale = $this->ctx->users()->get($userId)['locale'] ?? 'tr';
        $text = self::MESSAGES[$key][str_starts_with((string) $locale, 'en') ? 'en' : 'tr'] ?? $key;
        foreach ($params as $name => $value) {
            $text = str_replace('{' . $name . '}', $value, $text);
        }

        return $text;
    }
}
