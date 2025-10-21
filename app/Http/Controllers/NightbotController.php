<?php

namespace App\Http\Controllers;

use App\Facades\NowPlaying;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class NightbotController extends Controller
{
    private function getDisplayName(Request $request): string
    {
        $header = $request->header('X-Nightbot-User');

        parse_str($header, $params);

        return $params['displayName'];
    }

    public function vote(Request $request): Response
    {
        if(!$request->has('type'))
            throw new BadRequestHttpException();

        $type = trim( Str::lower($request->input('type')) );

        $emoji = match($type) {
            'up' => '⬆️',
            'down' => '⬇️',
            default => throw new UnprocessableEntityHttpException('Invalid vote type')
        };

        $track = NowPlaying::track();

        $track->votes()->updateOrCreate([
            'display_name' => $this->getDisplayName($request)
        ], [
            'type' => $type
        ]);

        // TODO: Text response
        return new Response("Thank you! Your vote $emoji was recorded");
    }
}
