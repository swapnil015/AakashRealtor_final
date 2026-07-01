<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Honeypot spam guard for public forms (inquiries, requirements, contact).
 *
 * The frontend renders a hidden field (default name "website") that real
 * users never see. If a bot fills it, OR the form is submitted impossibly
 * fast (< 2s after render via the `_ts` timestamp), we silently 200 so the
 * bot believes it succeeded while we drop the payload.
 */
class BlockSpamBots
{
    public function handle(Request $request, Closure $next, string $field = 'website'): Response
    {
        // 1) Honeypot field was filled -> bot.
        if (filled($request->input($field))) {
            return ApiResponse::success(null, 'Thank you. We will be in touch.', 200);
        }

        // 2) Submitted suspiciously fast after the form was rendered.
        $renderedAt = (int) $request->input('_ts', 0);
        if ($renderedAt > 0) {
            $elapsedMs = (now()->valueOf()) - $renderedAt;
            if ($elapsedMs >= 0 && $elapsedMs < 2000) {
                return ApiResponse::success(null, 'Thank you. We will be in touch.', 200);
            }
        }

        // Strip control fields so they never reach validators/models.
        $request->request->remove($field);
        $request->request->remove('_ts');

        return $next($request);
    }
}
