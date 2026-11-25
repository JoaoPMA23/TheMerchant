<?php

namespace App\View\Composers;

use App\Services\ChatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Entrega ao cabeçalho o número de mensagens não lidas, sem consulta dentro da view.
 *
 * Sem cache de propósito: depois de ler uma conversa, o contador precisa cair na próxima página.
 */
class UnreadChatCountComposer
{
    public function __construct(private readonly ChatService $chat)
    {
    }

    public function compose(View $view): void
    {
        $user = Auth::user();

        $view->with('unreadChatCount', $user ? $this->chat->getUnreadCountForUser($user) : 0);
    }
}
