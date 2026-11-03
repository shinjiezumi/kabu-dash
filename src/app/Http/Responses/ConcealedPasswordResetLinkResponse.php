<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;

/**
 * 再設定リンクの送信に失敗した場合も、成功時と同じ応答を返す
 *
 * 標準の応答では、未登録のメールアドレスや短時間の再送で `email` エラーが返り、
 * メールアドレスの登録有無を判別できてしまうため差し替える。
 * Fortify はステータスをコンストラクタ引数で渡すが、この応答では使わない。
 */
final class ConcealedPasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse
{
    public function toResponse($request): RedirectResponse
    {
        return back()->with('status', trans(Password::RESET_LINK_SENT));
    }
}
