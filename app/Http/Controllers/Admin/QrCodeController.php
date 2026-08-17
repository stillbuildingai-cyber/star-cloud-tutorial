<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    /**
     * Generate a QR Code image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generate(Request $request)
    {
        $data = $request->query('data');
        $size = $request->query('size', 250);

        if (!$data) {
            return response()->noContent();
        }

        // Generate SVG QR Code
        $qrCode = QrCode::size($size)
            ->format('svg')
            ->margin(1)
            ->generate($data);

        return response($qrCode)->header('Content-Type', 'image/svg+xml');
    }
}
