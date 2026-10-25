<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use InvalidArgumentException;

class GenererImageQrCode
{
    public function executer(string $token): string
    {
        if ($token === '') {
            throw new InvalidArgumentException('Le token QR ne peut pas être vide.');
        }

        $renderer = new ImageRenderer(new RendererStyle(320, 4), new SvgImageBackEnd());

        $svg = (new Writer($renderer))->writeString($token);

        return preg_replace('/^<\?xml[^?]*\?>\s*/', '', $svg) ?? $svg;
    }
}
