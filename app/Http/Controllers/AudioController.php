<?php

namespace App\Http\Controllers;

use App\Models\Track;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Ramsey\Http\Range\Exception\NoRangeException;
use Ramsey\Http\Range\Range;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AudioController extends Controller
{
    public function get(ServerRequestInterface $request)
    {
        $hash = Route::current()->parameter('hash');

        if(empty($hash))
            throw new BadRequestHttpException('Hash not in request');

        $track = Track::whereHash($hash)->firstOrFail();

        $filePath = Storage::disk('media')->path($track->path);
        $fileSize = filesize($filePath);

        $mimeType = mime_content_type($filePath);

        $range = new Range($request, filesize($filePath));

        try {
            // getRanges() always returns an iterable collection of range values,
            // even if there is only one range, as is the case in this example.
            foreach ($range->getUnit()->getRanges() as $rangeValue) {

                $start = $rangeValue->getStart();
                $length = $rangeValue->getLength();

                break; // NB: Only one range thanks

            }
        } catch (NoRangeException $e) {

            // This wasn't a range request or the `Range` header was empty.
            $start = 0;
            $length = 1024;

        }

        $end = $start + $length - 1;

        $content = file_get_contents(
            filename: $filePath,
            offset: $start,
            length: $length,
        );

        return new Response($content, Response::HTTP_PARTIAL_CONTENT, [
            'Accept-Ranges' => 'bytes',
            'Content-Type' => $mimeType,
            'Content-Length' => $length,
            'Content-Range' => "bytes $start-$end/$fileSize"
        ]);
    }
}
